<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use KeywordGenerator\Collection\StopWordCollection;
use KeywordGenerator\Enum\Tag;
use KeywordGenerator\FileSystem\FileSystem;
use KeywordGenerator\Implementation\TextRank;
use KeywordGenerator\Linguistics\Filter\EnglishFilter;
use KeywordGenerator\Linguistics\Filter\GermanFilter;
use KeywordGenerator\Linguistics\Lemma\EnglishLemmatizer;
use KeywordGenerator\Linguistics\PartOfSpeech\EnglishTagger;
use KeywordGenerator\Struct\Keyword;
use KeywordGenerator\Struct\TaggedWord;

$language = 'en';

$generator = new TextRank(new EnglishFilter(), new EnglishTagger(), new EnglishLemmatizer());

$text = implode(' ', FileSystem::readFileContents('sample-english-1.txt'));
// $text = implode(' ', FileSystem::readFileContents('sample-german-1.txt'));
// $text = implode(' ', FileSystem::readFileContents('sample-german-2.txt'));

$text = strtolower($text);

$lemmatizedWords = $generator->generateKeywords($text);
// print_r($lemmatizedWords);

$stateWasFirstAllAppearance = false;

function applyPOSTagCorrection(TaggedWord $word): TaggedWord
{
    global $stateWasFirstAllAppearance;

    switch ($word->word) {
        case 'of':
        case 'over':
        case 'for':
        case 'in':
            $word->tag = Tag::PREPOSITION;
            break;
        case 'linear':
        case 'diophantine':
        case 'strict':
        case 'nonstrict':
        case 'upper':
        case 'corresponding':
        case 'mixed':
            $word->tag = Tag::ADJECTIVE;
            break;
        case 'the':
        case 'a':
        case 'these':
            $word->tag = Tag::DETERMINER;
            break;
        // case 'inequation':
        //     $word->token = 'inequations';
        //     $word->setTag('NNS');
        //     break;
        case 'and':
            $word->tag = Tag::CONJUNCTION;
            break;
        // case 'are':
        //     $word->setTag('VBP');
        //     break;
        // case 'given':
        //     $word->setTag('VBN');
        //     break;
        case 'criteria':
            $word->word = 'criterion';
            break;
        case 'supporting':
            $word->tag = Tag::NOUN;
            break;
        // case 'can':
        //     $word->setTag('MD');
        //     break;
        // case 'be':
        //     $word->setTag('VB');
        //     break;
        case 'all':
            if (!$stateWasFirstAllAppearance) {
                $word->tag = Tag::DETERMINER;
                $stateWasFirstAllAppearance = true;
            } else {
                // $word->setTag('PDT');
            }
            break;
    }

    return $word;
}

foreach ($lemmatizedWords as $word) {
    applyPOSTagCorrection($word);
}

// foreach ($lemmatizedWords as $word) {
//     echo $word.', ';
// }

$wantedPOS = [
    Tag::NOUN,
    Tag::NOUN_PLURAL,
    Tag::NOUN_PROPER_PLURAL,
    Tag::NOUN_PROPER_PLURAL,
    Tag::ADJECTIVE,
    // 'JJR',
    // 'JJS',
    // 'VBG',
    // 'FW',
];

$stopWords = array_filter($lemmatizedWords, fn (TaggedWord $word) => !in_array($word->tag, $wantedPOS) /*|| !$word->isProcessed()*/);
$stopWordCollection = new StopWordCollection(array_map(fn (TaggedWord $word): string => $word->word, $stopWords));

if ($language === 'en') {
    $stopWordCollection->merge(
        StopWordCollection::fromFile('stopwords-english-1.txt'),
        new StopWordCollection(['corresponding'])
    );
} else {
    $stopWordCollection->merge(StopWordCollection::fromFile('stopwords-german-1.txt'));
    // $stopWordCollection->merge(StopWordCollection::fromFile('stopwords-german-2.txt'));
}

function removeStopWords(TaggedWord ...$words): array
{
    global $stopWordCollection;

    $words = array_filter($words, fn (TaggedWord $word) => !$stopWordCollection->contains($word->word));
    $words = array_values($words);

    return $words;
}

$processedWords = removeStopWords(...$lemmatizedWords);
$processedText = array_map(fn (TaggedWord $word) => $word->word, $processedWords);
// print_r($processedText);

