<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Dictionary;

class EmptyDictionary implements Dictionary
{
    public function isNoun(string $word): bool
    {
        return true;
    }

    public function isNounPlural(string $word): bool
    {
        return false;
    }

    public function isPronoun(string $word): bool
    {
        return false;
    }

    public function isVerb(string $word): bool
    {
        return false;
    }

    public function isVerbGerund(string $word): bool
    {
        return false;
    }

    public function isVerbPastParticiple(string $word): bool
    {
        return false;
    }

    public function isAdjective(string $word): bool
    {
        return false;
    }

    public function isAdverb(string $word): bool
    {
        return false;
    }

    public function isConjunction(string $word): bool
    {
        return false;
    }

    public function isPreposition(string $word): bool
    {
        return false;
    }

    public function isInterjection(string $word): bool
    {
        return false;
    }
}
