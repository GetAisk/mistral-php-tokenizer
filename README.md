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

use Mistral\Tokenizer\TokenizerFactory;

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

This implementation provides a simplified tokenizer for Mistral models. The current version uses a character-based tokenization for testing purposes. In a production environment, you would need to implement the full BPE algorithm with the model's merges.

## Testing

Run the tests with PHPUnit:

```bash
./vendor/bin/phpunit
```

## License

MIT