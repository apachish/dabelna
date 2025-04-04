<?php

namespace App\Http\Controllers;

use Apachish\Dabelna\App\Models\UserTelegram;
use Illuminate\Http\Request;

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
//                    dd('Location: https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"]);
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

    public function verification()
    {

    }

}
