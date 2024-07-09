<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Lemma;

use KeywordGenerator\Enum\Tag;
use KeywordGenerator\Struct\TaggedWord;

class EnglishLemmatizer implements Lemmatizer
{
    public function lemmatize(TaggedWord ...$taggedWords): array
    {
        foreach ($taggedWords as $taggedWord) {
            if ($taggedWord->tag === Tag::NOUN_PLURAL && str_ends_with($taggedWord->word, 's')) {
                $taggedWord->word = substr($taggedWord->word, 0, -1);
                $taggedWord->tag = Tag::NOUN;
            }
        }

        return $taggedWords;
    }
}
