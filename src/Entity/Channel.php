<?php

namespace App\Entity;

use App\Repository\ChannelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChannelRepository::class)]
class Channel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $channelId = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $lastOfferId = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChannelId(): ?int
    {
        return $this->channelId;
    }

    public function setChannelId(?int $channelId): self
    {
        $this->channelId = $channelId;
        return $this;
    }

    public function getLastOfferId(): ?int
    {
        return $this->lastOfferId;
    }

    public function setLastOfferId(?int $lastOfferId): self
    {
        $this->lastOfferId = $lastOfferId;
        return $this;
    }
}