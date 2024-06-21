<?php

namespace App\MessageHandler\Handler;

use App\MessageHandler\Message\SendOfferMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;
#[AsMessageHandler]
class SendOfferMessageHandler
{
    public function __invoke(SendOfferMessage $message): void
    {
        $process = new Process(['php', 'bin/console', 'app:codeur:fetch-offer']);
        $process->run();
    }
}