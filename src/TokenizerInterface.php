<?php

namespace Mistral\Tokenizer;

/**
 * Interface for all tokenizers
 */
interface TokenizerInterface
{
    /**
     * Encode a string into a list of tokens
     *
     * @param string $text The text to encode
     * @param bool $addBos Whether to add a beginning of sequence token
     * @param bool $addEos Whether to add an end of sequence token
     * @return array<int> List of token IDs
     */
    public function encode(string $text, bool $addBos = false, bool $addEos = false): array;

    /**
     * Encode a batch of strings into a list of lists of tokens
     *
     * @param array<string> $texts The texts to encode
     * @return array<array<int>> List of lists of token IDs
     */
    public function encodeBatch(array $texts): array;

    /**
     * Decode a list of tokens into a string
     *
     * @param array<int> $tokens The token IDs to decode
     * @return string The decoded text
     */
    public function decode(array $tokens): string;

    /**
     * Decode a batch of lists of tokens into a list of strings
     *
     * @param array<array<int>> $tokensBatch The batch of token IDs to decode
     * @return array<string> The decoded texts
     */
    public function decodeBatch(array $tokensBatch): array;

    /**
     * Get the vocabulary size of the tokenizer
     *
     * @return int Vocabulary size
     */
    public function vocabSize(): int;

    /**
     * Get the ID of the beginning of sequence token
     *
     * @return int BOS token ID
     */
    public function bosId(): int;

    /**
     * Get the ID of the end of sequence token
     *
     * @return int EOS token ID
     */
    public function eosId(): int;

    /**
     * Get the ID of the padding token
     *
     * @return int PAD token ID
     */
    public function padId(): int;

    /**
     * Get the ID of the unknown token
     *
     * @return int UNK token ID
     */
    public function unkId(): int;
}