<?php

namespace App\Services;



use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\UserTelegram;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;


class ActionAccountingServices extends TextServices
{
    public $keyboard_menu = [
        [
            ["text" => "ارسال پیام برای همه\xF0\x9F\x92\xAC"],
            ['text' => "تراکنش در انتظار تایید\xF0\x9F\x92\xB3"],
            ["text" => "\xF0\x9F\x94\x8Dجستجو"],
            ["text" => "لیست کاربران\xF0\x9F\x91\xA4"],
        ],
    ];

    public function __construct($token)
    {
        parent::__construct($token);
    }


    public function checkMessage()
    {

        $access_text = [
            "/start",
            "ارسال پیام برای همه\xF0\x9F\x92\xAC",
            "تراکنش در انتظار تایید\xF0\x9F\x92\xB3",
            "\xF0\x9F\x94\x8Dجستجو",
            "لیست کاربران\xF0\x9F\x91\xA4",
        ];
        if (in_array($this->message, $access_text))
            return true;

        return false;
    }

    public function actionData()
    {
        cache()->forget($this->getKeyCache() . $this->getUserId());
        $this->removeMessageCache();

        if (str_contains($this->getData(), "pre_"))
            $this->pre();
        elseif (str_contains($this->getData(), "next_"))
            $this->next();
        elseif (str_contains($this->getData(), "get_message_user_"))
            $this->getMessageUser($this);
        elseif (str_contains($this->getData(), "add_chanel_"))
            $this->addChanel($this);
        elseif (str_contains($this->getData(), "edit_name_"))
            $this->getEditName($this);
        elseif (str_contains($this->getData(), "null"))
            return null;

    }

    private function sendBotCustomer($chat_id, array|string $message): void
    {
        $bot_customer = Bot::where("title", "botCustomer")->first();
        if ($bot_customer) {
            try {
                $telegram_customer = new Api($bot_customer->token);
                $telegram_customer->sendMessage(
                    [
                        'chat_id' => $chat_id,
                        'text' => $message,
                    ]
                );
            } catch (\Exception $exception) {
                logger("get error", [
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getCode(),
                    $exception->getTrace(),
                    $exception->getFile()
                ]);
            }
        }
    }


    public function actionText()
    {
        cache()->forget($this->getKeyCache() . $this->getUserId());
        switch ($this->getMessage()) {
            case "ارسال پیام برای همه\xF0\x9F\x92\xAC":
                $this->getMessageGroup($this);
                break;
            case "تراکنش در انتظار تایید\xF0\x9F\x92\xB3":
                break;
            case "لیست کاربران\xF0\x9F\x91\xA4":
                $this->listUser();
                break;

            case "\xF0\x9F\x94\x8Dجستجو":
                $message = "\n\n";
                $message .= "شماره موبایل یا نام شخص را وارد کنید";
                $this->getTelegramServices()->sendMessage($this->getUserId(), $message);
                cache()->set($this->getKeyCache() . $this->getUserId(), "find_user");
                break;
            case  "/start":
                $this->getTelegramServices()->sendMessage($this->getUserId(), "خوش آمدید");
                break;
        }
    }

    public function actionTextCache()
    {
        $key_case = $this->getMessageCache();
        if (str_contains($this->getMessageCache(), "edit_name_done_"))
            $key_case = "edit_name_done_";
        elseif (str_contains($this->getMessageCache(), "send_message_user_"))
            $key_case = "send_message_user_";

        switch ($key_case) {

            case "send_message_user_":
                $this->setMessageUser($this);
                break;
            case "send_message_group":
                $this->setMessageGroup($this);
                break;
            case "edit_name_done_":
                $this->setName($this);
                break;
            case "find_user":
                $this->findUser($this);
                break;
        }
    }



