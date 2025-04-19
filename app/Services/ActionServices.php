<?php

namespace App\Services;



use Apachish\Dabelna\App\Models\AccessBot;
use Apachish\Dabelna\App\Models\Card;
use Apachish\Dabelna\App\Models\Game;
use Apachish\Dabelna\App\Models\Setting;
use Apachish\Dabelna\App\Models\Transaction;
use Apachish\Dabelna\App\Models\UserTelegram;
use App\Jobs\SendMessageAccountingBot;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\FileUpload\InputFile;
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
        $exist = UserTelegram::where("fullName",$this->message)->first();
        if($exist){
            $text = "نام کاربری شما در سیستم موجود می باشد";
            $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
            return false;
        }else{
            $this->getUser()->fullName = $this->message;
            $this->getUser()->update();
            cache()->forget($this->getKeyCache() . $this->getUserId());
            if (false && !$this->getUser()->mobile) {
                $text = "ممنون شماره خود را به اشتراک بگذارید";
                $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
                cache()->set($this->getKeyCache() . $this->getUserId(), "add_mobile");

            } elseif (!$this->getUser()->status) {
                $text = "لطفا قوانین را مطالعه فرمایید";
                $rule = Setting::where("key", "rule")->where("status",true)->first();

                $text .= $rule ? $rule->value : "";
                $keyboard[0][0] = ['text' => "قوانین را خواندم و آنها را پذیرفتم"];
                TelegramServices::menu($this->telegram, $keyboard, $this->getUser(), $text);
                cache()->forget($this->getKeyCache() . $this->getUserId());
            }
        }


    }

    public function startMessage()
    {
        $this->getUser()->change_menu = true;
        $this->getUser()->update();
        $keyboard_menu = $this->setMenu();
        $this->message_menu = "خوش آمدید، از این لحظه منو شما فعال شد";
        $this->menu($keyboard_menu, $this->getUser()->status, $this->getUser());
    }

    public function ruleAccept()
    {
        $this->getUser()->update(["accept_rule" => now()->format("Y-m-d H:i"),"status"=>true]);
        $text = "کاربر ";
        $text .= $this->getUser()->fullName;
        $text .= " به سیستم ";
        $text .= env("APP_NAME");
        $text .= " خوش آمدید .";
        $text .= "\n";
        $text .= "در صورت داشتن معرف کد معرف خود را وارد کنید ";
        $text .= "\n";
        $text .= "در غیر اینصورت دکمه شروع بازی را کلیک کنید تا منو بازی برای شما فعال گردد";
        cache()->set($this->getKeyCache() . $this->getUserId(), "pending_agent");
        $keyboard[0] = [
            ['text' => "شروع بازی\xF0\x9F\x8E\xB0"],
        ];
        $response = TelegramServices::menu($this->telegram, $keyboard, $this->getUser(), $text);
    }

    public function addAgent()
    {
        $agent = $this->getMessage();
        $agent_player = UserTelegram::where("telegram_id",$agent)->first();
        if($agent_player){
            $this->getUser()->agent_id = $agent_player->id;
            $this->getUser()->update();
            $text = "کاربر ";
            $text .= $agent_player->fullName;
            $text .= " به عنوان معرف شما ثبت گردید";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            $this->startMessage();
        }else{
            $text = "کد معرف وارد شده نامعتبر می باشد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);

        }
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
//        $message_id = cache()->get("sendTypeCharging_" .  $this->getUserId());
        $message_id = cache()->get("increase_in_inventory_" .  $this->getUserId());
        $text = "📌 جهت افزایش اعتبار کیف پول مبلغ مورد نظر را به تتر ارسال کنید";
        $keyboard = [];
        $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        cache()->forget("increase_in_inventory_" .  $this->getUserId());
        cache()->set("ChargingUsdt_" .  $this->getUserId(), $message_id);
        cache()->set($this->getKeyCache() . $this->getUserId(), "charging_usdt" );
    }

    public function withdrawalUsdt()
    {
//        $message_id = cache()->get("sendTypeCharging_" .  $this->getUserId());
        $message_id = cache()->get("increase_in_inventory_" .  $this->getUserId());
        $text = "  📌 جهت  برداشت از کیف پول مبلغ مورد نظر را به تتر ارسال کنید مبلغ کارمزد انتقال از کیف پول شما کم می شود";
        $keyboard = [];
        $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        cache()->forget("increase_in_inventory_" .  $this->getUserId());
        cache()->set($this->getKeyCache() . $this->getUserId(), "withdrawal_usdt_" );
    }
    public function checkGiveUsdt()
    {
        $wallet_usdt = data_get($this->getUser(), 'walletsUsdt');
        $wallet_usdt_give = data_get($this->getUser(), 'walletsUsdtWithdraw');
        $usdt  = ($wallet_usdt->sum("amount") - $wallet_usdt_give->sum("amount"))-1;
        $price = $this->getMessageCache();
        if(!is_numeric($price)){
            $text = "⚠️مبلغ وارد شد فقط عدد باشد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            return false;
        }
        if($usdt < $price){
            $text = "⚠️مبلغ وارد شد از کیف پول شما بیشتر می باشد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            return false;
        }
        $text = "📌 لطفا دقت فرمایید  کیف پول USDT در شبکه TRC20 را وارد فرمایید ";
        $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
        cache()->set($this->getKeyCache() . $this->getUserId(), "get_withdrawal_usdt_" );
    }
    public function checkWalletUsdt()
    {
        $wallet_usdt = data_get($this->getUser(), 'walletsUsdt');
        $wallet_usdt_give = data_get($this->getUser(), 'walletsUsdtWithdraw');
        $usdt  = ($wallet_usdt->sum("amount") - $wallet_usdt_give->sum("amount"))-1;
        $price = $this->getMessageCache();
        if(!is_numeric($price)){
            $text = "⚠️مبلغ وارد شد فقط عدد باشد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            return false;
        }
        if($usdt < $price){
            $text = "⚠️مبلغ وارد شد از کیف پول شما بیشتر می باشد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
            return false;
        }
        $text = "📌 لطفا دقت فرمایید  کیف پول USDT در شبکه TRC20 را وارد فرمایید ";
        $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
        cache()->set($this->getKeyCache() . $this->getUserId(), "get_withdrawal_usdt_" );
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


        $transaction = Transaction::create([
            "user_id"=>$this->getUser()->id,
            "payment_method"=>"wallet",
            "amount"=>$amount,
            "status"=>Transaction::STATUS_PENDING,
        ]);
        $keyboard[0] = [
            ['text' => "✅ پرداخت کردم ارسال رسید", 'callback_data' => "get_payment_receipt_" . $this->getUser()->id."_".$transaction->id],
        ];
        $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);
        cache()->set("checkPayUsdt_" .  $this->getUserId(), $message_id);
        cache()->forget($this->getKeyCache() . $this->getUserId());
    }

    public function pendingSendFile()
    {
        $array = str_replace('get_payment_receipt_', '', $this->getData());

        $info = explode("_", $array);
        $user_id = data_get($info, 0);
        $transaction_id = data_get($info, 1);
        $text = "👈 کاربر گرامی :"."\n";
        $text.="🏷 لطفا عکس رسید واریزی ارسال کنید تا حساب شما شارژ شود ، از ارسال رسید فیک خودداری کنید."."\n";
        $text .= "✅ تایید رسید واریزی 5 دقیقه الی 12 ساعت.";
        $keyboard = [];
        $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);
        cache()->set($this->getKeyCache() . $this->getUserId(), "charging_usdt_getFile_".$user_id."_".$transaction_id );

    }
    public function getPaymentReceipt()
    {
        $array = str_replace('charging_usdt_getFile_', '', $this->getMessageCache());

        $info = explode("_", $array);
        $user_id = data_get($info, 0);
        $transaction_id = data_get($info, 1);
        $token = data_get($this->bot,"token");
        $apiURL = "https://api.telegram.org/bot".$token;
        $chat_id = data_get($this->getData(),"chat.id");
        $keyboard = [];

        $transaction = Transaction::find($transaction_id);
        if($transaction) {
            $paths = [];
            collect($this->getPhoto())->each(function ($image) use ($apiURL, $chat_id, $token,&$paths) {
                $file_id = data_get($image, "file_id");

                // دریافت اطلاعات فایل
                logger("$apiURL/getFile?file_id=$file_id", [$file_id]);
                $file_info = file_get_contents("$apiURL/getFile?file_id=$file_id");
                $file_info = json_decode($file_info, true);

                logger("file_info", [$file_info]);
                if (isset($file_info["result"]["file_path"])) {
                    $file_path = $file_info["result"]["file_path"];
                    $file_url = "https://api.telegram.org/file/bot$token/$file_path";
                    // ارسال لینک عکس به کاربر
                    $contents = file_get_contents($file_url);
                    $filename = $file_id . "_" . basename(parse_url($file_url, PHP_URL_PATH)); // خروجی: sample.pdf
                    $paths[] = "telegram/{$this->getUserId()}/{$filename}";
                    Storage::disk('public')->put("telegram/{$this->getUserId()}/{$filename}", $contents);

                }
            });

            $text = "✅ رسید شما ارسال شد لطفا صبور باشید تا بررسی شود";
            $transaction->description = $this->getPhoto();
            $transaction->data = $paths;
            $transaction->status = Transaction::STATUS_PENDING_ACCEPT_RECEIPT;
            $transaction->save();
            dispatch(new SendMessageAccountingBot($transaction->id));
        }else{
            $text = "❌زمان ارسال رسید شما به پایان رسید لطفا مجداد فرایند را انجام دهید";

        }
        $message_id = $this->getTelegramServices()->MessageReplyMarkup($this->telegram, $this->getUserId(), $text, $keyboard);

        cache()->forget($this->getKeyCache() . $this->getUserId());
    }


    public function gameTest()
    {
        $this->card(Game::TYPE_TEST);

    }
    public function GameRial()
    {
        $this->card(Game::TYPE_RIAL);
    }
    public function gameUsdt()
    {
        $data = str_replace("get_card_","",$this->getData());
        $array = explode("_", $data);
        $this->card(Game::TYPE_USDT,data_get($array,0),data_get($array,1));
    }

    public function listGame()
    {
        $message_id = cache()->get("buy_game_" .  $this->getUserId());
        $text = "📌 کاربر عزیز تعداد افراد حاضر در هر اتاق که حداکثر 48 ";
        $text .= " \xF0\x9F\x8E\xAB	";
        $text .="  بلیط میباشد، در مبلغ هر گروه ضرب میشود و پس از کسر 10% کارمزد به حساب کاربری فرد برنده(احتمال برنده شدن چند نفر می باشد) افزوده میشود.";
        $text .= "\n";
        $text .= "یکی گروه های زیر را انتخاب کنید";
        $games =  Game::where("status",Game::STATUS_WAITING_PLAYER)->where("type",Game::TYPE_USDT)->get();
        $keyboard = [];
        $m = 0;
        $k = 0;
        foreach ($games as $i=>$game) {
            $price = data_get($game,"price")?:"رایگان";
            $keyboard[$i][$m] = [
                'text' => " بازی $price تتری ",
                'callback_data' => "request_get_card_" . $game->id,
            ];
        }
        $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        cache()->set("buy_game_" .  $this->getUserId(), $message_id);

    }

    public function listCard($type)
    {
        $message_id = cache()->get("buy_game_" .  $this->getUserId());
        $wallet_usdt = data_get($this->getUser(), 'walletsUsdt')->sum("amount");

        $game_id  = (int)str_replace("request_get_card_","",$this->getData());
        $text = "📌 کاربر عزیز یکی از کارت های ";
        $text .= " \xF0\x9F\x8E\xAB	";
        $text .=" باقیمانده زیر را انتخاب کنید :";
        $text .= "\n";
        $text .= "\xE2\x80\xBC	احتمال دارد تا کلیک شما کاربر دیگری کارت دریافت کنند";
        $game =  Game::where("id",Game::STATUS_WAITING_PLAYER)->where("type",$type)
            ->with(["cards" => function ($query) {
                $query->whereNull("player_id");
            }]);
        if($game_id)
            $game = $game->find($game_id);
        else
            $game = $game->first();
        logger("gamee",[$game,$game_id,$type]);
        if($game) {
            if($wallet_usdt && $wallet_usdt > data_get($game,"price")) {
                $keyboard = [];
                $m = 0;
                $k = 0;
                foreach ($game->cards as $i => $card) {
                    $keyboard[$m][$k] = [
                        'text' => data_get($card, "title"),
                        'callback_data' => "get_card_" . $game->id . "_" . $card->id,
                    ];
                    $k++;
                    if($i%5 ==0)
                    {
                        $m++;
                        $k=0;
                    }
                }
                $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
                cache()->set("buy_game_" . $this->getUserId(), $message_id);
            }else{
                $text = "موجود کاربری شما کافی نمی باشد";
                $keyboard =[];
                $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
            }
        }else{
            $text = "کارت بازی تمام شده است";
            $keyboard =[];
            $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        }


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

    /**
     * @return void
     * @throws \Telegram\Bot\Exceptions\TelegramSDKException
     */
    public function card($type,$game_id=null,$id=null): void
    {
        $message_id = cache()->get("buy_game_" .  $this->getUserId());
        $game = Game::where("type", $type)->with(["cards" => function ($query) use ($id) {
            $query->whereNull("player_id");
            if($id)
                $query->where("id",$id);
        }])->where("status", Game::STATUS_WAITING_PLAYER)
            ->lockForUpdate();
        if($game_id)
            $game = $game->find($game_id);
        else
            $game = $game->first();
        logger("game",[$type,$game,$game_id]);

        if ($game) {

            if ($game->cards->count()) {
                $card = $id?$game->cards->where("id",$id)->first():$game->cards->random(1)->first();
                if($card) {
                    $path_report = storage_path(data_get($card, "file"));
                    $name_file = convertNumber(toJalali(now(), "m_d")) . slug_seo(data_get($this->getUser(), "fullName"), "_") . "_" . data_get($game, "id") . "_" . data_get($card, "id");
                    $text = "بلیط بازی خود تا زمان قرعه کشی نزد خود نگه دارید";
                    $game->remaining_card -=1;
                    if( $game->remaining_card >= 0) {
                        $card->player_id = $this->getUser()->id;
                        $card->update();

                        $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);
                        $response = $this->telegram->sendDocument([
                            'chat_id' => $this->getUserId(),
                            'document' => InputFile::create($path_report, $name_file . ".pdf")
                        ]);
                        $game->update();
                    }
                }else{
                    if($id){
                        $text = "کارت انتخابی شما توسط فرد دیگری دریافت شد";
                        $keyboard =[];
                        $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
                    }else{
                        $text = "کارت بازی تمام شده است";
                        $keyboard =[];
                        $message_id = $this->getTelegramServices()->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
                    }
                }

            } else {
                $text = "کارت این بازی تمام شد منتظر بازی جدید باشید";
                $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);

            }
        } else {
            $text = "در حال حاضر بازی فعال نمی باشد";
            $this->telegram->sendMessage(['chat_id' => $this->getUserId(), 'text' => $text]);

        }
    }

}
