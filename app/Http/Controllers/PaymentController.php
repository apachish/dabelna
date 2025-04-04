<?php

namespace App\Http\Controllers;

use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\Transaction;
use Apachish\Dabelna\App\Models\UserTelegram;
use Apachish\Dabelna\App\Models\Wallet;
use Illuminate\Http\Request;
use Telegram\Bot\Api;

class PaymentController extends Controller
{
    public function goGateway($user_id,$amount)
    {
        $user = UserTelegram::find($user_id);

        $merchant_id = env("MERCHANT_ID");
        if($user == null) return  response()->json(["not found"]);


        $data = array("merchant_id" => $merchant_id,
            "amount" => $amount,
            "callback_url" => env("CALLBACK_URL"),
            "description" => "شارژ اکانت",
            "metadata" => [ "mobile"=> data_get($user, "mobile")],
        );

        $jsonData = json_encode($data);
        $ch = curl_init(env("GATEWAY_URL"));
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $result = json_decode($result, true, JSON_PRETTY_PRINT);
        curl_close($ch);



        if ($err) {
            return  response()->json(["cURL Error #:"=>$err]);
        } else {
            if (empty($result['errors'])) {
                if ($result['data']['code'] == 100) {
                    $this->setApachish($user_id,$amount,$result['data']["authority"]);
                    Transaction::create([
                        "user_id"=>$user_id,
                        "payment_method"=>"gateway",
                        "amount"=>$amount,
                        "status"=>"pending",
                        "data"=>$result['data']["authority"],
                    ]);
                    return redirect()->away('https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"]);
                }
            } else {

                return  response()->json([
                    'Error Code: ' => $result['errors']['code'],
                    'message: ' =>  $result['errors']['message']
                ]);

            }
        }

    }

    public function setApachish($user_id,$amount,$authority)
    {

        $data = array(
            "amount" => $amount,
            "user_id" => $user_id,
        );

        $jsonData = json_encode($data);
        $ch = curl_init(env("APACHISH_URL")."/get-payment/".$authority);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));

        $result = curl_exec($ch);
        $err = curl_error($ch);
        $result = json_decode($result, true, JSON_PRETTY_PRINT);
        curl_close($ch);

        if ($err) {
             logger("error apachish set",["cURL Error #:"=>$err]);
            return false;

        } else {
            if (empty($result['errors'])) {
                logger("set ok");
                return true;
            } else {

                logger("set Error",[
                    'Error Code: ' => $result['errors']['code'],
                    'message: ' =>  $result['errors']['message']
                ]);
                return false;

            }
        }

    }

    public function verification(Request $request,$authority)
    {
        $result = $request->get("result");
        $transaction_apachish = $request->get("transaction");
        $error = $request->get("error");
        $transaction = Transaction::with("user")->where("data",$authority)->first();
        $bot = Bot::where("title",'DabernaGameBot')->first();
        if($transaction){
            if($transaction_apachish){
                if(data_get($transaction_apachish,"status") ==  "successful"){
                    $transaction->status = "completed";
                    $transaction->update();
                    $ref_id = data_get($result,'data.ref_id');
                    $wallet = Wallet::create([
                        "user_id"=>data_get($transaction,'user_id'),
                        "amount"=>data_get($transaction,'amount'),
                        "type"=>Wallet::TYPE_RIAL,
                        "type_amount"=>Wallet::TYPE_AMOUNT_DEPOSIT,
                        "status"=>Wallet::STATUS_CONFIRMATION,
                        "description"=>$transaction_apachish,
                        "ref_id"=>$ref_id,
                    ]);
                    if($bot){
                        $this->telegram = new Api(data_get($bot,"token"));
                        $text = "✅ حساب شما به مبلغ";
                        $text .= data_get($transaction,'amount');
                        $text .= "شارژ شد";
                        $this->telegram->sendMessage(['chat_id' => data_get($transaction->user,"user_id"), 'text' => $text]);
                    }


                }else{
                    $transaction->status = "failed";
                    $transaction->update();
                    if($bot){
                        $this->telegram = new Api(data_get($bot,"token"));
                        $text = "❌ حساب شما به مبلغ";
                        $text .= data_get($transaction,'amount');
                        $text .= "موفق به پرداخت نشد";
                        $this->telegram->sendMessage(['chat_id' => data_get($transaction->user,"user_id"), 'text' => $text]);
                    }
                }
            }
        }
    }

}
