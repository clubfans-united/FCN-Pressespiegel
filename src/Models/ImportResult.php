<?php

namespace FCNPressespiegel\Models;

readonly class ImportResult implements \JsonSerializable
{

    private int $timestamp;

    /**
     * @param Article[] $articles
     * @param array     $feedErrors
     * @param array     $articleErrors
     */
    public function __construct(public array $articles = [], public array $feedErrors = [], public array $articleErrors = [])
    {
        $this->timestamp = time();
    }

    public function jsonSerialize(): array
    {
        return [
            'articles' => $this->articles,
            'feedErrors' => $this->feedErrors,
            'articleErrors' => $this->articleErrors
        ];
    }

    public function hasFeedErrors(): bool
    {
        return count($this->feedErrors) > 0;
    }

    public function hasArticleErrors(): bool
    {
        return count($this->articleErrors) > 0;
    }

    public function hasErrors(): bool
    {
        return $this->hasFeedErrors() || $this->hasArticleErrors();
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getDateTime(): \DateTime
    {
        $dateTime = new \DateTime('@' . $this->timestamp);
        $dateTime->setTimezone(wp_timezone());
        return $dateTime;
    }


}
