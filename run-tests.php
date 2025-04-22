<?php

// Include necessary files
require_once __DIR__ . '/src/TokenizerInterface.php';
require_once __DIR__ . '/src/AbstractTokenizer.php';
require_once __DIR__ . '/src/Utils.php';
require_once __DIR__ . '/src/TekkenTokenizer.php';
require_once __DIR__ . '/src/TokenizerFactory.php';

use Mistral\Tokenizer\TekkenTokenizer;
use Mistral\Tokenizer\Utils;

echo "Running tests for Mistral PHP Tokenizer\n";
echo "======================================\n\n";

// Initialize the tokenizer
$tokenizer = new TekkenTokenizer(
    [], // tokenToId - simplified for tests
    'pattern',
    32000, // vocabSize
    20, // numSpecialTokens
    'v3' // version
);

// Test vocab size
echo "Testing vocabSize(): ";
$result = ($tokenizer->vocabSize() === 32000);
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test encode and decode
echo "Testing encode() and decode(): ";
$text = 'Hello, world!';
$tokens = $tokenizer->encode($text);
$decoded = $tokenizer->decode($tokens);
$result = ($decoded === $text);
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test encode batch
echo "Testing encodeBatch(): ";
$texts = [
    'Hello, world!',
    'How are you?',
    'Tokenizer test.'
];
$tokensBatch = $tokenizer->encodeBatch($texts);
$result = (count($tokensBatch) === count($texts));
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test decode batch
echo "Testing decodeBatch(): ";
$decodedTexts = $tokenizer->decodeBatch($tokensBatch);
$result = ($texts === $decodedTexts);
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test count tokens
echo "Testing countTokens(): ";
$count = $tokenizer->countTokens($text);
$result = (count($tokens) === $count);
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test empty string
echo "Testing empty string: ";
$emptyTokens = $tokenizer->encode('');
$emptyDecoded = $tokenizer->decode($emptyTokens);
$result = (count($emptyTokens) === 0 && $emptyDecoded === '');
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test special token access
echo "Testing special token access: ";
$result = (
    is_int($tokenizer->bosId()) &&
    is_int($tokenizer->eosId()) &&
    is_int($tokenizer->padId()) &&
    is_int($tokenizer->unkId())
);
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test Utils
echo "Testing Utils::splitUtf8Characters(): ";
$text = 'Hello, 世界!';
$chars = Utils::splitUtf8Characters($text);
$result = (
    count($chars) === 10 &&
    $chars[0] === 'H' &&
    $chars[7] === '世' &&
    $chars[8] === '界'
);
echo $result ? "PASSED" : "FAILED";
echo "\n";

echo "Testing Utils::stringToBytes() and bytesToString(): ";
$bytes = Utils::stringToBytes('ABC');
$string = Utils::bytesToString([65, 66, 67]);
$result = ($bytes === [65, 66, 67] && $string === 'ABC');
echo $result ? "PASSED" : "FAILED";
echo "\n";

// Test adding BOS/EOS
echo "Testing adding BOS/EOS tokens: ";
$text = 'Hello';

// With BOS
$tokens = $tokenizer->encode($text, true, false);
$hasBos = ($tokens[0] === $tokenizer->bosId());

// With EOS
$tokens = $tokenizer->encode($text, false, true);
$hasEos = ($tokens[count($tokens) - 1] === $tokenizer->eosId());

// With both
$tokens = $tokenizer->encode($text, true, true);
$hasBoth = (
    $tokens[0] === $tokenizer->bosId() &&
    $tokens[count($tokens) - 1] === $tokenizer->eosId()
);

$result = ($hasBos && $hasEos && $hasBoth);
echo $result ? "PASSED" : "FAILED";
echo "\n";

echo "\nAll tests completed.\n";