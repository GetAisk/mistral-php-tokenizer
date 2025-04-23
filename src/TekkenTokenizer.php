<?php

namespace Aisk\Tokenizer;

/**
 * PHP implementation of the Tekken tokenizer
 */
class TekkenTokenizer extends AbstractTokenizer
{
    /** @var array<string, int> The vocabulary mapping from token bytes to ID */
    private array $tokenToId;

    /** @var array<int, string> The vocabulary mapping from ID to token bytes */
    private array $idToToken;

    /** @var string Regex pattern for BPE tokenization */
    private string $pattern;

    /** @var array<array{string, string}> BPE merges sorted by priority */
    private array $bpeMerges = [];

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
        $vocabInfo = $data['vocab'] ?? [];
        $tokenToId = [];
        $merges = [];
        
        foreach ($vocabInfo as $info) {
            $tokenBytes = base64_decode($info['token_bytes']);
            $tokenId = $info['rank'];
            
            // Store the token bytes to ID mapping
            $tokenToId[$tokenBytes] = $tokenId;
        }
        
        // Create the instance
        $tokenizer = new self(
            $tokenToId,
            $pattern,
            $vocabSize,
            $numSpecialTokens,
            $version
        );
        
        // Build BPE merges
        $tokenizer->buildBpeMerges();
        
        return $tokenizer;
    }

    /**
     * Create a new TekkenTokenizer
     *
     * @param array<string, int> $tokenToId The vocabulary mapping from token bytes to ID
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
        
        // Create the idToToken map
        $this->idToToken = [];
        foreach ($this->tokenToId as $token => $id) {
            $this->idToToken[$id] = $token;
        }
        
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
     * Build BPE merges from the vocabulary
     */
    public function buildBpeMerges(): void
    {
        // Skip tokens that correspond to single bytes (0-255)
        $mergeableTokens = [];
        foreach ($this->tokenToId as $token => $id) {
            if (strlen($token) > 1) {
                $mergeableTokens[$token] = $id;
            }
        }
        
        // For each mergeable token, find the pair of tokens that could have been merged to create it
        $merges = [];
        foreach ($mergeableTokens as $token => $id) {
            // Try all possible splits
            for ($i = 1; $i < strlen($token); $i++) {
                $first = substr($token, 0, $i);
                $second = substr($token, $i);
                
                // Check if both parts are in our vocabulary
                if (isset($this->tokenToId[$first]) && isset($this->tokenToId[$second])) {
                    // Add this merge with its priority (lower rank = higher priority)
                    $merges[] = [
                        'first' => $first, 
                        'second' => $second, 
                        'result' => $token,
                        'priority' => $id
                    ];
                    break; // We found a valid split, no need to check others
                }
            }
        }
        
        // Sort merges by priority (lower rank = higher priority)
        usort($merges, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        // Store the sorted merges
        $this->bpeMerges = array_map(function($merge) {
            return [$merge['first'], $merge['second']];
        }, $merges);
    }
    
    /**
     * Byte-pair encoding algorithm implementation
     * 
     * @param string $text The text to tokenize
     * @return array<int> The token IDs
     */
    private function bpeEncode(string $text): array
    {
        if (empty($text)) {
            return [];
        }
        
        // Convert string to UTF-8 bytes
        $bytes = Utils::stringToBytes($text);
        
        // Initialize with individual bytes
        $tokens = [];
        foreach ($bytes as $byte) {
            $tokens[] = chr($byte);
        }
        
        // Apply merges until no more can be applied
        $changes = true;
        while ($changes && count($tokens) > 1) {
            $changes = false;
            
            // Find all pairs in the current token list
            $pairs = [];
            for ($i = 0; $i < count($tokens) - 1; $i++) {
                $pairs[] = [$tokens[$i], $tokens[$i + 1]];
            }
            
            // Find the highest priority merge
            $bestMerge = null;
            $bestPos = -1;
            
            // Check against our merge list (most common merges first)
            foreach ($this->bpeMerges as $mergeIndex => $merge) {
                list($first, $second) = $merge;
                
                // Look for this pair in our token list
                for ($i = 0; $i < count($pairs); $i++) {
                    if ($pairs[$i][0] === $first && $pairs[$i][1] === $second) {
                        // We found a mergeable pair
                        $bestMerge = $merge;
                        $bestPos = $i;
                        break 2; // Break out of both loops
                    }
                }
            }
            
            // Apply the best merge if found
            if ($bestMerge !== null && $bestPos >= 0) {
                list($first, $second) = $bestMerge;
                $merged = $first . $second;
                
                // Replace the pair at bestPos with the merged token
                $newTokens = array_slice($tokens, 0, $bestPos);
                $newTokens[] = $merged;
                $newTokens = array_merge($newTokens, array_slice($tokens, $bestPos + 2));
                
                $tokens = $newTokens;
                $changes = true;
            }
        }
        
        // Convert tokens to IDs
        $ids = [];
        foreach ($tokens as $token) {
            // If we have the token in our vocabulary
            if (isset($this->tokenToId[$token])) {
                $ids[] = $this->tokenToId[$token] + $this->numSpecialTokens;
            } else {
                // Fall back to byte-level encoding
                foreach (Utils::stringToBytes($token) as $byte) {
                    $byteToken = chr($byte);
                    if (isset($this->tokenToId[$byteToken])) {
                        $ids[] = $this->tokenToId[$byteToken] + $this->numSpecialTokens;
                    } else {
                        // Use the unknown token as last resort
                        $ids[] = $this->unkId;
                    }
                }
            }
        }
        
        return $ids;
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
     * @inheritDoc
     */
    public function encode(string $text, bool $addBos = false, bool $addEos = false): array
    {
        if (empty($text)) {
            return [];
        }

        // If we have BPE merges, use BPE encoding
        if (!empty($this->bpeMerges)) {
            $tokens = $this->bpeEncode($text);
        } else {
            // Fall back to a simple character-based encoding for testing
            $tokens = $this->simpleTokenize($text);
        }
        
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
     * Simple tokenization implementation for testing
     * Used as a fallback when BPE merges aren't available
     * 
     * @param string $text The text to tokenize
     * @return array<int> The token IDs
     */
    private function simpleTokenize(string $text): array
    {
        // For testing purposes, use a simple character-based tokenization
        $chars = Utils::splitUtf8Characters($text);
        $tokens = [];
        
        foreach ($chars as $char) {
            // Map each character to a token ID based on its ASCII value
            $tokens[] = ord($char) + $this->numSpecialTokens;
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
                // Skip special tokens
                continue;
            } else {
                $tokenId = $token - $this->numSpecialTokens;
                
                // Check if we have this token in our vocabulary
                if (isset($this->idToToken[$tokenId])) {
                    $result .= $this->idToToken[$tokenId];
                } else {
                    // Fall back to treating it as a byte
                    $result .= chr($tokenId);
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Get the BPE merges
     * 
     * @return array<array{string, string}> The BPE merges
     */
    public function getBpeMerges(): array
    {
        return $this->bpeMerges;
    }
}