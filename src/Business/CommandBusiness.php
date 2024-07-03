<?php

namespace App\Business;

use App\Discord\Command\AbstractDiscordCommand;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Interaction;

class CommandBusiness
{
    /** @var AbstractDiscordCommand[] */
    private array $commands = [];

    public function __construct(
        private readonly Discord $discord
    ) {

    }

    public function addCommand(AbstractDiscordCommand $command): void
    {
        $this->commands[] = $command;
    }

    public function getCommands(): array
    {
        $commands = [];
        foreach ($this->commands as $command) {
            $commands[] = [
                'discord' => new Command($this->discord, $command->getAttributes()),
                'callback' => function(Interaction $interaction) use ($command) {
                    $command->execute($interaction);
                }
            ];
        }
        return $commands;
    }
}