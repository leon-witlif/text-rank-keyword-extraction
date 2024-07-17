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
use KeywordGenerator\Struct\Keyword;
use KeywordGenerator\Struct\TaggedWord;

switch ('en') {
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

$text = implode(' ', FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/sample-english-1.txt'));
// $text = implode(' ', FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/sample-german-1.txt'));
// $text = implode(' ', FileSystem::readFileContents(FileSystem::FILES_DIRECTORY.'/sample-german-2.txt'));

$text = strtolower($text);

$phrases = $generator->generateKeywords($text);

// print_r($phrase);
// print_r($phrases);

$uniquePhrases = array_values(array_unique($phrases, SORT_REGULAR));
// print_r($uniquePhrases);

foreach ($generator->getVocabulary() as $word) {
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
        $phraseScore += $generator->getScores()[array_search($word, $generator->getVocabulary())];
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
