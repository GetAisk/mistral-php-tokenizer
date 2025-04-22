<?php

namespace Aisk\Tokenizer;

/**
 * Abstract base class for tokenizers implementing common functionality
 */
abstract class AbstractTokenizer implements TokenizerInterface
{
    /**
     * @inheritDoc
     */
    public function encodeBatch(array $texts): array
    {
        return array_map(fn (string $text) => $this->encode($text), $texts);
    }

    /**
     * @inheritDoc
     */
    public function decodeBatch(array $tokensBatch): array
    {
        return array_map(fn (array $tokens) => $this->decode($tokens), $tokensBatch);
    }

    /**
     * Count the number of tokens in a text
     *
     * @param string $text The text to count tokens for
     * @return int Number of tokens
     */
    public function countTokens(string $text): int
    {
        return count($this->encode($text));
    }
}