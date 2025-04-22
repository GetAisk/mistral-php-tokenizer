<?php

namespace Mistral\Tokenizer;

/**
 * Utility functions for tokenizers
 */
class Utils
{
    /**
     * Check if a byte is a continuation byte in UTF-8
     *
     * @param int $byte The byte to check
     * @return bool True if the byte is a continuation byte
     */
    public static function isContinuationByte(int $byte): bool
    {
        return ($byte & 0xC0) === 0x80;
    }

    /**
     * Check if string position is at the boundary of a UTF-8 character
     * 
     * @param string $string The string to check
     * @param int $pos The position to check
     * @return bool True if the position is at a character boundary
     */
    public static function isCharBoundary(string $string, int $pos): bool
    {
        if ($pos <= 0 || $pos >= strlen($string)) {
            return true;
        }
        
        $byte = ord($string[$pos]);
        return !self::isContinuationByte($byte);
    }

    /**
     * Split a string into UTF-8 characters
     *
     * @param string $string The string to split
     * @return array<string> The UTF-8 characters
     */
    public static function splitUtf8Characters(string $string): array
    {
        return preg_match_all('/./us', $string, $matches) ? $matches[0] : [];
    }

    /**
     * Converts a string to an array of bytes
     *
     * @param string $string The string to convert
     * @return array<int> The bytes
     */
    public static function stringToBytes(string $string): array
    {
        $bytes = [];
        for ($i = 0; $i < strlen($string); $i++) {
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }

    /**
     * Converts an array of bytes to a string
     *
     * @param array<int> $bytes The bytes to convert
     * @return string The resulting string
     */
    public static function bytesToString(array $bytes): string
    {
        return implode('', array_map('chr', $bytes));
    }

    /**
     * Encode bytes to base64
     *
     * @param array<int> $bytes The bytes to encode
     * @return string The base64 encoded string
     */
    public static function encodeBase64(array $bytes): string
    {
        return base64_encode(self::bytesToString($bytes));
    }

    /**
     * Decode base64 to bytes
     *
     * @param string $base64 The base64 encoded string
     * @return array<int> The decoded bytes
     */
    public static function decodeBase64(string $base64): array
    {
        return self::stringToBytes(base64_decode($base64));
    }

    /**
     * Normalizes a string by handling unicode characters
     * 
     * @param string $text The text to normalize
     * @return string The normalized text
     */
    public static function normalizeString(string $text): string
    {
        // PHP doesn't have a direct equivalent to Python's NFKC normalization,
        // but we can use the Normalizer class if the intl extension is available
        if (extension_loaded('intl') && class_exists('\Normalizer')) {
            return \Normalizer::normalize($text, \Normalizer::FORM_KC);
        }
        
        // Fallback to a basic normalization
        return $text;
    }
}