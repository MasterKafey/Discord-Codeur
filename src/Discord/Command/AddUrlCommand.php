<?php

namespace App\Discord\Command;

use App\Entity\Url;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Doctrine\ORM\EntityManagerInterface;

class AddUrlCommand extends AbstractDiscordCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function getName(): string
    {
        return 'add-url';
    }

    public function getDescription(): string
    {
        return 'Add a new url to watch';
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'url',
                'description' => 'The url to watch',
                'type' => Option::STRING,
                'required' => true,
            ]
        ];
    }

    public function execute(Interaction $interaction): void
    {
        /** @var \Discord\Parts\Interactions\Request\Option $option */
        $option = $interaction->data->options->get('name', 'url');
        $url = (new Url())->setValue($option->value)->setChannelId($interaction->channel_id);
        $this->entityManager->persist($url);
        $this->entityManager->flush();
        $interaction->respondWithMessage(MessageBuilder::new()->setContent('URL added successfully!'));
    }
}