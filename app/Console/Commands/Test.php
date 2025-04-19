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

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $game_server = new GameService();
        $game = Game::with("winners")->where("id", 4)->first();
        $game_server->sendWinner($game);
    }
}
