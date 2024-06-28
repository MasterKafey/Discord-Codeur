<?php

namespace App\Entity;

use App\Repository\ChannelRepository;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTime $lastPublishedOfferDateTime;

    #[ORM\OneToMany(targetEntity: Url::class, mappedBy: 'channel')]
    private Collection $urls;

    public function __construct()
    {
        $this->lastPublishedOfferDateTime = new \DateTime();
    }

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

    public function getLastPublishedOfferDateTime(): \DateTime
    {
        return $this->lastPublishedOfferDateTime;
    }

    public function setLastPublishedOfferDateTime(\DateTime $lastPublishedOfferDateTime): self
    {
        $this->lastPublishedOfferDateTime = $lastPublishedOfferDateTime;
        return $this;
    }

    public function getUrls(): Collection
    {
        return $this->urls;
    }

    public function setUrls(Collection $urls): self
    {
        $this->urls = $urls;
        return $this;
    }
}