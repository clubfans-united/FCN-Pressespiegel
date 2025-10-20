<?php

namespace FCNPressespiegel\Models;

readonly class ImportResult implements \JsonSerializable
{
    /**
     * @param Article[] $articles
     * @param array $feedErrors
     * @param array $articleErrors
     */
    public function __construct(public array $articles = [], public array $feedErrors = [], public array $articleErrors = [])
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'articles' => $this->articles,
            'feedErrors' => $this->feedErrors,
            'articleErrors' => $this->articleErrors
        ];
    }
}
