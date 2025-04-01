<?php

namespace App\Services;


use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\Setting;
use Apachish\Dabelna\App\Models\TextTelegram;
use Apachish\Dabelna\App\Models\UserTelegram;
use Telegram\Bot\Api;

class TextServices
{

    private $type;
    private $price;

    private $number_order;
    private $description;

    private $type_message;

    protected $message;

    private $message_cache;

    public $data;

    public $bot;

    public $bot_admin;

    public $telegram;

    public $message_menu = "خوش آمدید";

    private $pattern;

    public $telegram_services;

    private $token;

    protected $update;

    private $user;

    private $user_id;

    private $key_cache;
    private $contact;
    private $word;

    public function __construct($token)
    {
        $this->token = $token;

        $this->bot = cache()->remember("token_" . $token, now()->addDay(), function () {
            return Bot::where('token', $this->token)->where("status",true)->first();
        });
        $this->telegram = new Api($this->bot->token);

        $this->telegram_services = new TelegramServices($this->token);
        $this->update = $this->telegram->getWebhookUpdate();
    }

    /**
     * @return Api
     */
    public function getTelegram(): Api
    {
        return $this->telegram;
    }

    /**
     * @return TelegramServices
     */
    public function getTelegramServices(): TelegramServices
    {
        return $this->telegram_services;
    }

    /**
     * @return mixed
     */
    public function getKeyCache()
    {
        return $this->key_cache;
    }

    /**
     * @param mixed $key_cache
     */
    public function setKeyCache($key_cache): void
    {
        $this->key_cache = $key_cache;
    }

    /**
     * @return mixed
     *
     *  get type message
     *
     * // دریافت دستور ارسال شده توسط کار
     */
    public function getTypeMessage()
    {
        return $this->type_message;
    }

    /**
     * @param mixed $type_message
     */
    public function setTypeMessage(): void
    {
        if (isset($this->update["my_chat_member"]))
            $type = "my_chat_member";
        elseif (isset($this->update['callback_query']))
            $type = "callback_query";
        else
            $type = "message";

        $this->type_message = $type;
    }


    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param mixed $user_id
     */
    public function setUserId(): void
    {
        $this->user_id = data_get($this->update, $this->type_message . '.from.id');;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }



    /**
     * @param mixed $user
     */
    public function setUser(): void
    {
        $user_telegram = UserTelegram::where("telegram_id", $this->user_id)->withTrashed()->first();
        if ($user_telegram == null && $this->user_id) {
            $update = $this->update;
            $type = $this->type_message;
            $data = array_filter([
                "telegram_id" => $this->user_id,
                "is_bot" => data_get($update, $type . '.from.is_bot'),
                "first_name" => data_get($update, $type . '.from.first_name'),
                "last_name" => data_get($update, $type . '.from.last_name'),
                "mobile" => data_get($update, $type . '.mobile'),
                "username" => data_get($update, $type . '.from.username'),
                "language_code" => data_get($update, $type . '.from.language_code'),
                "role" => "user",
                "status" => false,
            ]);
            if ($update && $data) {
                $user_telegram = UserTelegram::updateOrCreate(["telegram_id" => $this->user_id], $data);
                $this->sendMessageNewUser();
            }
            $this->user = $user_telegram;
        } elseif (data_get($user_telegram, "deleted_at")) {
            cache()->forget("keyword_menu" . $this->getKeyCache() . $user_telegram->telegram_id);
            $message = 'منو کاربری شما تغییر  یافت';
            if ($this->getMessage())
                $message = "متن شما نامعتبر می باشد";
            $this->telegram_services->deleteKeyboard($user_telegram->telegram_id, $message);
        } else
            $this->user = $user_telegram;
    }

    private $message_id = null;

    public function getMessageId()
    {
        return $this->message_id;
    }

