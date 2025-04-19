<?php

namespace App\Console\Commands;

use Apachish\Dabelna\App\Models\Bot;
use Apachish\Dabelna\App\Models\Game;
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
        $game = Game::where("id", 4)->first();
        $game->remaining_card = 0;
        if ($game->isDirty()) {
            logger('Game is dirty'); // یعنی قراره چیزی تغییر کنه
            $game->save();
        } else {
            logger('Nothing to update');
        }
    }
}
