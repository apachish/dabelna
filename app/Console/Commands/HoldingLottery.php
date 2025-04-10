<?php

namespace App\Console\Commands;

use Apachish\Dabelna\App\Models\Game;
use App\Services\DobrnaGame;
use Illuminate\Console\Command;

class HoldingLottery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:holding-lottery';

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
        $games =  Game::where("lottery_date",now())->get();
        foreach ($games as $game) {
            $game->status = Game::STATUS_PLAYING;
            $game->update();
            $dobrna = new DobrnaGame($game->num_player);
        }

    }
}
