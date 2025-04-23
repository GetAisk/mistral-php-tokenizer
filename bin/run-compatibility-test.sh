#!/bin/bash

# Script to run compatibility tests between PHP and Python tokenizers locally
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_DIR="$(dirname "$SCRIPT_DIR")"
MISTRAL_COMMON_DIR="${REPO_DIR}/../mistral-common"
TEST_DIR="${REPO_DIR}/compatibility-test"

echo "=== Compatibility Test Setup ==="
echo "PHP Tokenizer: ${REPO_DIR}"
echo "Python Tokenizer: ${MISTRAL_COMMON_DIR}"
echo "Test Directory: ${TEST_DIR}"

# Check if mistral-common exists
if [ ! -d "$MISTRAL_COMMON_DIR" ]; then
  echo "Error: mistral-common directory not found at ${MISTRAL_COMMON_DIR}"
  echo "Please make sure you have both repositories cloned side by side:"
  echo "- mistral-php-tokenizer"
  echo "- mistral-common"
  exit 1
fi

# Create test directory structure
mkdir -p "${TEST_DIR}/fixtures"
mkdir -p "${TEST_DIR}/results"

# Copy test fixtures
cp "${REPO_DIR}/data/"*.json "${TEST_DIR}/fixtures/"
if [ -d "${MISTRAL_COMMON_DIR}/tests/data/samples" ]; then
  cp -r "${MISTRAL_COMMON_DIR}/tests/data/samples" "${TEST_DIR}/fixtures/samples"
fi

# Create PHP test script
cat > "${TEST_DIR}/generate_test_cases.php" << 'EOF'
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Aisk\Tokenizer\TokenizerFactory;

// Define test cases
$testCases = [
    "Simple text" => "Hello, world!",
    "Multi-byte characters" => "Hello, 世界!",
    "Empty string" => "",
    "Whitespace" => "   ",
    "Numbers" => "123456789",
    "Special characters" => "!@#$%^&*()_+-=[]{}|;':\",./<>?",
    "Multi-line text" => "Line 1\nLine 2\nLine 3",
    "JSON structure" => '{"name":"John","age":30,"city":"New York"}',
    "Code snippet" => "function hello() {\n  console.log('Hello world');\n}",
];

// Write test cases to JSON file
file_put_contents('test_cases.json', json_encode($testCases, JSON_PRETTY_PRINT));

// Create PHP tokenizer
$factory = new TokenizerFactory();
$tokenizer = $factory->getTekkenTokenizer('240911');

// Tokenize test cases
$results = [];
foreach ($testCases as $name => $text) {
    $tokens = $tokenizer->encode($text);
    $results[$name] = [
        'text' => $text,
        'tokens' => $tokens,
        'token_count' => count($tokens)
    ];
}

// Write PHP results to JSON file
file_put_contents('results/php_results.json', json_encode($results, JSON_PRETTY_PRINT));

echo "Generated PHP test results\n";
EOF

# Create Python test script
cat > "${TEST_DIR}/generate_python_results.py" << 'EOF'
import json
import sys
import os

# Add mistral_common module to path
sys.path.append(os.path.abspath(os.environ['MISTRAL_COMMON_DIR']))

from mistral_common.tokens.tokenizers.mistral import MistralTokenizer

# Load test cases
with open('test_cases.json', 'r') as f:
    test_cases = json.load(f)

# Create tokenizer (Tekken v3)
tokenizer = MistralTokenizer.v3(is_tekken=True)

# Tokenize test cases
results = {}
for name, text in test_cases.items():
    tokens = tokenizer.encode(text, add_bos=False, add_eos=False)
    results[name] = {
        'text': text,
        'tokens': tokens,
        'token_count': len(tokens)
    }

# Write Python results to JSON file
with open('results/python_results.json', 'w') as f:
    json.dump(results, f, indent=2)

print("Generated Python test results")
EOF

# Create comparison script
cat > "${TEST_DIR}/compare_results.py" << 'EOF'
import json
import sys

# Load results
with open('results/php_results.json', 'r') as f:
    php_results = json.load(f)

with open('results/python_results.json', 'r') as f:
    python_results = json.load(f)

# Compare results
all_match = True
mismatches = []

for name in php_results:
    php = php_results[name]
    python = python_results[name]
    
    tokens_match = php['tokens'] == python['tokens']
    count_match = php['token_count'] == python['token_count']
    
    if not tokens_match or not count_match:
        all_match = False
        mismatches.append({
            'name': name,
            'tokens_match': tokens_match,
            'count_match': count_match,
            'php_tokens': php['tokens'][:10],
            'python_tokens': python['tokens'][:10],
            'php_count': php['token_count'],
            'python_count': python['token_count']
        })

# Print results
print(f"\n{'=' * 50}")
print(f"COMPATIBILITY TEST RESULTS")
print(f"{'=' * 50}")

if all_match:
    print("✅ All test cases match between PHP and Python tokenizers!")
    sys.exit(0)
else:
    print(f"❌ Found {len(mismatches)} mismatching test cases:")
    for mismatch in mismatches:
        print(f"\nTest: {mismatch['name']}")
        print(f"  Tokens match: {'✅' if mismatch['tokens_match'] else '❌'}")
        print(f"  Count match: {'✅' if mismatch['count_match'] else '❌'}")
        
        if not mismatch['tokens_match']:
            print(f"  PHP tokens (first 10): {mismatch['php_tokens']}")
            print(f"  Python tokens (first 10): {mismatch['python_tokens']}")
        
        if not mismatch['count_match']:
            print(f"  PHP count: {mismatch['php_count']}")
            print(f"  Python count: {mismatch['python_count']}")
    
    sys.exit(1)
EOF

# Run PHP test generation
cd "${TEST_DIR}"
echo "Generating PHP test results..."
php generate_test_cases.php

# Run Python test generation
echo "Generating Python test results..."
MISTRAL_COMMON_DIR="${MISTRAL_COMMON_DIR}" python generate_python_results.py

# Compare results
echo "Comparing results..."
python compare_results.py

# Check exit code
if [ $? -eq 0 ]; then
  echo "✅ Compatibility test passed!"
  exit 0
else
  echo "❌ Compatibility test failed!"
  exit 1
fi