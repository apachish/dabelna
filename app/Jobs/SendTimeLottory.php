<?php

namespace App\Jobs;

use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Telegram\Bot\Api;

class SendTimeLottory implements ShouldQueue
{
    use Queueable;

    public $gmae_id;
    /**
     * Create a new job instance.
     */
    public function __construct($game_id)
    {
        $this->gmae_id = $game_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $game = Game::with("cards.player")->find($this->gmae_id);
        if($game && ata_get($game,"lottery_date")){
            $bot = Bot::where("title","DabernaGameBot")->first();
            if($bot){
                $telegram = new Api($bot->token);

            }
            $text  = "زمان برگزاری قرعه کشی بازی";
            $text  .= " ". $game->title." ";
            $text .= " :"."\n";
            $text .= toJalali(data_get($game,"lottery_date"));

            if(data_get($bot,"chanel_id"))
                $telegram->sendMessage(['chat_id' => data_get($bot,"chanel_id"), 'text' => $text]);
        }


    }
}