    public function setServiceTelgramUser(TelegramServices $service_telgram_user): void
    {
        $this->service_telgram_user = $service_telgram_user;
    }
    private function listUser( $page = 1, $message_id = null, $filter = null)
    {
        $users = UserTelegram::withTrashed()->with([ "walletsUsdtWithdraw", "walletsUsdt"])
        ;
        if ($filter) {
            $users->where(function ($query) use ($filter) {
                $query->where("fullName", "like", "%" . $filter . "%");
                $query->orWhere("mobile", "like", "%" . $filter . "%");
            });
        }
        $users = $users->simplePaginate(4, ['*'], 'page', $page);
        $page = $users->currentPage();
        $next = $users->nextPageUrl() ? (int)str_replace("?page=", "", strstr($users->nextPageUrl(), "?page=")) : null;
        $pre = $users->previousPageUrl() ? (int)str_replace("?page=", "", strstr($users->previousPageUrl(), "?page=")) : null;
        $keyboard = [];
        $i = 0;
        $text = "\n\nلیست  کاربران";
        $text .= "\n\n";
        $text .= "تعداد کاربران:".$users->count();
        $users->each(function ($user) use (&$keyboard, &$i, $page, $filter) {
            $text = $user->fullName ?: $user->first_name . " " . $user->last_name;
            $key_i = $user->role . "_" . $user->id . "_" . $page;
            if ($filter)
                $key_i .= "_" . $filter;

            $keyboard[$i++] = [
                ['text' => "  $text  ", 'callback_data' => "set_user_" . $key_i],
            ];
            $wallet_usdt = data_get($user, 'walletsUsdt');
            $wallet_usdt_give = data_get($user, 'walletsUsdtWithdraw');
            $usdt = 0;
            if ($wallet_usdt)
                $usdt = $wallet_usdt->sum("amount") - $wallet_usdt_give->sum("amount");
            $array = [
                ['text' => "\xE2\x9C\x8Fویرایش کاربر", 'callback_data' => 'edit_name_' . $key_i],
                ['text' => "\xF0\x9F\x92\xACپیام", 'callback_data' => 'send_message_' . $key_i],
                ['text' => "\xF0\x9F\x91\xA4".$user->children->count(), 'callback_data' => "null"],
                ['text' => "\xF0\x9F\x92\xB5".$usdt, 'callback_data' => "null"],
            ];
            if (!$this->telegram_services->checkMember(data_get($this->bot, "chanel_id"), $user->telegram_id))
                $array[] = ['text' => "\xE2\x9E\x95", 'callback_data' => 'add_chanel_' . $key_i];
            $keyboard[$i++] = $array;
            $array = [];




            $keyboard[$i++] = $array;
        });
        if ($pre)
            $keyboard[$i][] = ['text' => "قبلی", "callback_data" => "pre_" . $pre  . ($filter ? "_" . $filter : null)];
        if ($next)
            $keyboard[$i][] = ['text' => "بعدی", "callback_data" => "next_" . $next  . ($filter ? "_" . $filter : null)];

        if ($message_id)
           $this->telegram_services->editMessageTextAndInlineKeyboard($this->getUserId(), $message_id, $text, $keyboard);
        else {
           $this->telegram_services->menu_key = "menu_List_user_";
            $menu =$this->telegram_services->MessageReplyMarkup($this->getTelegram(), $this->getUserId(), $text, $keyboard);
        }
    }

    public function getEditName()
    {
        $data = str_replace('edit_name_', '', $this->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);

        $user_con = UserTelegram::find($id);

        if ($user_con) {
            $fullName = $user_con->fullName ?: $user_con->first_name . " " . $user_con->last_name;
            $message = " نام و نام خانوادگی :";
            $message .= $fullName;
            $message .= "\n\n";
            $message .= " نام و نام خانوادگی جدید وارد کنید ";
           $this->telegram_services->sendMessage($this->getUserId(), $message);
            cache()->set($this->getKeyCache() . $this->getUserId(), "edit_name_done_" . $data);
        }
    }
    public function setName()
    {
        $data = str_replace('edit_name_done_', '', $this->getMessageCache());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);

        $user_con = UserTelegram::find($id);

