<?php

namespace App\Console\Commands;

use App\Events\MessageSentEvent;
use Illuminate\Console\Command;

class BroadcastMessageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:broadcast-message {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        MessageSentEvent::dispatch($this->argument('message'));

        return 0;
    }

}
