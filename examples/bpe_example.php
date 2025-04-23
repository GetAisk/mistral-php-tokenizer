<?php

// For local development
require_once __DIR__ . '/../src/TokenizerInterface.php';
require_once __DIR__ . '/../src/AbstractTokenizer.php';
require_once __DIR__ . '/../src/Utils.php';
require_once __DIR__ . '/../src/TekkenTokenizer.php';
require_once __DIR__ . '/../src/TokenizerFactory.php';

use Aisk\Tokenizer\TekkenTokenizer;

// This example demonstrates the BPE algorithm by creating a simple
// vocabulary and showing how it merges tokens

// Create a minimal vocabulary with some bytes and common merges
$vocab = [];

// Add single byte tokens (0-255)
for ($i = 0; $i < 256; $i++) {
    $vocab[chr($i)] = $i;
}

// Add some example merges
// These represent commonly occurring byte pairs
$vocab['th'] = 256;        // 'th' is common in English
$vocab['he'] = 257;        // 'he' is common
$vocab['er'] = 258;        // 'er' is common
$vocab['in'] = 259;        // 'in' is common
$vocab['the'] = 260;       // 'the' from merging 'th' + 'e'
$vocab['ing'] = 261;       // 'ing' is common
$vocab['tion'] = 262;      // 'tion' is common
$vocab['and'] = 263;       // 'and' is common
$vocab['for'] = 264;       // 'for' is common

// Create a tokenizer with our vocabulary
$tokenizer = new TekkenTokenizer(
    $vocab,          // Our custom vocabulary
    'pattern',       // Regex pattern (not used in our example)
    300,             // Vocab size
    5,               // Number of special tokens
    'example'        // Version
);

// Define special token positions
$tokenizer->buildBpeMerges();

// Test tokenization with our vocabulary
$texts = [
    'the',
    'there',
    'the cat in the hat',
    'testing',
    'and for the information'
];

// Display BPE merges
echo "BPE Merges:\n";
$merges = $tokenizer->getBpeMerges();
foreach ($merges as $index => $merge) {
    echo "  " . ($index + 1) . ". Merge '" . bin2hex($merge[0]) . "' + '" . bin2hex($merge[1]) . "'\n";
}
echo "\n";

// Test each text
foreach ($texts as $text) {
    // Tokenize the text
    $tokens = $tokenizer->encode($text);
    
    // Display token IDs
    echo "Text: '{$text}'\n";
    echo "  Token IDs: " . implode(', ', $tokens) . "\n";
    
    // Display token contents
    $tokenStrings = [];
    foreach ($tokens as $token) {
        if ($token < 5) { // Special token
            $tokenStrings[] = "<special:{$token}>";
        } else {
            $tokenId = $token - 5; // Adjust for special tokens
            foreach ($vocab as $str => $id) {
                if ($id === $tokenId) {
                    $tokenStrings[] = $str;
                    break;
                }
            }
        }
    }
    
    echo "  Token strings: " . implode(' | ', array_map(function($t) { 
        return "'" . ($t === "\n" ? "\\n" : ($t === "\t" ? "\\t" : $t)) . "'"; 
    }, $tokenStrings)) . "\n";
    
    // Decode back to text
    $decoded = $tokenizer->decode($tokens);
    echo "  Decoded: '{$decoded}'\n\n";
}