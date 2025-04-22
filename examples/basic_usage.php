<?php

// Assuming you've installed the package via Composer
// require_once 'vendor/autoload.php';

// For local development
require_once __DIR__ . '/../src/TokenizerInterface.php';
require_once __DIR__ . '/../src/AbstractTokenizer.php';
require_once __DIR__ . '/../src/Utils.php';
require_once __DIR__ . '/../src/TekkenTokenizer.php';
require_once __DIR__ . '/../src/TokenizerFactory.php';

use Mistral\Tokenizer\TokenizerFactory;

// Create a tokenizer factory
$factory = new TokenizerFactory();

try {
    // Get the Tekken tokenizer
    // Note: In a simplified implementation, we're using a character-based tokenizer for testing
    $tokenizer = $factory->getTekkenTokenizer('240911');

    // Encode a string
    $text = 'Hello, world!';
    $tokens = $tokenizer->encode($text);
    echo "Encoded '{$text}' to " . count($tokens) . " tokens: " . implode(', ', $tokens) . PHP_EOL;

    // Count tokens
    $count = $tokenizer->countTokens($text);
    echo "'{$text}' has {$count} tokens" . PHP_EOL;

    // Decode tokens back to text
    $decoded = $tokenizer->decode($tokens);
    echo "Decoded tokens back to: '{$decoded}'" . PHP_EOL;

    // Test with different text
    $texts = [
        'Hello, world!',
        'How are you?',
        'Testing the tokenizer implementation.'
    ];

    foreach ($texts as $text) {
        $tokens = $tokenizer->encode($text);
        $decoded = $tokenizer->decode($tokens);
        echo "Text: '{$text}' -> Tokens: " . count($tokens) . " -> Decoded: '{$decoded}'" . PHP_EOL;
    }

    // Test special tokens
    echo "BOS ID: " . $tokenizer->bosId() . PHP_EOL;
    echo "EOS ID: " . $tokenizer->eosId() . PHP_EOL;
    echo "PAD ID: " . $tokenizer->padId() . PHP_EOL;
    echo "UNK ID: " . $tokenizer->unkId() . PHP_EOL;

    // Encode with BOS and EOS tokens
    $tokensWithSpecial = $tokenizer->encode($text, true, true);
    echo "Tokens with BOS and EOS: " . implode(', ', $tokensWithSpecial) . PHP_EOL;

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}