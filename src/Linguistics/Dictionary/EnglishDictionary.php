<?php

declare(strict_types=1);

namespace KeywordGenerator\Linguistics\Dictionary;

use KeywordGenerator\FileSystem\FileSystem;

class EnglishDictionary implements Dictionary
{
    /** @var string[] */
    private array $nouns;

    /** @var string[] */
    private array $pronouns;

    /** @var string[] */
    private array $verbs;

    /** @var string[] */
    private array $adjectives;

    /** @var string[] */
    private array $adverbs;

    /** @var string[] */
    private array $conjunctions;

    /** @var string[] */
    private array $prepositions;

    /** @var string[] */
    private array $interjections;

    public function __construct()
    {
        $this->nouns = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/noun.txt');
        $this->pronouns = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/pronoun.txt');
        $this->verbs = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/verb.txt');
        $this->adjectives = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/adjective.txt');
        $this->adverbs = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/adverb.txt');
        $this->conjunctions = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/conjunction.txt');
        $this->prepositions = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/preposition.txt');
        $this->interjections = FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/dictionary/interjection.txt');
    }

    public function isNoun(string $word): bool
    {
        return in_array($word, $this->nouns, true);
    }

    public function isNounPlural(string $word): bool
    {
        return str_ends_with($word, 's') && in_array(substr($word, 0, -1), $this->nouns, true);
    }

    public function isPronoun(string $word): bool
    {
        return in_array($word, $this->pronouns, true);
    }

    public function isVerb(string $word): bool
    {
        return in_array($word, $this->verbs, true);
    }

    public function isVerbGerund(string $word): bool
    {
        return str_ends_with($word, 'ing') && in_array(substr($word, 0, -3), $this->verbs, true);
    }

    public function isVerbPastParticiple(string $word): bool
    {
        return str_ends_with($word, 'ed') && in_array(substr($word, 0, -2), $this->verbs, true);
    }

    public function isAdjective(string $word): bool
    {
        return in_array($word, $this->adjectives, true);
    }

    public function isAdverb(string $word): bool
    {
        return in_array($word, $this->adverbs, true) || str_ends_with($word, 'ly');
    }

    public function isConjunction(string $word): bool
    {
        return in_array($word, $this->conjunctions, true);
    }

    public function isPreposition(string $word): bool
    {
        return in_array($word, $this->prepositions, true);
    }

    public function isInterjection(string $word): bool
    {
        return in_array($word, $this->interjections, true);
    }
}