    /*
  * set message id
  */
    public function setMessageId(): void
    {
        if (isset($this->update[$this->type_message]['message_id']))
            $this->message_id = $this->update[$this->type_message]['message_id']; // چت‌آیدی کاربر
        if (isset($this->update[$this->type_message]['message']['message_id']))
            $this->message_id = $this->update[$this->type_message]['message']['message_id']; // چت‌آیدی کاربر

    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage(): void
    {
        $this->message = isset($this->update['message']['text']) ? cleanInput($this->convertNumber($this->update['message']['text'])) : null;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData(): void
    {
        $this->data = data_get($this->update, $this->type_message . ".data");
    }

    /**
     * @return mixed
     */
    public function getMessageCache()
    {
        return $this->message_cache;
    }

    /**
     * @param mixed $message_cache
     */
    public function setMessageCache(): void
    {
        $this->message_cache = cache()->get($this->key_cache . $this->user_id);

        // data_get($cache_data, "title")
    }

    /**
     * @return mixed
     */
    public function getBotAdmin()
    {
        return new Api($this->bot_admin->token);;
    }

    /**
     * @param mixed $bot_admin
     */
    public function setBotAdmin(): void
    {
        $bot = Bot::where("title", "botManage")->first();
        $this->bot_admin = $bot ?: null;
    }


    public function removeMessageCache(): void
    {
        $this->message_cache = null;

        // data_get($cache_data, "title")
    }
    /**
     * @return mixed

    /**
     * @return mixed
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param mixed $contact
     */
    public function setContact(): void
    {
        if (isset($this->update['message']['contact']["phone_number"])) {
            $this->contact = $this->convertNumber($this->update['message']['contact']["phone_number"]);
            if (!str_contains($this->contact, "+"))
                $this->contact = "+" . $this->contact;
        }
    }

    public function checkText()
    {

        $accept = [
            "/start",
            "start",
            "/help",
            "منو",
            "\xF0\x9F\x8E\xABخرید بلیط",
            "\xF0\x9F\x92\xB3کیف پول",
            "پروفایل\xF0\x9F\x91\xA4",
            "\xF0\x9F\x93\x9Aقوانین",
            "راهنما\xE2\x81\x89",
            "قوانین را خواندم و آنها را پذیرفتم",
            "راهنما را خواندم و یاد گرفتم",
        ];
        if (in_array($this->message, $accept))
            return true;
        if ($this->contact)
            return true;

        return false;
    }

    public function checkData()
    {
        if (str_contains($this->data, "rule_accept"))
            return true;
        return false;
    }

    public function checkCache()
    {
        if (str_contains($this->message_cache, "add_customer_"))
            return true;
        elseif (str_contains($this->message_cache, "add_fullName"))
            return true;
        elseif (str_contains($this->message_cache, "add_mobile"))
            return true;
        elseif (str_contains($this->message_cache, "pending_accept"))
            return true;
        return false;
    }

    public function actionByMessage()
    {
        if ($this->getMessage() == "قوانین را خواندم و آنها را پذیرفتم") {
            $this->ruleAccept();
            return true;
        }
        if ($this->getMessage() == "راهنما را خواندم و یاد گرفتم") {
            $this->helpAccept();
            return true;
        }
        if (!$this->user->fullName) {
            $text = " لطفا نام و نام خانوادگی خود را وارد نمایید";
            cache()->set($this->key_cache . $this->user_id, "add_fullName");
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => $text]);
            return true;
        } elseif (!$this->user->mobile) {
            $text = "لطفا شماره خود را به اشتراک بگذارید";
            $this->telegram_services->sendRequestContactButton($this->getUserId(), $text);
            cache()->set($this->key_cache . $this->user_id, "rule_accept");
            return true;

        } elseif (!$this->user->accept_rule) {
            $text = "لطفا قوانین را مطالعه فرمایید";
            $rule = Setting::where("key", "rule")->first();

            $text .= $rule ? $rule->value : "";
            $keyboard[0][0] = ['text' => "قوانین را خواندم و آنها را پذیرفتم"];
            TelegramServices::menu($this->telegram, $keyboard, $this->getUser(), $text);
            cache()->forget($this->getKeyCache() . $this->getUserId());
//            $menu = $this->getTelegramServices()->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
//            cache()->set("rule_accept". $this->user_id,$menu);
            return true;

        }


        /*
       * check message
       */

        cache()->forget($this->key_cache . $this->user_id);

        if (!$this->checkText())
            return $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);

