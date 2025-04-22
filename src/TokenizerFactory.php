<?php

namespace Mistral\Tokenizer;

/**
 * Factory for creating tokenizers
 */
class TokenizerFactory
{
    /**
     * Data directory where tokenizer files are stored
     */
    private string $dataDir;

    /**
     * Create a new TokenizerFactory
     *
     * @param string|null $dataDir Directory where tokenizer files are stored
     */
    public function __construct(?string $dataDir = null)
    {
        if ($dataDir === null) {
            $this->dataDir = dirname(__DIR__) . '/data';
        } else {
            $this->dataDir = $dataDir;
        }
    }

    /**
     * Get the Tekken tokenizer
     *
     * @param string $version Version of the tokenizer (240718 or 240911)
     * @return TekkenTokenizer The Tekken tokenizer
     */
    public function getTekkenTokenizer(string $version = '240911'): TekkenTokenizer
    {
        $validVersions = ['240718', '240911'];
        if (!in_array($version, $validVersions)) {
            throw new \InvalidArgumentException(
                "Invalid Tekken tokenizer version: {$version}. Valid versions are: " . implode(', ', $validVersions)
            );
        }

        $path = "{$this->dataDir}/tekken_{$version}.json";
        return TekkenTokenizer::fromFile($path);
    }

    /**
     * Get the data directory
     *
     * @return string The data directory
     */
    public function getDataDir(): string
    {
        return $this->dataDir;
    }

    /**
     * Set the data directory
     *
     * @param string $dataDir The data directory
     * @return self
     */
    public function setDataDir(string $dataDir): self
    {
        $this->dataDir = $dataDir;
        return $this;
    }
}