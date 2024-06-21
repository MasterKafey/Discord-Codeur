<?php

namespace App\Command;

use App\Business\CommandBusiness;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\all;

#[AsCommand(name: 'app:start', description: 'Start discord bot')]
class StartCommand extends Command
{
    public function __construct(
        private readonly CommandBusiness $commandBusiness,
        private readonly string $discordBotToken,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $discord = new Discord([
            'token' => $this->discordBotToken,
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT | Intents::GUILD_MESSAGES | Intents::GUILDS,
        ]);

        $discord->on('ready', function(Discord $discord) {
            $discord->application->commands->freshen()->then(function ($commands) use ($discord) {
                $promises = [];
                foreach ($commands as $command) {
                    $promises[] = $discord->application->commands->delete($command->id);
                }

                return all($promises);
            })->then(function() use ($discord) {
                $commands = $this->commandBusiness->getCommands($discord);
                foreach ($commands as $command) {
                    $discord->application->commands->save($command['discord']);
                    $discord->listenCommand($command['discord']->name, $command['callback']);
                }
            });
        });


        $discord->run();

        return Command::SUCCESS;
    }
}