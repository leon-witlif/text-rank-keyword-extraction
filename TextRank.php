<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use KeywordGenerator\Collection\StopWordCollection;
use KeywordGenerator\FileSystem\FileSystem;
use KeywordGenerator\Implementation\TextRank;
use KeywordGenerator\Linguistics\Dictionary\EmptyDictionary;
use KeywordGenerator\Linguistics\Dictionary\EnglishDictionary;
use KeywordGenerator\Linguistics\Filter\EnglishFilter;
use KeywordGenerator\Linguistics\Filter\GermanFilter;
use KeywordGenerator\Linguistics\Lemma\EnglishLemmatizer;
use KeywordGenerator\Linguistics\PartOfSpeech\EnglishTagger;
use KeywordGenerator\Linguistics\PartOfSpeech\GermanTagger;
use KeywordGenerator\Linguistics\PartOfSpeech\TestTagger;
use KeywordGenerator\Struct\Keyword;
use KeywordGenerator\Struct\TaggedWord;

switch ('de') {
    case 'test':
    default:
        $filter = new EnglishFilter();
        $tagger = new TestTagger();
        $lemmatizer = new EnglishLemmatizer();
        $stopWords = StopWordCollection::fromFile(FileSystem::FILES_DIRECTORY.'/stopwords-english-1.txt');
        $stopWords->merge(new StopWordCollection(['corresponding']));

        break;
    case 'en':
        $filter = new EnglishFilter();
        $dictionary = new EnglishDictionary();
        $tagger = new EnglishTagger($dictionary);
        $lemmatizer = new EnglishLemmatizer();
        $stopWords = StopWordCollection::fromFile(FileSystem::FILES_DIRECTORY.'/stopwords-english-1.txt');

        break;
    case 'de':
        $filter = new GermanFilter();
        $dictionary = new EmptyDictionary();
        $tagger = new GermanTagger($dictionary);
        // $lemmatizer = new GermanLemmatizer();
        $stopWords = StopWordCollection::fromFile(FileSystem::FILES_DIRECTORY.'/stopwords-german-1.txt');

        break;
}

$generator = new TextRank($filter, $tagger, $lemmatizer, $stopWords);

// $text = implode(' ', FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/sample-english-1.txt'));
$text = implode(' ', FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/sample-german-1.txt'));
// $text = implode(' ', FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/sample-german-2.txt'));

$text = strtolower($text);

$processedWords = $generator->generateKeywords($text);
$processedText = array_map(fn (TaggedWord $word) => $word->word, $processedWords);
// print_r($processedText);

$lemmatizedText = array_map(fn (TaggedWord $word) => $word->word, $generator->getLemmatizedWords());
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
    if ($generator->getStopWordCollection()->contains($word)) {
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