$lemmatizedText = array_map(fn (TaggedWord $word) => $word->word, $lemmatizedWords);
// print_r($lemmatizedText);

$vocabulary = array_values(array_unique($processedText));
// print_r($vocabulary);

$vocabularyLength = count($vocabulary);
$weightedEdge = [];

$score = [];
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
    $score[$i] = 1;

    for ($j = 0; $j < $vocabularyLength; $j++) {
        if ($j === $i) {
            set2DArrayValue($weightedEdge, $i, $j, 0);
        } else {
            for ($windowStart = 0; $windowStart < count($processedText) - $windowSize; $windowStart++) {
                $window = array_slice($processedText, $windowStart, $windowSize, true);

                if (in_array($vocabulary[$i], $window) && in_array($vocabulary[$j], $window)) {
                    $indexOfI = $windowStart + array_search($vocabulary[$i], $window);
                    $indexOfJ = $windowStart + array_search($vocabulary[$j], $window);

                    if (!isset($coveredCoOccurrence[$indexOfI][$indexOfJ])) {
                        if (!isset($weightedEdge[$i][$j])) {
                            set2DArrayValue($weightedEdge, $i, $j, 0);
                        }

                        $weightedEdge[$i][$j] += 1 / abs($indexOfI - $indexOfJ);
                        set2DArrayValue($coveredCoOccurrence, $indexOfI, $indexOfJ, true);
                    }
                }
            }
        }
    }
}

// print_r($weightedEdge);

$inout = [];

for ($i = 0; $i < $vocabularyLength; $i++) {
    $inout[$i] = 0;

    for ($j = 0; $j < $vocabularyLength; $j++) {
        if (isset($weightedEdge[$i][$j])) {
            $inout[$i] += $weightedEdge[$i][$j];
        }
    }
}

// print_r($inout);

$maxIterations = 50;
$dampeningFactor = 0.85;
$threshold = 0.0001;

for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
    $prevScore = $score;

    for ($i = 0; $i < $vocabularyLength; $i++) {
        $summation = 0;

        for ($j = 0; $j < $vocabularyLength; $j++) {
            if (isset($weightedEdge[$i][$j])) {
                try {
                    $summation += $weightedEdge[$i][$j] / $inout[$j] * $score[$j];
                } catch (Error) {
                }
            }
        }

        $score[$i] = (1 - $dampeningFactor) + $dampeningFactor * $summation;
    }
}

// for ($i = 0; $i < $vocabularyLength; $i++) {
//     echo sprintf('%s: %f', $vocabulary[$i], $score[$i]).PHP_EOL;
// }

$phrases = [];
$phrase = ' ';

foreach ($lemmatizedText as $word) {
    if ($stopWordCollection->contains($word)) {
        if ($phrase !== ' ') {
            $phrases[] = explode(' ', trim($phrase));
        }

        $phrase = ' ';
    } else {
        $phrase .= $word.' ';
    }
}

// print_r($phrase);
// print_r($phrases);

$uniquePhrases = array_values(array_unique($phrases, SORT_REGULAR));
// print_r($uniquePhrases);

foreach ($vocabulary as $word) {
    foreach ($uniquePhrases as $phrase) {
        if (in_array($word, $phrase) && in_array([$word], $uniquePhrases) && count($phrase) > 1) {
            unset($uniquePhrases[array_search([$word], $uniquePhrases)]);
        }
    }
}

// print_r($uniquePhrases);

$phraseScores = [];
$keywords = [];

foreach ($uniquePhrases as $phrase) {
    $phraseScore = 0;
    $keyword = '';

    foreach ($phrase as $word) {
        $keyword .= $word.' ';
        $phraseScore += $score[array_search($word, $vocabulary)];
    }

    $keywords[] = new Keyword(trim($keyword), $phraseScore);
}

// $counter = 0;
// foreach ($keywords as $keyword) {
//     echo sprintf('Keyword: %s: Score: %f', $keyword, $phraseScores[$counter]).PHP_EOL;
//     $counter++;
// }

usort($keywords, fn (Keyword $a, Keyword $b) => $a->score <=> $b->score);

$keywords = array_reverse($keywords);

foreach (array_slice($keywords, 0, 10) as $keyword) {
    echo $keyword->keyword.PHP_EOL;
}
