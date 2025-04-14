<?php

namespace App\Jobs;

use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\Message;
use Apachish\Dabelna\App\Models\Transaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class SendMessageAccountingBot implements ShouldQueue
{
    use Queueable;
    private $order_id;
    private $send;
    /**
     * Create a new job instance.
     */
    public function __construct($order_id)
    {
        $this->order_id = $order_id;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot_accounting = Bot::where("title", "botAccounting")->with("accessBot")->first();
        if ($bot_accounting) {
            try {
                $telegram_accounting = new Api($bot_accounting->token);
                $order = Transaction::with("user")->find($this->order_id);
                if ($order) {
                    $admins = $bot_accounting->accessBot;
                    $message = " مبلغ ".$order->price." توسط کاربر ".data_get($order,'user.fullName')." واریز شد.";
                    foreach ($admins as $admin) {

                        $this->send = Message::create([
                            "telegram_id" => $admin->user_id,
                            "bot_id" => $bot_accounting->id,
                            "status" => Message::STATUS_PENDING,
                            "text" => $message,
                            "request_id" => $this->order_id
                        ]);
                        $file = json_decode(data_get($order, "data"));
                        $path_report = data_get($file,'0');
                        $name_file = "receipt_".data_get($order,'user.telegram_id')."_".data_get($order,'id').".jpg";
                        $response = $telegram_accounting->sendDocument([
                            'chat_id' => $admin->user_id,
                            'document' => InputFile::create($path_report, $name_file)
                        ]);
                        $send_accounting = $telegram_accounting->sendMessage(
                            [
                                'chat_id' => $admin->user_id,
                                'text' => $message,
                                'parse_mode' => 'MarkdownV2'
                            ]);
                    }
                }

            } catch (\Exception $exception) {
                logger("get error", [
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getCode(),
                    $exception->getTrace(),
                    $exception->getFile()
                ]);
                $this->send->error_text = $exception->getMessage();
                $this->send->status = Message::STATUS_FAILED;
                $this->send->update();
            }
        }
    }
}
