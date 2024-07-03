<?php

namespace App\Discord\Command;

use App\Entity\Channel;
use App\Entity\Url;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use Doctrine\ORM\EntityManagerInterface;

class DeleteUrlCommand extends AbstractDiscordCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function getName(): string
    {
        return 'delete-url';
    }

    public function getDescription(): string
    {
        return "Delete an URL from the current channel";
    }

    public function getOptions(): array
    {
        return [
            [
                'name' => 'url',
                'required' => true,
                'description' => 'The url to remove',
                'type' => Option::STRING,
            ],
        ];
    }

    public function execute(Interaction $interaction): void
    {
        $urlValue = $interaction->data->options->get('name', 'url')->value;

        $channel = $this->entityManager->getRepository(Channel::class)->findOneBy(['channelId' => $interaction->channel_id]);

        if (null === $channel) {
            $interaction->respondWithMessage(MessageBuilder::new()->setContent("Le channel ne contient aucune url"));
        }

        /** @var Url $url */
        foreach ($channel->getUrls() as $url) {
            if ($url->getValue() === $urlValue) {
                $this->entityManager->remove($url);
                $this->entityManager->flush();
                $interaction->respondWithMessage(MessageBuilder::new()->setContent("L'url à bien été supprimé avec succés"));
                return;
            }
        }

        $interaction->respondWithMessage(MessageBuilder::new()->setContent("L'url demandé n'existe pas dans ce channel"));
    }
}