# Mistral PHP Tokenizer

A PHP implementation of the Mistral tokenizers, focusing on the Tekken tokenizer.

Developed by [Aisk](https://getaisk.com).

## Installation

```bash
composer require aisk/mistral-tokenizer
```

## Features

- Text tokenization and detokenization
- Token counting
- Batch processing
- Special token handling
- Unicode character support

## Usage

### Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use Aisk\Tokenizer\TokenizerFactory;

// Create a tokenizer factory
$factory = new TokenizerFactory();

// Get the Tekken tokenizer
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
```

### Available Tokenizers

- Tekken Tokenizer: A byte-pair encoding tokenizer optimized for Mistral AI models
  - Supports both '240718' and '240911' versions

### Batch Processing

```php
<?php

$texts = [
    'Hello, world!',
    'How are you?',
    'Tokenizer test.'
];

// Encode in batch
$tokensBatch = $tokenizer->encodeBatch($texts);

// Decode in batch
$decodedTexts = $tokenizer->decodeBatch($tokensBatch);
```

### Command Line Tool

The package includes a command line tool for tokenizing text:

```bash
# Tokenize text from the command line
./vendor/bin/tokenize "Hello, world!"

# Specify a tokenizer version
./vendor/bin/tokenize -v 240718 "Hello, world!"

# Tokenize text from a file
./vendor/bin/tokenize -f input.txt

# Only show the token count
./vendor/bin/tokenize -c "Hello, world!"
```

## Implementation Notes

This implementation provides a full Byte Pair Encoding (BPE) tokenizer for Mistral models. It includes:

1. Automatic construction of BPE merges from vocabulary
2. Efficient implementation of the BPE algorithm
3. Proper handling of special tokens
4. Fallback to character-based tokenization for testing purposes when no vocabulary is available

The tokenizer automatically builds the BPE merge table from the vocabulary, identifying which token pairs should be merged in which order based on their priority in the vocabulary.

## Testing

Run the tests with PHPUnit:

```bash
./vendor/bin/phpunit
```

## License

MIT