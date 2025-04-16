<?php

namespace App\Console\Commands;

use Apachish\Dabelna\App\Models\Bot;
use Illuminate\Console\Command;

class SendMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-message';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bot = Bot::where("title","DabernaGameBot")->first();
        if(!$bot){
            $token = $bot->token;
            $chat_id = $bot->chanel_id;
            file_get_contents("https://api.telegram.org/bot$token/sendAnimation?chat_id=$chat_id&animation=https://media.giphy.com/media/111ebonMs90YLu/giphy.gif&caption=🎉 ما 10 هزار نفری شدیم!");

        }

    }
}
