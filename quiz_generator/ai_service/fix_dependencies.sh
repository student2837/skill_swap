#!/bin/bash

# Script to fix dependency conflicts by reinstalling with correct versions

echo "Fixing dependency conflicts..."
echo "This will reinstall packages with compatible versions"
echo ""

cd "$(dirname "$0")"

# Activate virtual environment if it exists
if [ -d "venv" ]; then
    source venv/bin/activate
    echo "✅ Virtual environment activated"
else
    echo "Creating virtual environment..."
    python3 -m venv venv
    source venv/bin/activate
fi

# Uninstall conflicting packages
echo "Uninstalling old packages..."
pip uninstall -y langchain langchain-core langgraph langsmith langchain-openai langchain-community 2>/dev/null

# Install compatible versions
echo "Installing compatible versions..."
pip install --upgrade pip
pip install -r requirements.txt

echo ""
echo "✅ Dependencies fixed!"
echo ""
echo "Next steps:"
echo "1. Set API key: export OPENAI_API_KEY='your-openai-api-key'"
echo "   Or create .env file with: OPENAI_API_KEY=your-openai-api-key"
echo "2. Test: python test_config.py"
echo "3. Start: python -m uvicorn main:app --reload --host 127.0.0.1 --port 8001"
