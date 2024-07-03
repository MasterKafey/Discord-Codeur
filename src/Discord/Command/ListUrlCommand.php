<?php

namespace App\Discord\Command;

use App\Entity\Channel;
use App\Entity\Url;
use Discord\Builders\Components\Button;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Interaction;
use Doctrine\ORM\EntityManagerInterface;

class ListUrlCommand extends AbstractDiscordCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {

    }

    public function getName(): string
    {
        return 'list-urls';
    }

    public function execute(Interaction $interaction): void
    {
        $channelId = $interaction->channel_id;

        $channel = $this->entityManager->getRepository(Channel::class)->findOneBy(['channelId' => $channelId]);

        if (null === $channel)
        {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent("Le channel ne contient pas d'url"));
            return;
        }

        $messages = implode("\n", array_map(function (Url $url) {
            return $url->getValue();
        }, $channel->getUrls()->toArray()));

        $interaction->respondWithMessage(MessageBuilder::new()->setContent($messages));
    }
}