        TextTelegram::create([
            "update_id" => data_get($this->update, 'update_id'),
            "message_id" => data_get($this->update, 'message.message_id'),
            "user_telegram_id" => $this->user->id,
            "text" => data_get($this->update, 'message.text'),
            "data" => json_encode($this->update)
        ]);
        if ($this->pattern && preg_match($this->pattern, $this->message))
            return $this->checkWord();

        switch ($this->message) {
            case "منو":
                $keyboard_menu = $this->setMenu();
                $response = TelegramServices::menu($this->telegram, $keyboard_menu, $this->getUser(), "بازگشت به منو اصلی");
                break;
            case "\xF0\x9F\x8E\xABخرید بلیط":
            case "\xF0\x9F\x92\xB3کیف پول":
            case "پروفایل\xF0\x9F\x91\xA4":
            case "\xF0\x9F\x93\x9Aقوانین":
            case "راهنما\xE2\x81\x89":
                $this->getAction();
                break;
            case  "/start":
            case  "start":
                if ($this->user && $this->user->role)
                    $this->user->change_menu = true;
                break;
            default:
                $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
                break;
        }


    }

    public function actionByData()
    {
        /*
        * check data
        */
        if (!$this->checkData())
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
        elseif (str_contains($this->data, "rule_accept"))
            $this->ruleAccept();
    }

    public function actionByCache()
    {
        /*
        * check data
        */
        if (!$this->checkCache())
            $this->telegram->sendMessage(['chat_id' => $this->user_id, 'text' => 'متن نا معتبر می باشد']);
        elseif (str_contains($this->message_cache, "add_customer_mobile"))
            $this->addCustomer();
        elseif (str_contains($this->message_cache, "add_mobile"))
            $this->addMobile();
        elseif (str_contains($this->message_cache, "add_fullName"))
            $this->addFullName();
        elseif (str_contains($this->message_cache, "pending_accept")) {
            if ($this->user->status)
                cache()->forget($this->getKeyCache() . $this->user->id);
            else
                $this->pendingAccept();

        }
    }

    private function getAction()
    {
        switch ($this->message) {
            case "\xF0\x9F\x8E\xABخرید بلیط":
                $text = "موبایل مشتری خود را وارد کنید با کد کشور بدون صفر مثل ";
                $text .= "\n\n";
                $text .= '+989120001122';
                $text .= "\n\n";
                $text .= '+11234567890';
                $text .= "\n\n";
                $text .= '+442071838750';
                $text .= "\n\n";
                $this->telegram_services->sendMessage($this->user_id, $text);
                break;
            case "\xF0\x9F\x93\x88معاملات باز":
                cache()->forget("trade_open_" . $this->user_id);
                $worker = UserTelegram::where("agent_id", $this->getUser()->id)->get();
                $keyboard = [];
                $i = 0;
                $keyboard[$i++] = [
                    ['text' => "خودم", 'callback_data' => "trade_open_" . $this->getUser()->id],
                ];
                $worker->each(function ($row) use (&$i, &$keyboard) {
                    $keyboard[$i++] = [
                        ['text' => $row->fullName, 'callback_data' => "trade_open_" . $row->id],
                    ];
                });
                $message_id = $this->telegram_services->MessageReplyMarkup($this->telegram, $this->user_id, "شخص مورد نظر را انتخاب کنید", $keyboard);
                cache()->set("trade_open_" . $this->user_id, $message_id);
                break;

            case "\xF0\x9F\x93\x8Bلیست همکاران":
                $this->listWorker();
                break;

            case "\xF0\x9F\x93\x9Aقوانین":
                $help = Setting::where("key", "rule")->first();
                $this->telegram_services->sendMessage($this->user_id, $help->value);
                break;

            case "راهنما\xE2\x81\x89":
                $help = Setting::where("key", "help")->first();
                $this->telegram_services->sendMessage($this->user_id, $help->value);

                break;
            case "\xE2\x8C\x9Bمدت اشتراک":
//                $help = Setting::where("key", "membership")->first();
                if ($this->getUser()->memberShip) {
                    $message = "تاریخ پایان اشتراک شما:";
                    $message .= "\n\n";
                    $message .= toJalali($this->getUser()->memberShip->expiration_date, "Y/d/m");
                } else {
                    $message = "خدمات تا اطلاع ثانوی 😍 رایگان 😍 میباشد";
                }
                $this->telegram_services->sendMessage($this->user_id, $message);

                break;
            case "\xF0\x9F\x92\xB3حق اشتراک":
                $help = Setting::where("key", "membership")->first();
                if ($help)
                    $this->telegram_services->sendMessage($this->user_id, $help->value);

                break;

            case "\xE2\x9A\xA0\xE2\x9D\x8Cغیرفعال سازی تایید دو مرحله ای":
                $this->user->verify_two = null;
                $this->user->change_menu = true;
                $this->user->update();
                $keyboard_menu = $this->setMenu();
                $this->message_menu = "تایید دو مرحله ای غیر فعال شد";
                $this->menu($keyboard_menu, $this->user->status, $this->user);
                break;

            case "\xE2\x9C\x8Cفعال سازی دو مرحله ای":
                $this->user->verify_two = now();
                $this->user->change_menu = true;

                $this->user->update();
                $keyboard_menu = $this->setMenu();
                $this->message_menu = "تایید دو مرحله ای  فعال شد";
                $this->menu($keyboard_menu, $this->user->status, $this->user);
                break;

            case  "\xE2\x9D\x8Cغیر فعال فوری":
                if (!cache()->get("disable_immediately_" . $this->getUserId())) {
                    cache()->set("disable_immediately_" . $this->getUserId(), 1, now()->addSecond(5));
                    return true;
                }
                cache()->forget("disable_immediately_" . $this->getUserId());
                try {
                    // خارج کردن کاربر از کانال
                    $response = $this->telegram->kickChatMember(
                        [
                            'chat_id' => $this->bot->chanel_id,
                            'user_id' => $this->user->telegram_id,
                        ]);

                    if ($response) {
                        logger("User has been successfully removed from the channel.");
                    } else {
                        logger("Failed to remove user from the channel.");
                    }
                } catch (\Exception $e) {
                    logger("Error: " . $e->getMessage());
                }
                $this->user->status = false;
                $this->user->change_menu = true;
                $this->user->update();
                $this->telegram_services->deleteKeyboard($this->user->id, "مواظب خودت باش");
                $this->user->delete();
                cache()->forget("keyword_menu" . $this->getKeyCache() . $this->user->id);
                $this->menu([], false);
                break;
            default:
                return false;
        }

    }

    public function setMenu()
    {
        $i = 0;
        $keyboard_menu[$i++] = [
            ['text' => "\xF0\x9F\x8E\xABخرید بلیط"]
        ];
        $keyboard_menu[$i++] = [
            ['text' => "\xF0\x9F\x92\xB3کیف پول"],
            ['text' => "پروفایل\xF0\x9F\x91\xA4"],
        ];
        $keyboard_menu[$i++] = [
            ['text' => "\xF0\x9F\x93\x9Aقوانین"],
            ['text' => "راهنما\xE2\x81\x89"],
        ];
        return $keyboard_menu;
    }

    private function sendMessageNewUser(): void
    {
        $text = 'خوش آمدید! به ' . env("APP_NAME") . '.';
        $this->telegram_services->sendMessage($this->getUserId(), $text);


    }


    public function menu($keyboard, $show, $user = null)
    {

        $user = $user ? $user : $this->getUser();
        if ($show) {
            if ($user && $user->change_menu) {
                {
//                    $this->telegram_services->deleteKeyboard($user->id);
                    $user->change_menu = false;
                    $user->update();
                }

                $response = TelegramServices::menu($this->telegram, $keyboard, $user, $this->message_menu);
            }

        } elseif ($user->change_menu) {
            $this->telegram_services->deleteKeyboard($user->telegram_id, $this->message_menu);
            $user->change_menu = false;
            $user->update();
        }

    }


    protected function iranMobile($value)
    {

        if ((bool)preg_match('/^(((98)|(\+98)|(0098)|0)(9){1}[0-9]{9})+$/', $value) || (bool)preg_match('/^(9){1}[0-9]{9}+$/', $value))
            return true;

        return false;
    }

    protected function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }

}
