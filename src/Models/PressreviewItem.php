<?php

namespace FCNPressespiegel\Models;

use DateTime;
use JsonSerializable;

class PressreviewItem implements JsonSerializable
{
    /**
     * @var string
     */
    private string $originalTitle;

    /**
     * @var string
     */
    private string $displayTitle;

    /**
     * @var string
     */
    private string $url;

    /**
     * @var string
     */
    private string $excerpt;

    /**
     * @var DateTime
     */
    private DateTime $created;

    /**
     * @var string
     */
    private string $host;

    /**
     * @param string $host
     * @return PressreviewItem
     */
    public function setHost(string $host): PressreviewItem
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return PressreviewItem
     */
    public function setUrl(string $url): PressreviewItem
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayTitle(): string
    {
        return $this->displayTitle;
    }

    /**
     * @param string $displayTitle
     * @return PressreviewItem
     */
    public function setDisplayTitle(string $displayTitle): PressreviewItem
    {
        $this->displayTitle = $displayTitle;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * @return PressreviewItem
     */
    public function setCreated(DateTime $created): PressreviewItem
    {
        $this->created = $created;
        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
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

    /**
     * @param string $originalTitle
     * @return PressreviewItem
     */
    public function setOriginalTitle(string $originalTitle): PressreviewItem
    {
        $this->originalTitle = $originalTitle;
        return $this;
    }

    /**
     * @return string
     */
    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    /**
     * @param string $excerpt
     * @return PressreviewItem
     */
    public function setExcerpt(string $excerpt): PressreviewItem
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }
}
