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
        $channelIds = array_unique(array_map(function (Url $url) {
            return $url->getChannelId();
        }, $urls));

        $channels = $this->entityManager->getRepository(Channel::class)->findByIds($channelIds);
        $channelIds = [];
        foreach ($channels as $channel)
        {
            $channelIds[$channel->getChannelId()] = $channel;
        }
        $channels = $channelIds;
        $urlsByChannels = [];

        foreach ($urls as $url) {
            $urlsByChannels[$url->getChannelId()] = array_merge($urlsByChannels[$url->getChannelId()] ?? [], [$url]);
        }

        $offersToHandle = [];
        foreach ($urlsByChannels as $channelId => $urls) {
            $channel = $channels[$channelId] ?? (new Channel())->setChannelId($channelId);
            $lastOfferId = $channel->getLastOfferId();
            $maxOfferId = $lastOfferId;
            foreach ($urls as $url) {
                $offers = $this->codeurBusiness->getOffers($url->getValue());
                usort($offers, function (array $offer) {
                    return $offer['guid'];
                });
                foreach ($offers as $offer) {
                    $currentId = $offer['guid'];

                    if ($currentId <= $lastOfferId) {
                        continue;
                    }
                    if ($currentId > $maxOfferId) {
                        $maxOfferId = $currentId;
                    }
                    $offer['discord'] = [
                        'channel' => $channel,
                        'url' => $url,
                    ];

                    $offersToHandle[] = $offer;
                }
            }
            $channel->setLastOfferId($maxOfferId);
            $this->entityManager->persist($channel);
        }

        if (!empty($offersToHandle)) {
            $discord = new Discord([
                'token' => $this->discordBotToken,
                'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT | Intents::GUILD_MESSAGES | Intents::GUILDS,
            ]);
            usort($offersToHandle, function (array $offer) {
                return -$offer['guid'];
            });
            $discord->on('ready', function (Discord $discord) use ($offersToHandle) {
                $promises = [];
                foreach ($offersToHandle as $offer) {
                    $message = $this->codeurBusiness->convertOfferToMarkdown($offer);
                    $promises[] = $discord->getChannel($offer['discord']['channel']->getChannelId())->sendMessage($message);
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