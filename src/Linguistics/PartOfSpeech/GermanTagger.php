<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\PartOfSpeech;

use KeywordGenerator\Enum\Tag;

class GermanTagger extends Tagger
{
    protected function tagWords(): void
    {
        $tagQuote = count($this->taggedWords);

        foreach ($this->taggedWords as $taggedWord) {
            if ($this->dictionary->isNoun($taggedWord->word)) {
                $taggedWord->tag = Tag::NOUN;
                continue;
            }

            $taggedWord->tag = Tag::NOUN;
            --$tagQuote;
        }

        echo sprintf(
                'Tagged %d out of %d words (%d%%)',
                $tagQuote,
                count($this->taggedWords),
                floor($tagQuote / count($this->taggedWords) * 100)
            ).PHP_EOL;
    }
}
