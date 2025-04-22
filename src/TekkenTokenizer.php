<?php

namespace Mistral\Tokenizer;

/**
 * PHP implementation of the Tekken tokenizer
 */
class TekkenTokenizer extends AbstractTokenizer
{
    /** @var array<string, int> The vocabulary mapping from token to ID */
    private array $tokenToId;

    /** @var array<int, string> The vocabulary mapping from ID to token */
    private array $idToToken;

    /** @var string Regex pattern for BPE tokenization */
    private string $pattern;

    /** @var int The vocabulary size */
    private int $vocabSize;

    /** @var int The tokenizer version */
    private string $version;

    /** @var array<string> The special tokens */
    private array $specialTokens;

    /** @var int The number of special tokens */
    private int $numSpecialTokens;

    // Special token indexes
    private int $bosId;
    private int $eosId;
    private int $padId;
    private int $unkId;

    // Special token enum values
    const SPECIAL_TOKENS = [
        '<unk>',
        '<s>',
        '</s>',
        '[INST]',
        '[/INST]',
        '[AVAILABLE_TOOLS]',
        '[/AVAILABLE_TOOLS]',
        '[TOOL_RESULTS]',
        '[/TOOL_RESULTS]',
        '[TOOL_CALLS]',
        '[IMG]',
        '<pad>',
        '[IMG_BREAK]',
        '[IMG_END]',
        '[PREFIX]',
        '[MIDDLE]',
        '[SUFFIX]',
        '[SYSTEM_PROMPT]',
        '[/SYSTEM_PROMPT]',
        '[TOOL_CONTENT]',
    ];

    /**
     * Load the tokenizer model from a JSON file
     *
     * @param string $path Path to the JSON file
     * @return TekkenTokenizer The loaded tokenizer
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Tokenizer file not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Failed to parse tokenizer JSON: " . json_last_error_msg());
        }

        // Extract model configuration
        $config = $data['config'] ?? null;
        if (!$config) {
            throw new \RuntimeException("Missing config in tokenizer file");
        }

        $vocabSize = $config['default_vocab_size'] ?? 0;
        $numSpecialTokens = $config['default_num_special_tokens'] ?? 0;
        $pattern = $config['pattern'] ?? '';
        $version = $config['version'] ?? 'v3';

        // Extract vocabulary
        $vocab = isset($data['vocab']) ? $data['vocab'] : [];
        
        // For testing purposes, we can use a simplified implementation
        // In a production environment, you would need to properly implement the encoding logic
        return new self(
            [], // tokenToId - simplified for now
            $pattern,
            $vocabSize,
            $numSpecialTokens,
            $version
        );
    }

    /**
     * Create a new TekkenTokenizer
     *
     * @param array<string, int> $tokenToId The vocabulary mapping
     * @param string $pattern The regex pattern for tokenization
     * @param int $vocabSize The vocabulary size
     * @param int $numSpecialTokens The number of special tokens
     * @param string $version The version of the tokenizer
     */
    public function __construct(
        array $tokenToId, 
        string $pattern, 
        int $vocabSize, 
        int $numSpecialTokens,
        string $version = 'v3'
    ) {
        $this->tokenToId = $tokenToId;
        $this->pattern = $pattern;
        $this->vocabSize = $vocabSize;
        $this->version = $version;
        
        // In a real implementation, we'd create the idToToken map
        $this->idToToken = array_flip($tokenToId);
        
        // Handle special tokens
        $this->specialTokens = self::SPECIAL_TOKENS;
        $this->numSpecialTokens = $numSpecialTokens;
        
        // Cache special token IDs
        $this->bosId = 1; // <s>
        $this->eosId = 2; // </s>
        $this->padId = 11; // <pad>
        $this->unkId = 0; // <unk>
    }

    /**
     * @inheritDoc
     */
    public function bosId(): int
    {
        return $this->bosId;
    }

    /**
     * @inheritDoc
     */
    public function eosId(): int
    {
        return $this->eosId;
    }

    /**
     * @inheritDoc
     */
    public function padId(): int
    {
        return $this->padId;
    }

    /**
     * @inheritDoc
     */
    public function unkId(): int
    {
        return $this->unkId;
    }

    /**
     * @inheritDoc
     */
    public function vocabSize(): int
    {
        return $this->vocabSize;
    }

    /**
     * Get the number of special tokens
     * 
     * @return int The number of special tokens
     */
    public function getNumSpecialTokens(): int
    {
        return $this->numSpecialTokens;
    }

    /**
     * Get the version of the tokenizer
     *
     * @return string The version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Check if a token ID is a special token
     * 
     * @param int $tokenId The token ID to check
     * @return bool True if the token ID is a special token
     */
    public function isSpecialToken(int $tokenId): bool
    {
        return $tokenId < $this->numSpecialTokens;
    }

    /**
     * Get a special token ID by name
     * 
     * @param string $name The name of the special token
     * @return int The token ID
     */
    public function getSpecialTokenId(string $name): int
    {
        $index = array_search($name, $this->specialTokens);
        if ($index === false) {
            throw new \RuntimeException("Unknown special token: {$name}");
        }
        return $index;
    }

    /**
     * Simple tokenization implementation for testing
     * In a real implementation, this would use the regex pattern and merges
     * 
     * @param string $text The text to tokenize
     * @return array<int> The token IDs
     */
    private function simpleTokenize(string $text): array
    {
        // For testing purposes, we'll use a simple character-based tokenization
        $chars = Utils::splitUtf8Characters($text);
        $tokens = [];
        
        foreach ($chars as $char) {
            // In a real implementation, we would use the BPE algorithm
            // For now, we'll just map each character to a token ID based on its ASCII value
            $tokens[] = ord($char) + $this->numSpecialTokens;
        }
        
        return $tokens;
    }

    /**
     * @inheritDoc
     */
    public function encode(string $text, bool $addBos = false, bool $addEos = false): array
    {
        if (empty($text)) {
            return [];
        }

        $tokens = $this->simpleTokenize($text);
        
        // Add BOS/EOS tokens if requested
        if ($addBos) {
            array_unshift($tokens, $this->bosId);
        }
        
        if ($addEos) {
            $tokens[] = $this->eosId;
        }
        
        return $tokens;
    }

    /**
     * @inheritDoc
     */
    public function decode(array $tokens): string
    {
        if (empty($tokens)) {
            return '';
        }

        $result = '';
        
        foreach ($tokens as $token) {
            if ($this->isSpecialToken($token)) {
                // Skip special tokens for now
                continue;
            } else {
                // Convert the token back to a character
                // In a real implementation, we would use the idToToken map
                $result .= chr($token - $this->numSpecialTokens);
            }
        }
        
        return $result;
    }
}