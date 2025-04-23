# Byte Pair Encoding (BPE) Algorithm

The Mistral PHP Tokenizer implements the Byte Pair Encoding (BPE) algorithm, which is commonly used in large language models like Mistral.

## Overview

Byte Pair Encoding is a data compression technique that:

1. Starts with a vocabulary of individual bytes (0-255)
2. Iteratively finds the most frequent pair of adjacent tokens and replaces them with a new token
3. Continues this process until a desired vocabulary size is reached

## Implementation Details

Our implementation includes:

### 1. Vocabulary Loading

The tokenizer loads vocabulary from Mistral model files, which contain:
- Token bytes (base64 encoded)
- Token ranks (priorities)
- Token strings (human-readable representation, optional)

### 2. BPE Merge Construction

Instead of storing explicit merges, our implementation:

1. Analyzes the vocabulary to find multi-byte tokens
2. For each multi-byte token, identifies potential token pairs that could be merged to form it
3. Creates a merge table ordered by token priority
4. Uses this merge table during tokenization

This approach reconstructs the merge operations from the vocabulary alone, without needing explicit merge rules.

### 3. Tokenization Process

When tokenizing a text string:

1. Start by converting the string to individual bytes
2. Repeatedly:
   a. Find all adjacent pairs in the token list
   b. Find the highest priority merge in our merge table
   c. Apply that merge, replacing the pair with the merged token
   d. Continue until no more merges can be applied
3. Convert the resulting tokens to token IDs
4. Add special tokens (BOS/EOS) as requested

### 4. Detokenization

When decoding token IDs back to text:

1. Convert each token ID to its corresponding token bytes
2. Skip special tokens
3. Concatenate all token bytes to form the final string

## Example

Consider a simple example with the text "the cat":

1. Initial byte tokenization: ['t', 'h', 'e', ' ', 'c', 'a', 't']
2. Apply merge ('t', 'h') → 'th': ['th', 'e', ' ', 'c', 'a', 't']
3. Apply merge ('th', 'e') → 'the': ['the', ' ', 'c', 'a', 't'] 
4. No more eligible merges, convert to token IDs: [260, 32, 99, 97, 116]

## Performance Considerations

The current implementation prioritizes correctness and readability over maximum performance. For production use with large texts, consider:

1. Implementing caching of frequently tokenized substrings
2. Using more efficient data structures for the merge search
3. Pre-computing common token sequences

## References

For more information on BPE:
- [Neural Machine Translation of Rare Words with Subword Units](https://arxiv.org/abs/1508.07909) - Original BPE paper
- [GPT-2: Language Models are Unsupervised Multitask Learners](https://cdn.openai.com/better-language-models/language_models_are_unsupervised_multitask_learners.pdf) - Describes BPE usage in modern language models