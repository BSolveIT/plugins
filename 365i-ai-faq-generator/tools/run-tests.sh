#!/bin/bash

# Script to run all tests for the 365i AI FAQ Generator plugin
# This script runs both syntax checks and component tests

echo "========================================"
echo "Running 365i AI FAQ Generator Test Suite"
echo "========================================"
echo ""

# 1. Run syntax check
echo "Step 1: Running PHP Syntax Check"
echo "--------------------------------"
php ./syntax-check.php
SYNTAX_RESULT=$?

echo ""

# 2. Run component tests
echo "Step 2: Running Component Tests"
echo "-------------------------------"
php ./test-components.php
COMPONENT_RESULT=$?

echo ""
echo "========================================"
echo "Test Results Summary"
echo "========================================"

# Check results
if [ $SYNTAX_RESULT -eq 0 ] && [ $COMPONENT_RESULT -eq 0 ]; then
    echo "✅ All tests passed successfully!"
    echo "The plugin is ready for deployment."
    exit 0
else
    echo "❌ Some tests failed. Please review the output above."
    exit 1
fi