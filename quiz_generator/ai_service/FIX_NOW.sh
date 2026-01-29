#!/bin/bash

# Quick fix script for dependency conflicts
# Run this to fix all dependency issues

cd "$(dirname "$0")"

echo "🔧 Fixing dependency conflicts..."
echo ""

# Activate venv
if [ -d "venv" ]; then
    source venv/bin/activate
else
    echo "Creating virtual environment..."
    python3 -m venv venv
    source venv/bin/activate
fi

echo "📦 Uninstalling conflicting packages..."
pip uninstall -y langchain langchain-core langgraph langsmith langchain-openai langchain-community langchain-google-genai pydantic 2>/dev/null

echo ""
echo "📥 Installing compatible versions..."
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
