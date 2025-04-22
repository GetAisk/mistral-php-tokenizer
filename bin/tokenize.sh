#!/bin/bash

# Find the PHP binary
PHP_BIN=$(which php)
if [ -z "$PHP_BIN" ]; then
  echo "Error: PHP not found in PATH"
  exit 1
fi

# Find the script directory
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

# Execute the tokenize script
$PHP_BIN "$DIR/tokenize" "$@"