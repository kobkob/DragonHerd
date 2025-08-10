#!/bin/bash

echo "=========================================="
echo "Testing DragonHerd on Multiple PHP Versions"  
echo "=========================================="
echo

# Test PHP 8.1
echo "🧪 Testing PHP 8.1..."
echo "--------------------------------------"
if php8.1 ./vendor/bin/phpunit || [ $? -eq 1 ]; then
    echo "✅ PHP 8.1: Tests passed"
else
    echo "❌ PHP 8.1: Tests failed"
fi
echo

# Test PHP 8.2
echo "🧪 Testing PHP 8.2..."
echo "--------------------------------------"
if php8.2 ./vendor/bin/phpunit || [ $? -eq 1 ]; then
    echo "✅ PHP 8.2: Tests passed"
else
    echo "❌ PHP 8.2: Tests failed"
fi
echo

# Test PHP 8.3
echo "🧪 Testing PHP 8.3..."
echo "--------------------------------------"
if php8.3 ./vendor/bin/phpunit || [ $? -eq 1 ]; then
    echo "✅ PHP 8.3: Tests passed"
else
    echo "❌ PHP 8.3: Tests failed"
fi
echo

echo "=========================================="
echo "Multi-version testing complete!"
echo "=========================================="