        if ($user_con) {
            $user_con->fullName = $this->getMessage();
            $user_con->update();
            $message = $user_con->fullName;
            $message .= "\n\n";
            $message .= "نام و نام خانوادگی بروزرسانی شد ";
           $this->telegram_services->sendMessage($this->getUserId(), $message);
        }
        $message_id = cache()->get("menu_List_user_" . $this->getUserId());
        $this->listUser( $page, $message_id, $filter);
        cache()->forget($this->getKeyCache() . $this->getUserId());
    }

    public function findUser()
    {
        $this->listUser( 1, null, $this->getMessage());

        cache()->forget($this->getKeyCache() . $this->getUserId());
    }

    public function getMessageGroup()
    {
        $message = "پیامی که می خواهید برای کاربران سیستم ارسال کنید وارد کنید";
       $this->telegram_services->sendMessage($this->getUserId(), $message);
        cache()->set($this->getKeyCache() . $this->getUserId(), "send_message_group");

    }

    public function setMessageGroup()
    {
        $users = UserTelegram::get();
        foreach ($users as $user) {
            try {
                $message_id = $this->telegram_services->sendMessage($user->telegram_id, $this->getMessage());
            } catch (\Exception $exception) {
                logger("message send admin " . $user->telegram_id . ":" . $exception->getMessage());
            }
        }
        cache()->forget($this->getKeyCache() . $this->getUserId());

    }

    public function getMessageUser()
    {
        $data = str_replace('get_message_user_', '', $this->getData());

        $array = explode("_", $data);
        $id = (int)data_get($array, 1);
        $user_con = UserTelegram::find($id);
        if ($user_con) {
            $message = "پیامی که می خواهید برای کاربر";
            $message .= "\n\n";
            $message .= $user_con->fullName;
            $message .= "ارسال کنید وارد کنید";
           $this->telegram_services->sendMessage($this->getUserId(), $message);
            cache()->set($this->getKeyCache() . $this->getUserId(), "send_message_user_" . $user_con->id);
        }

    }

    public function setMessageUser()
    {
        $id = str_replace('send_message_user_', '', $this->getMessageCache());

        $user = UserTelegram::find($id);
        if ($user) {
            try {
                $message_id = $this->telegram_services->sendMessage($user->telegram_id, $this->getMessage());
                cache()->forget($this->getKeyCache() . $this->getUserId());
                $message = "  پیام به کاربر  $user->fullName  ارسال شد  ";
               $this->telegram_services->sendMessage($this->getUserId(), $message);
            } catch (\Exception $exception) {
                logger("message send admin " . $user->telegram_id . ":" . $exception->getMessage());
            }
        } else {
           $this->telegram_services->sendMessage($this->getUserId(), "پیام ارسال نشد");

        }

    }

    public function pre()
    {
        $data = str_replace('pre_', '', $this->getData());
        $array = explode("_", $data);
        $page = (int)data_get($array, 0);
        $filter = data_get($array, 2, null);
        $message_id = cache()->get("menu_List_user_" . $this->getUserId());
        if ($message_id)
            $this->listUser( $page, $message_id, $filter);
    }

    public function next()
    {
        $data = str_replace('next_', '', $this->getData());
        $array = explode("_", $data);
        $page = (int)data_get($array, 0);
        $role = data_get($array, 1, null);
        $filter = data_get($array, 2, null);
        $message_id = cache()->get("menu_List_user_" . $this->getUserId());
        if ($message_id)
            $this->listUser( $page, $message_id, $filter);
    }
    public function addChanel()
    {
        $data = str_replace('add_chanel_', '', $this->getData());
        $array = explode("_", $data);
        $role = data_get($array, 0);
        $id = (int)data_get($array, 1);
        $page = (int)data_get($array, 2);
        $filter = data_get($array, 3, null);
        $user_con = UserTelegram::find($id);
        if ($user_con) {
            $response = $this->telegram->createChatInviteLink([
                'chat_id' => data_get($this->bot, "chanel_id"),
                'name' => Str::slug($user_con->fullName, "_"),
                'expire_date' => time() + 150, // لینک به مدت 24 ساعت معتبر است
                'member_limit' => 1, // تعداد اعضای جدیدی که با این لینک می‌توانند بپیوندند
            ]);

            $inviteLink = data_get($response, "invite_link");


            $this->telegram->sendMessage([
                'chat_id' => $this->getUserId(),
                'text' => "لینک دعوت کانال برای کاربر ارسال شد",
            ]);
            // ارسال لینک دعوت به کاربر
            $message_link = "لطفا با استفاده از لینک دعوت[فقط ۳ دقیقه معتبر می باشد] به کانال  " . env("APP_NAME") . " بپیوندید: ";
            $message_link .= "\n\n " . $inviteLink;
            $this->telegram_services->sendMessage($user_con->telegram_id, $message_link);
            $message_id = cache()->get("menu_List_user_" . $this->getUserId());
            $this->listUser( $page, $message_id, $filter);
        }
    }
}
