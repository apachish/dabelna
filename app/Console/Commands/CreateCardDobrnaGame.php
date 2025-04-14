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
        $games = Game::where("status",Game::STATUS_WAITING)->dosenthave("cards")->get();
        foreach ($games as $key => $game) {
            $data["status"] = Game::STATUS_WAITING_PLAYER;
            $dobrna = new DobrnaGame(data_get($game,"num_player"));
            $path = "app/public/games/".Game::$types[data_get($game,"type")]."/".data_get($game,"id")."/";
            makeDirectoryStorage($path);
            $dobrna->generatePDF($path,data_get($game,"id"),$key);
        }
    }
}
