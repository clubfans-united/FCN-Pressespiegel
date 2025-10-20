<?php

namespace FCNPressespiegel\Models;

use DateTime;
use JsonSerializable;

class Article implements JsonSerializable
{
    private string $originalTitle;

    private string $displayTitle;

    private string $url;

    private string $excerpt;

    private DateTime $created;

    private string $host;

    public function setHost(string $host): Article
    {
        $this->host = $host;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): Article
    {
        $this->url = $url;
        return $this;
    }

    public function getDisplayTitle(): string
    {
        return $this->displayTitle;
    }

    public function setDisplayTitle(string $displayTitle): Article
    {
        $this->displayTitle = $displayTitle;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): Article
    {
        $this->created = $created;
        return $this;
    }

    public function jsonSerialize(): array
    {

        $createdAsString = $this->created->format(get_option('date_format') . ' ' . get_option('time_format'));

        return [
            'title' => $this->getDisplayTitle(),
            'original_title' => $this->getOriginalTitle(),
            'excerpt' => $this->getExcerpt(),
            'url' => $this->getUrl(),
            'host' => $this->getHost(),
            'created' => $createdAsString,
            'created_timestamp' => $this->created->getTimestamp(),
        ];
    }

    public function getOriginalTitle(): string
    {
        return $this->originalTitle . ' | ' . $this->host;
    }

    public function setOriginalTitle(string $originalTitle): Article
    {
        $this->originalTitle = $originalTitle;
        return $this;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): Article
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }
}
