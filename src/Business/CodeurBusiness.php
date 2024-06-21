<?php

namespace App\Business;

use League\HTMLToMarkdown\HtmlConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CodeurBusiness
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly HtmlConverterInterface $htmlConverter,
    )
    {
    }

    public function getOffers(string $url): array
    {
        $content = $this->client->request(Request::METHOD_GET, $url)->getContent();

        $data = json_decode(json_encode(simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return array_reverse($data['channel']['item']);
    }

    public function convertOfferToMarkdown(array $offer): string
    {
        ['title' => $title, 'pubDate' => $pubDate, 'description' => $description, 'link' => $link] = $offer;
        $title = $this->htmlConverter->convert($title);
        $pubDate = new \DateTime($pubDate);
        $description = $this->htmlConverter->convert(trim($description));
        $lines = explode("\n", $description);
        $description = [];
        for ($i = 0; $i < count($lines) - 1; $i++) {
            if (empty($lines[$i])) {
                continue;
            }

            $description[] = trim($lines[$i]);
        }
        $description = preg_replace('/https?:\/\/[^\s]+/', '<$0>', implode("\n", $description));
        return "## [$title](<$link>)\n**{$pubDate->format('d/m/y H:i')}**\n```$description```";
    }
}