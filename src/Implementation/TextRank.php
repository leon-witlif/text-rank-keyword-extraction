<?php

declare(strict_types=1);

namespace KeywordGenerator\Implementation;

use KeywordGenerator\Collection\StopWordCollection;
use KeywordGenerator\Enum\Tag;
use KeywordGenerator\Linguistics\Filter\Filter;
use KeywordGenerator\Linguistics\Lemma\Lemmatizer;
use KeywordGenerator\Linguistics\PartOfSpeech\Tagger;
use KeywordGenerator\Struct\TaggedWord;

class TextRank implements KeywordGenerator
{
    /** @var TaggedWord[] */
    private array $lemmatizedTaggedWords = [];

    /** @var string[] */
    private array $lemmatizedWords = [];

    /** @var TaggedWord[] */
    private array $processedTaggedWords = [];

    /** @var string[] */
    private array $processedWords = [];

    /** @var string[] */
    private array $vocabulary = [];

    /** @var float[][] */
    private array $weightedEdges = [];

    /** @var float[] */
    private array $scores = [];

    /** @var string[] */
    private array $phrases = [];

    public function __construct(
        private Filter $filter,
        private Tagger $tagger,
        private Lemmatizer $lemmatizer,
        private StopWordCollection $stopWords
    ) {
    }

    public function generateKeywords(string $text): array
    {
        $this->prepareText($text);

        $this->calculateWeightedEdges();

        $this->calculateScore();

        $this->buildPhrases();

        return $this->phrases;
    }

    private function prepareText(string $text): void
    {
        $filteredText = $this->filter->filter($text);

        $taggedWords = $this->tagger->tag($filteredText);

        $this->lemmatizedTaggedWords = $this->lemmatizer->lemmatize(...$taggedWords);
        $this->lemmatizedWords = array_map(fn (TaggedWord $word): string => $word->word, $this->lemmatizedTaggedWords);

        $this->addNotWantedWordsToStopWordsByPOS();

        $this->removeStopWords();

        $this->createVocabulary();
    }

    private function addNotWantedWordsToStopWordsByPOS(): void
    {
        $wantedPOS = [
            Tag::NOUN,
            Tag::NOUN_PLURAL,
            Tag::NOUN_PROPER_PLURAL,
            Tag::NOUN_PROPER_PLURAL,
            Tag::ADJECTIVE,
            // 'JJR',
            // 'JJS',
            Tag::VERB_GERUND,
            // 'FW',
        ];

        $notWantedTaggedWords = array_filter(
            $this->lemmatizedTaggedWords,
            fn (TaggedWord $taggedWord): bool => !in_array($taggedWord->tag, $wantedPOS, true)
        );

        $notWantedWords = array_map(fn (TaggedWord $taggedWord): string => $taggedWord->word, $notWantedTaggedWords);

        $this->stopWords->merge(new StopWordCollection($notWantedWords));
    }

    private function removeStopWords(): void
    {
        foreach ($this->lemmatizedTaggedWords as $taggedWord) {
            if (!$this->stopWords->contains($taggedWord->word)) {
                $this->processedTaggedWords[] = $taggedWord;
            }
        }

        $this->processedWords = array_map(fn (TaggedWord $word): string => $word->word, $this->processedTaggedWords);
    }

    private function createVocabulary(): void
    {
        $this->vocabulary = array_values(array_unique($this->processedWords));
    }

    private function calculateWeightedEdges(): void
    {
        $vocabularyLength = count($this->vocabulary);

        $windowSize = 3;
        $coveredCoOccurrence = [];

        function set2DArrayValue(array &$array, int $i, int $j, int|bool $value): void
        {
            if (!array_key_exists($i, $array)) {
                $array[$i] = [];
            }

            $array[$i][$j] = $value;
        }

        for ($i = 0; $i < $vocabularyLength; $i++) {
            $this->scores[$i] = 1.0;

            for ($j = 0; $j < $vocabularyLength; $j++) {
                if ($j === $i) {
                    set2DArrayValue($this->weightedEdges, $i, $j, 0);
                } else {
                    for ($windowStart = 0; $windowStart < count($this->processedWords) - $windowSize; $windowStart++) {
                        $window = array_slice($this->processedWords, $windowStart, $windowSize, true);

                        if (in_array($this->vocabulary[$i], $window) && in_array($this->vocabulary[$j], $window)) {
                            $indexOfI = $windowStart + array_search($this->vocabulary[$i], $window);
                            $indexOfJ = $windowStart + array_search($this->vocabulary[$j], $window);

                            if (!isset($coveredCoOccurrence[$indexOfI][$indexOfJ])) {
                                if (!isset($this->weightedEdges[$i][$j])) {
                                    set2DArrayValue($this->weightedEdges, $i, $j, 0);
                                }

                                $this->weightedEdges[$i][$j] += 1 / abs($indexOfI - $indexOfJ);
                                set2DArrayValue($coveredCoOccurrence, $indexOfI, $indexOfJ, true);
                            }
                        }
                    }
                }
            }
        }
    }

    private function calculateScore(): void
    {
        $vocabularyLength = count($this->vocabulary);
        $inout = [];

        for ($i = 0; $i < $vocabularyLength; $i++) {
            $inout[$i] = .0;

            for ($j = 0; $j < $vocabularyLength; $j++) {
                if (isset($this->weightedEdges[$i][$j])) {
                    $inout[$i] += $this->weightedEdges[$i][$j];
                }
            }
        }

        $maxIterations = 50;
        $dampeningFactor = 0.85;
        $convergenceThreshold = 0.0001;

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $prevScore = $this->scores;

            for ($i = 0; $i < $vocabularyLength; $i++) {
                $summation = 0;

                for ($j = 0; $j < $vocabularyLength; $j++) {
                    if (isset($this->weightedEdges[$i][$j])) {
                        try {
                            $summation += $this->weightedEdges[$i][$j] / $inout[$j] * $this->scores[$j];
                        } catch (\Error) {
                        }
                    }
                }

                $this->scores[$i] = (1 - $dampeningFactor) + $dampeningFactor * $summation;

                // if np.sum(np.fabs(prevScore - this->score)) <= convergenceThreshold:
                //     break
            }
        }
    }

    private function buildPhrases(): void
    {
        $phrase = ' ';

        foreach ($this->lemmatizedWords as $word) {
            if ($this->stopWords->contains($word)) {
                if ($phrase !== ' ') {
                    $this->phrases[] = explode(' ', trim($phrase));
                }

                $phrase = ' ';
            } else {
                $phrase .= $word.' ';
            }
        }
    }

    // Temporary getter functions
    public function getVocabulary(): array
    {
        return $this->vocabulary;
    }

    public function getScores(): array
    {
        return $this->scores;
    }
}
