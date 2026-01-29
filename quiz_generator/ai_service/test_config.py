#!/usr/bin/env python3
"""
Quick test script to verify OpenAI API configuration
"""
import os
from langchain_openai import ChatOpenAI

def test_openai_config():
    """Test if OpenAI API is properly configured"""
    print("Testing OpenAI API Configuration...")
    print("-" * 50)
    
    # Check for API key
    api_key = os.getenv("OPENAI_API_KEY")
    
    if not api_key:
        print("❌ ERROR: No API key found!")
        print("   Set OPENAI_API_KEY environment variable")
        return False
    
    print(f"✅ API Key found: {api_key[:10]}...{api_key[-4:]}")
    
    # Try to initialize LLM
    try:
        llm = ChatOpenAI(
            temperature=0.7,
            model="gpt-4",
            api_key=api_key
        )
        print("✅ LLM initialized successfully")
        
        # Try a simple test
        print("\nTesting API connection...")
        response = llm.invoke("Say 'Hello, OpenAI is working!' in one sentence.")
        print(f"✅ API Response: {response.content[:100]}...")
        print("\n🎉 OpenAI configuration is working correctly!")
        return True
        
    except Exception as e:
        print(f"❌ ERROR: Failed to connect to OpenAI API")
        print(f"   Error: {str(e)}")
        print("\nTroubleshooting:")
        print("   1. Verify your API key is correct")
        print("   2. Check your internet connection")
        print("   3. Ensure your OpenAI account has credits")
        print("   4. Check if the API key has access to GPT-4")
        return False

if __name__ == "__main__":
    test_openai_config()
