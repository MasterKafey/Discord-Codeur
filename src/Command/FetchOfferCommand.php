<?php

namespace App\Command;

use App\Business\CodeurBusiness;
use App\Entity\Channel;
use App\Entity\Url;
use Discord\Discord;
use Discord\WebSockets\Intents;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Promise\all;

#[AsCommand(name: 'app:codeur:fetch-offer')]
class FetchOfferCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CodeurBusiness         $codeurBusiness,
        private readonly string                 $discordBotToken,
    )
    {
        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $urls = $this->entityManager->getRepository(Url::class)->findAll();

        foreach ($urls as $url) {
            $offers = $this->codeurBusiness->getOffers($url->getValue());
            $max = $url->getChannel()->getLastPublishedOfferDateTime();
            foreach ($offers as $offer) {
                $offerDateTime = new \DateTime($offer['pubDate']);
                if ($offerDateTime > $url->getChannel()->getLastPublishedOfferDateTime()) {
                    $offersToHandle[] = [
                        'offer' => $offer,
                        'url' => $url,
                    ];
                }

                if ($offerDateTime > $max) {
                    $max = $offerDateTime;
                }
            }
            $url->getChannel()->setLastPublishedOfferDateTime($max);
            $this->entityManager->persist($url->getChannel());
        }


        if (!empty($offersToHandle)) {
            $discord = new Discord([
                'token' => $this->discordBotToken,
                'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT | Intents::GUILD_MESSAGES | Intents::GUILDS,
            ]);
            usort($offersToHandle, function (array $a, array $b) {
                return $b['offer']['guid'] <=> $a['offer']['guid'];
            });
            $discord->on('ready', function (Discord $discord) use ($offersToHandle) {
                $promises = [];
                foreach ($offersToHandle as ['offer' => $offer, 'url' => $url]) {
                    $message = $this->codeurBusiness->convertOfferToMarkdown($offer);
                    $promises[] = $discord->getChannel($url->getChannel()->getChannelId())->sendMessage($message);
                }

                all($promises)->done(function () use ($discord) {
                    $discord->close();
                });
            });
            $discord->run();
        }
        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}