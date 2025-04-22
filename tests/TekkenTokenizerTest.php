<?php

namespace Aisk\Tokenizer\Tests;

use Aisk\Tokenizer\TekkenTokenizer;
use Aisk\Tokenizer\TokenizerFactory;
use Aisk\Tokenizer\Utils;
use PHPUnit\Framework\TestCase;

class TekkenTokenizerTest extends TestCase
{
    private TekkenTokenizer $tokenizer;

    protected function setUp(): void
    {
        // Direct initialization for testing
        $this->tokenizer = new TekkenTokenizer(
            [], // tokenToId - simplified for tests
            'pattern',
            32000, // vocabSize
            20, // numSpecialTokens
            'v3' // version
        );
    }

    public function testVocabSize(): void
    {
        $this->assertEquals(32000, $this->tokenizer->vocabSize());
    }

    public function testEncodeAndDecode(): void
    {
        $text = 'Hello, world!';
        $tokens = $this->tokenizer->encode($text);
        $this->assertNotEmpty($tokens);
        
        $decoded = $this->tokenizer->decode($tokens);
        $this->assertEquals($text, $decoded);
    }

    public function testEncodeBatch(): void
    {
        $texts = [
            'Hello, world!',
            'How are you?',
            'Tokenizer test.'
        ];
        
        $tokensBatch = $this->tokenizer->encodeBatch($texts);
        $this->assertCount(count($texts), $tokensBatch);
        
        foreach ($tokensBatch as $tokens) {
            $this->assertNotEmpty($tokens);
        }
    }

    public function testDecodeBatch(): void
    {
        $texts = [
            'Hello, world!',
            'How are you?',
            'Tokenizer test.'
        ];
        
        $tokensBatch = $this->tokenizer->encodeBatch($texts);
        $decodedTexts = $this->tokenizer->decodeBatch($tokensBatch);
        
        $this->assertEquals($texts, $decodedTexts);
    }

    public function testCountTokens(): void
    {
        $text = 'Hello, world!';
        $tokens = $this->tokenizer->encode($text);
        $count = $this->tokenizer->countTokens($text);
        
        $this->assertEquals(count($tokens), $count);
    }

    public function testEmptyString(): void
    {
        $text = '';
        $tokens = $this->tokenizer->encode($text);
        $this->assertEmpty($tokens);
        
        $decoded = $this->tokenizer->decode($tokens);
        $this->assertEquals($text, $decoded);
    }

    public function testSpecialTokenAccess(): void
    {
        $this->assertIsInt($this->tokenizer->bosId());
        $this->assertIsInt($this->tokenizer->eosId());
        $this->assertIsInt($this->tokenizer->padId());
        $this->assertIsInt($this->tokenizer->unkId());
    }

    public function testUtils(): void
    {
        // Test UTF-8 split
        $text = 'Hello, 世界!';
        $chars = Utils::splitUtf8Characters($text);
        $this->assertEquals(['H', 'e', 'l', 'l', 'o', ',', ' ', '世', '界', '!'], $chars);
        
        // Test string to bytes
        $bytes = Utils::stringToBytes('ABC');
        $this->assertEquals([65, 66, 67], $bytes);
        
        // Test bytes to string
        $string = Utils::bytesToString([65, 66, 67]);
        $this->assertEquals('ABC', $string);
    }

    public function testAddingBosEos(): void
    {
        $text = 'Hello';
        
        // With BOS
        $tokens = $this->tokenizer->encode($text, true, false);
        $this->assertEquals($this->tokenizer->bosId(), $tokens[0]);
        
        // With EOS
        $tokens = $this->tokenizer->encode($text, false, true);
        $this->assertEquals($this->tokenizer->eosId(), $tokens[count($tokens) - 1]);
        
        // With both BOS and EOS
        $tokens = $this->tokenizer->encode($text, true, true);
        $this->assertEquals($this->tokenizer->bosId(), $tokens[0]);
        $this->assertEquals($this->tokenizer->eosId(), $tokens[count($tokens) - 1]);
    }
}