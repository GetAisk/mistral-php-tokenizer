# Tokenizer Compatibility Testing

This document explains how to run compatibility tests between the PHP and Python tokenizer implementations.

## Overview

The Mistral tokenizers should produce identical results for the same input text. The compatibility tests ensure that the output of the PHP tokenizer matches the output of the Python tokenizer for various test cases.

## Running Tests Locally

### Prerequisites

1. Both repositories cloned side by side:
   ```
   /path/to/repos/
   ├── mistral-php-tokenizer/
   └── mistral-common/
   ```

2. PHP 8.0+ with Composer dependencies installed
   ```bash
   cd mistral-php-tokenizer
   composer install
   ```

3. Python 3.8+ with Poetry and Python dependencies installed
   ```bash
   cd mistral-common
   pip install poetry
   poetry install
   ```

### Running the Test

From the root of the `mistral-php-tokenizer` repository, run:

```bash
chmod +x bin/run-compatibility-test.sh
./bin/run-compatibility-test.sh
```

This script will:
1. Create a test directory structure
2. Generate test cases
3. Run both tokenizers on the test cases
4. Compare the results
5. Report any differences found

## Troubleshooting

If the tests fail with mismatched tokens, check for:

1. **Different tokenizer versions**: Make sure both tokenizers are using the same Tekken model version
2. **Special token handling**: The Python tokenizer might automatically add BOS/EOS tokens
3. **UTF-8 handling**: Check for differences in how multi-byte characters are processed
4. **Whitespace handling**: Ensure both tokenizers handle whitespace consistently

## GitHub Workflow

A GitHub Actions workflow runs these compatibility tests automatically on pull requests and pushes to the main branch. The workflow:

1. Checks out both repositories
2. Sets up PHP and Python environments
3. Installs dependencies
4. Runs the compatibility tests
5. Uploads the test results as artifacts

To view the results of the GitHub Actions workflow, go to the "Actions" tab in the GitHub repository and select the "Tokenizer Compatibility Test" workflow.