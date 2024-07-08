<?php

declare(strict_types=1);

namespace KeywordGenerator\Implementation;

use KeywordGenerator\KeywordGenerator;
use KeywordGenerator\Linguistics\Filter\Filter;
use KeywordGenerator\Linguistics\Lemma\Lemmatizer;
use KeywordGenerator\Linguistics\PartOfSpeech\Tagger;

class TextRank implements KeywordGenerator
{
    public function __construct(
        private Filter $filter,
        private Tagger $tagger,
        private Lemmatizer $lemmatizer
    ) {
    }

    public function generateKeywords(string $text): array
    {
        $filteredText = $this->filter->filter($text);

        $taggedWords = $this->tagger->tag($filteredText);

        $lemmatizedWords = $this->lemmatizer->lemmatize(...$taggedWords);

        return $lemmatizedWords;
    }
}
