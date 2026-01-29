#!/bin/bash

# Setup script to configure OpenAI API key

echo "Setting up OpenAI API key..."

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cat > .env << EOF
# OpenAI API Configuration
OPENAI_API_KEY=your-openai-api-key-here

# Service Configuration
HOST=127.0.0.1
PORT=8001
EOF
    echo "✅ .env file created with OpenAI API key"
else
    # Update existing .env file - remove Gemini, add OpenAI
    if grep -q "GEMINI_API_KEY" .env; then
        # Remove Gemini key
        sed -i.bak '/^GEMINI_API_KEY=/d' .env
        sed -i.bak '/^# Google Gemini/d' .env
        echo "✅ Removed GEMINI_API_KEY from .env file"
    fi
    
    if grep -q "OPENAI_API_KEY" .env; then
        # Update existing OpenAI key
        sed -i.bak 's|^OPENAI_API_KEY=.*|OPENAI_API_KEY=your-openai-api-key-here|' .env
        echo "✅ Updated OPENAI_API_KEY in .env file"
    else
        # Add OpenAI key to existing file
        echo "" >> .env
        echo "# OpenAI API Configuration" >> .env
        echo "OPENAI_API_KEY=your-openai-api-key-here" >> .env
        echo "✅ Added OPENAI_API_KEY to .env file"
    fi
fi

echo ""
echo "Configuration complete!"
echo "API Key configured for OpenAI"
echo ""
echo "You can now start the service with:"
echo "  python -m uvicorn main:app --reload --host 127.0.0.1 --port 8001"
