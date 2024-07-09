<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Dictionary;

interface Dictionary
{
    public function isNoun(string $word): bool;

    public function isNounPlural(string $word): bool;

    public function isPronoun(string $word): bool;

    public function isVerb(string $word): bool;

    public function isVerbGerund(string $word): bool;

    public function isVerbPastParticiple(string $word): bool;

    public function isAdjective(string $word): bool;

    public function isAdverb(string $word): bool;

    public function isConjunction(string $word): bool;

    public function isPreposition(string $word): bool;

    public function isInterjection(string $word): bool;
}
