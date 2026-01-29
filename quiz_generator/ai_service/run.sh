#!/bin/bash

# AI Quiz Generator Service Startup Script
# This script must be run from the project root (quiz_generator/)

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Get the project root (parent of ai_service)
PROJECT_ROOT="$( cd "$SCRIPT_DIR/.." && pwd )"

# Change to project root
cd "$PROJECT_ROOT"

echo "Starting AI Quiz Generator Service..."
echo "Project root: $PROJECT_ROOT"

# Check if API key is set (OpenAI)
if [ -z "$OPENAI_API_KEY" ]; then
    echo "Warning: OPENAI_API_KEY environment variable is not set."
    echo "Please set it before running the service:"
    echo "  export OPENAI_API_KEY=your_openai_api_key_here"
    echo ""
fi

# Check Python version
python_version=$(python3 --version 2>&1 | awk '{print $2}')
echo "Python version: $python_version"

# Install dependencies if needed
if [ ! -d "ai_service/venv" ]; then
    echo "Creating virtual environment..."
    python3 -m venv ai_service/venv
fi

echo "Activating virtual environment..."
source ai_service/venv/bin/activate

echo "Installing dependencies..."
pip install -r ai_service/requirements.txt

echo "Starting FastAPI server..."
echo "Service will be available at http://localhost:8000"
echo "API docs available at http://localhost:8000/docs"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

# Run uvicorn from project root with package path
uvicorn ai_service.main:app --host 0.0.0.0 --port 8000 --reload
