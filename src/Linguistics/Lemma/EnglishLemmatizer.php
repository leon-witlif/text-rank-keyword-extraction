<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Lemma;

use KeywordGenerator\Struct\TaggedWord;

class EnglishLemmatizer implements Lemmatizer
{
    public function lemmatize(TaggedWord ...$taggedWords): array
    {
        return $taggedWords;
    }
}
