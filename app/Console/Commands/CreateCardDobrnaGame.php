<?php

namespace App\Console\Commands;

use Apachish\Dabelna\App\Models\Game;
use App\Services\DobrnaCardGenerator;
use App\Services\DobrnaGame;
use Illuminate\Console\Command;

class CreateCardDobrnaGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-card-dobrna-game';

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
        $games = Game::where("status",Game::STATUS_WAITING)->get();
        foreach ($games as $key => $value) {
            $data = $value->toArray();
            $data["status"] = Game::STATUS_WAITING_PLAYER;

           $game =  Game::create([
                "lottery_date"=>now()->addHour(1),
                "num_player"=>$value,
               "remaining_card"=>$value
            ]);
            $dobrna = new DobrnaGame(data_get($game,"num_player"));
            $path = "app/public/games/".Game::$types[data_get($game,"type")]."/".data_get($game,"id")."/";
            makeDirectoryStorage($path);
            $dobrna->generatePDF($path,data_get($game,"id"),$key);
        }
    }
}
