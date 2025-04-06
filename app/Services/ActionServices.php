<?php

namespace App\Services;



use Apachish\Dabelna\App\Models\AccessBot;
use Apachish\Dabelna\App\Models\Setting;
use Apachish\Dabelna\App\Models\Transaction;
use Apachish\Dabelna\App\Models\UserTelegram;
use Telegram\Bot\Keyboard\Keyboard;

class ActionServices extends TextServices
{

    protected $service_customer;


    public function __construct($token)
    {
        parent::__construct($token);

    }
    public function addMobile()
    {

        $mobile = $this->getContact();
        if ($mobile) {
            $this->getUser()->mobile = $mobile;
            $this->getUser()->update();
            if (!$this->getUser()->fullName) {
                $text = "لطفا نام و نام خانوادگی خود را وارد نمایید";
                cache()->set($this->getKeyCache() . $this->getUserId(), "add_fullName");
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);

            }
            $text = "لطفا قوانین را مطالعه فرمایید";
            $rule = Setting::where("key", "rule")->where("status",true)->first();

            $this->getUser()->status = true;
            $this->getUser()->update();
            $text .= $rule ? $rule->value : "";
            $keyboard[0][0] = ['text' => "قوانین را خواندم و آنها را پذیرفتم"];
            $this->telegram_services::menu($this->telegram, $keyboard, $this->getUser(), $text);
            cache()->forget($this->getKeyCache() . $this->getUserId());
        } else
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => "شماره همراه وارد شده نامعتبر می باشد دوباره وارد کنید"]);
    }

    public function addFullName()
    {
        $this->getUser()->fullName = $this->message;
        $this->getUser()->update();
        cache()->forget($this->getKeyCache() . $this->getUserId());
        if (!$this->getUser()->mobile) {
            $text = "ممنون شماره خود را به اشتراک بگذارید";
            $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
            cache()->set($this->getKeyCache() . $this->getUserId(), "add_mobile");

        } elseif (!$this->getUser()->status) {
            cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
            $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
        }
    }

    public function pendingAccept()
    {
        $text = "منتظر تایید مدیر سیستم باشید تا دسترسی به شما ارائه گردد";
        cache()->set($this->getKeyCache() . $this->getUserId(), "pending_accept");
        $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
    }

    public function ruleAccept()
    {
        $this->getUser()->update(["accept_rule" => now()->format("Y-m-d H:i")]);
        $this->getUser()->change_menu = true;
        $this->getUser()->update();
        $keyboard_menu = $this->setMenu();
        $this->message_menu = "خوش آمدید، از این لحظه منو کاربری فعال شد";
        $this->menu($keyboard_menu, $this->getUser()->status, $this->getUser());
    }

    public function sendTypeCharging()
    {
        $message = "شما می توانید به یکی از روش های زیر حساب خود را شارژ کنید";
        $keyboard[0] = [
            ['text' => "افزایش ریالی", 'callback_data' => "Charging_rial_" . $this->getUser()->id],
        ];
        $keyboard[1] = [
            ['text' => "افزایش تتری", 'callback_data' => "Charging_usdt_" . $this->getUser()->id],
        ];
        $message_id = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->getUserId(), $message, $keyboard);
        cache()->set("sendTypeCharging_" .  $this->getUserId(), $message_id);
    }

    public function ChargingRial()
    {
        $message_id = cache()->get("sendTypeCharging_" .  $this->getUserId());
        $text = "📌 جهت افزایش اعتبار کیف پول مبلغ مورد نظر را به ریال ارسال کنید";
        $keyboard = [];
        $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        cache()->forget("sendTypeCharging_" .  $this->getUserId());
        cache()->set("ChargingRial_" .  $this->getUserId(), $message_id);
        logger("cache",["ChargingRial_" .  $this->getUserId()=>$message_id]);
        cache()->set($this->getKeyCache() . $this->getUserId(), "Charging_rial" );
    }

    public function checkPayRial()
    {
        $message_id = cache()->get("ChargingRial_".  $this->getUserId());
        logger("get cache",["ChargingRial_".  $this->getUserId()=>$message_id]);

        $amount = (int)convertNumber($this->getMessage());

        $text = "مبلغ ".$amount ."می خواهید شارژ کنید در صورت تایید دکمه پرداخت را زده تا به درگاه بانک منتقل شوید";
        $keyboard[0] = [
            ['text' => "انتقال به درگاه بانک", 'callback_data' => "goGateway_" . $this->getUser()->id."_".$amount],
        ];//'url' => route("payment",["user_id"=>$this->getUser()->id,"amount"=>$amount])

        if(false && !data_get($this->getUser(),"national_code")){
            $text = " برای شارژ مبلغ ". $amount. " شما باید کد ملی خود را وارد فرمایید ";
            $keyboard = [];
            cache()->forget("ChargingRial_" .  $this->getUserId());
            cache()->set($this->getKeyCache() . $this->getUserId(), "add_national_code" );
        }
        $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);
        cache()->set("checkPayRial_" .  $this->getUserId(), $message_id);

    }

    public function goGateway()
    {
        $array = str_replace('goGateway_', '', $this->getData());
        $message_id = cache()->get("checkPayRial_".  $this->getUserId());

        $info = explode("_", $array);
        $user_id = data_get($info, 0);
        $user = UserTelegram::find($user_id);
        $amount = data_get($info, 1);

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
                    $text = " برای شارژ اعتبار دکمه زیر را برای انتقال به درگاه بانک کلیک کنید";
                    $keyboard[0] = [
                        ['text' => "انتقال به درگاه بانک", 'url' => 'https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"]],
                    ];
                    $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);

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
        $ch = curl_init(env("APACHISH_URL")."get-payment/".$authority);
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


    public function addNationalCode()
    {
        $message_id = cache()->get("checkPayRial_".  $this->getUserId());

        $national_code = (int)convertNumber($this->getMessage());

        if(checkMelliCode($national_code)){

            cache()->set("ChargingRial_" .  $this->getUserId(), $message_id);
            cache()->set($this->getKeyCache() . $this->getUserId(), "Charging_rial" );
        }
    }

    public function payRial()
    {

    }

    public function ChargingUsdt()
    {
        $message_id = cache()->get("sendTypeCharging_" .  $this->getUserId());
        $text = "📌 جهت افزایش اعتبار کیف پول مبلغ مورد نظر را به تتر ارسال کنید";
        $keyboard = [];
        $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        cache()->forget("sendTypeCharging_" .  $this->getUserId());
        cache()->set("ChargingUsdt_" .  $this->getUserId(), $message_id);
        cache()->set($this->getKeyCache() . $this->getUserId(), "charging_usdt" );
    }

    public function checkPayUsdt()
    {
        $message_id = cache()->get("ChargingUsdt_".  $this->getUserId());
        $amount = (int)convertNumber($this->getMessage());

        $text = "✅ لطفا مبلغ ";
        $text .= $amount;
        $text .= "تتر را از طریق کیف پول زیر پرداخت کنید"."\n";
        $text .="👈 کاربر گرامی :"."\n";
        $text .="🏷 لطفا عکس رسید واریزی ارسال کنید تا حساب شما شارژ شود ، از ارسال رسید فیک خودداری کنید."."\n";
        $text .= "✅ تایید رسید واریزی از 10 دقیقه الی 12 ساعت.  (صبور باشید!)"."\n";
        $text .= "❗️در صورت خطا برای انتقال به پشتیبانی پیام دهید"."\n";
        $text .= "کیف پول:"."\n";
        $text .= " ".env("WALLET_USDT")."\n";
        $text .= "❗️❗️❗️❗️❗️"."\n";
        $text .= "لطفا  در واریز خود دقت فرمایید ❤️"."\n";
        $text .= "1: حتما حتما گزینه پرداخت کردم | ارسال رسید رو بزنید و بعدش رسیدتون رو ارسال کنید تا برامون بیاد!"."\n";
        $text .= "⏳ نکته این تراکنش فقط تا 15 دقیقه مهلت پرداخت دارد"."\n";

        $keyboard[0] = [
            ['text' => "✅ پرداخت کردم ارسال رسید", 'callback_data' => "get_payment_receipt_" . $this->getUser()->id],
        ];
        $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);
        cache()->set("checkPayUsdt_" .  $this->getUserId(), $message_id);

    }

    public function pendingSendFile()
    {
        $text = "👈 کاربر گرامی :"."\n";
        $text.="🏷 لطفا عکس رسید واریزی ارسال کنید تا حساب شما شارژ شود ، از ارسال رسید فیک خودداری کنید."."\n";
        $text .= "✅ تایید رسید واریزی 5 دقیقه الی 12 ساعت.";
        $keyboard = [];
        $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);
        cache()->set($this->getKeyCache() . $this->getUserId(), "charging_usdt_getFile" );

    }
    public function getPaymentReceipt()
    {
        $token = data_get($this->bot,"token");
        $apiURL = "https://api.telegram.org/bot".$token;
        $chat_id = data_get($this->getData(),"chat.id");

        $file_id = data_get($this->getPhoto(),"file_id");

        // دریافت اطلاعات فایل
        logger("$apiURL/getFile?file_id=$file_id",[$file_id]);
        $file_info = file_get_contents("$apiURL/getFile?file_id=$file_id");
        $file_info = json_decode($file_info, true);

        logger("file_info",[$file_info]);
        if (isset($file_info["result"]["file_path"])) {
            $file_path = $file_info["result"]["file_path"];
            $file_url = "https://api.telegram.org/file/bot$token/$file_path";
            $text = "✅ رسید شما ارسال شد لطفا صبور باشید تا بررسی شود$file_url";
            // ارسال لینک عکس به کاربر
            file_get_contents("$apiURL/sendMessage?chat_id=$chat_id&text=");
            $keyboard = [];
            $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);
        }
        cache()->forget($this->getKeyCache() . $this->getUserId());
    }



    /**
     * @param mixed $number
     * @param \Illuminate\Database\Eloquent\Model|Transfer $transfer_new
     * @return mixed
     */
    public static function getKeyboardRequest(Transfer $transfer_new): mixed
    {
        $m = 0;
        $k = 0;
        $number = $transfer_new->number;
        $keyboard = [];
        for ($i = 1; $i <= $number; $i++) {
            $keyboard[$k][$m++] = [
                'text' => $i,
                'callback_data' => "request_transfer_" . $transfer_new->id . "_" . $i,
            ];
            if ($m == 3) {
                $m = 0;
                $k++;
            }
        }
        if (!$keyboard)
            $keyboard = null;
        return $keyboard;
    }

    /**
     * @param string $alert_text
     * @return void
     */
    public function sendAlert(string $alert_text): void
    {
        $callback_query = $this->update[$this->getTypeMessage()];
        $callback_id = data_get($callback_query, 'id');
        if ($callback_query) {
            $url = "https://api.telegram.org/bot" . $this->bot->token . "/answerCallbackQuery?callback_query_id=$callback_id&text=" . urlencode($alert_text) . "&show_alert=true";
            file_get_contents($url);
        }
    }

}
