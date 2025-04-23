<?php

namespace App\Console\Commands;

use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\Game;
use Apachish\Dabelna\App\Services\GameService;
use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    public  $message_cache = "get_withdrawal_usdt_";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $game_server = new GameService();
        $game = Game::with("winners")->where("id", 5)->first();
        $game_server->sendWinner($game);
//        if (str_contains($this->message_cache, "charging_usdt_getFile"))
//            echo "charging_usdt_getFile";
//        elseif (str_contains($this->message_cache, "charging_usdt"))
//            echo "charging_usdt";



//        elseif (str_contains($this->message_cache, "withdrawal_usdt_"))
//            echo "withdrawal_usdt_";
//
//        elseif (str_contains($this->message_cache, "get_withdrawal_usdt_"))
//            echo "get_withdrawal_usdt_";

    }
}
