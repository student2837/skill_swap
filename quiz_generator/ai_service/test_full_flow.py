#!/usr/bin/env python3
"""
Full flow test script for AI Quiz Generator
Tests the complete workflow: health check → generate exam → grade exam
"""
import requests
import json
import sys
import time

BASE_URL = "http://localhost:8000"

def print_section(title):
    print("\n" + "=" * 60)
    print(f"  {title}")
    print("=" * 60)

def test_health():
    """Test health endpoint"""
    print_section("1. Testing Health Endpoint")
    try:
        response = requests.get(f"{BASE_URL}/health", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print(f"✅ Health check passed")
            print(f"   Status: {data.get('status')}")
            print(f"   Service: {data.get('service')}")
            return True
        else:
            print(f"❌ Health check failed: {response.status_code}")
            return False
    except requests.exceptions.ConnectionError:
        print("❌ Cannot connect to service. Is it running?")
        print("   Start it with: python main.py")
        return False
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return False

def test_generate_exam():
    """Test exam generation"""
    print_section("2. Testing Exam Generation")
    
    exam_data = {
        "course_name": "Introduction to Python Programming",
        "teacher_name": "Dr. Jane Smith",
        "student_name": "John Doe",
        "learning_outcomes": [
            "Understand basic Python syntax and data types",
            "Write and call functions in Python",
            "Use control structures (if/else, loops)"
        ],
        "passing_score": 75.0
    }
    
    print("Sending request to generate exam...")
    print(f"   Course: {exam_data['course_name']}")
    print(f"   Learning Outcomes: {len(exam_data['learning_outcomes'])}")
    print("   This may take 30-60 seconds...")
    
    try:
        response = requests.post(
            f"{BASE_URL}/generate-exam",
            json=exam_data,
            timeout=120  # 2 minutes timeout for LLM
        )
        
        if response.status_code == 200:
            result = response.json()
            
            if result.get('success'):
                print(f"✅ Exam generated successfully!")
                print(f"   Total questions: {result.get('total_questions')}")
                print(f"   Message: {result.get('message')}")
                
                # Show first question as example
                if result.get('mcqs'):
                    first_q = result['mcqs'][0]
                    print(f"\n   Example Question:")
                    print(f"   {first_q.get('question')}")
                    print(f"   Correct Answer: {first_q.get('correct_answer')}")
                
                return result
            else:
                print(f"❌ Exam generation failed")
                print(f"   Error: {result.get('error')}")
                return None
        else:
            print(f"❌ Request failed: {response.status_code}")
            print(f"   Response: {response.text[:200]}")
            return None
            
    except requests.exceptions.Timeout:
        print("❌ Request timed out. LLM might be slow.")
        return None
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return None

def test_grade_exam(exam_data, exam_result):
    """Test exam grading"""
    print_section("3. Testing Exam Grading")
    
    if not exam_result or not exam_result.get('mcqs'):
        print("❌ Cannot test grading - no exam data")
        return None
    
    mcqs = exam_result['mcqs']
    
    # Create student answers (mix of correct and incorrect)
    student_answers = []
    correct_count = 0
    
    for mcq in mcqs:
        # Answer correctly for first half, incorrectly for second half
        if len(student_answers) < len(mcqs) / 2:
            answer = mcq['correct_answer']
            correct_count += 1
        else:
            # Pick a wrong answer
            wrong_answers = [c for c in ['A', 'B', 'C', 'D'] if c != mcq['correct_answer']]
            answer = wrong_answers[0]
        
        student_answers.append({
            "question_id": mcq['id'],
            "answer": answer
        })
    
    grade_data = {
        "course_name": exam_data['course_name'],
        "teacher_name": exam_data['teacher_name'],
        "student_name": exam_data['student_name'],
        "learning_outcomes": exam_data['learning_outcomes'],
        "passing_score": exam_data['passing_score'],
        "mcqs": mcqs,
        "student_answers": student_answers
    }
    
    print("Sending answers for grading...")
    print(f"   Questions answered: {len(student_answers)}")
    print(f"   Expected correct: {correct_count}/{len(mcqs)}")
    
    try:
        response = requests.post(
            f"{BASE_URL}/grade-exam",
            json=grade_data,
            timeout=120
        )
        
        if response.status_code == 200:
            result = response.json()
            
            if result.get('success'):
                print(f"✅ Grading completed!")
                print(f"   Raw Score: {result.get('raw_score')}/{result.get('total_questions')}")
                print(f"   Percentage: {result.get('percentage')}%")
                print(f"   Passing Score: {result.get('passing_score')}%")
                print(f"   Passed: {'✅ YES' if result.get('passed') else '❌ NO'}")
                
                if result.get('certificate_text'):
                    print(f"\n   🎓 Certificate Generated!")
                    cert_preview = result['certificate_text'][:150]
                    print(f"   Preview: {cert_preview}...")
                
                return result
            else:
                print(f"❌ Grading failed")
                print(f"   Error: {result.get('error')}")
                if result.get('validation_errors'):
                    print(f"   Validation Errors:")
                    for err in result['validation_errors']:
                        print(f"     - {err}")
                return None
        else:
            print(f"❌ Request failed: {response.status_code}")
            print(f"   Response: {response.text[:200]}")
            return None
            
    except Exception as e:
        print(f"❌ Error: {str(e)}")
        return None

def main():
    """Run all tests"""
    print("\n" + "🚀 AI Quiz Generator - Full Flow Test")
    print("=" * 60)
    
    # Test 1: Health check
    if not test_health():
        print("\n❌ Health check failed. Please start the service first.")
        print("   Run: cd ai_service && python main.py")
        sys.exit(1)
    
    # Test 2: Generate exam
    exam_data = {
        "course_name": "Introduction to Python Programming",
        "teacher_name": "Dr. Jane Smith",
        "student_name": "John Doe",
        "learning_outcomes": [
            "Understand basic Python syntax and data types",
            "Write and call functions in Python",
            "Use control structures (if/else, loops)"
        ],
        "passing_score": 75.0
    }
    
    exam_result = test_generate_exam()
    if not exam_result:
        print("\n❌ Exam generation failed. Check your API key and service logs.")
        sys.exit(1)
    
    # Test 3: Grade exam
    grade_result = test_grade_exam(exam_data, exam_result)
    if not grade_result:
        print("\n❌ Grading failed.")
        sys.exit(1)
    
    # Summary
    print_section("✅ All Tests Passed!")
    print("Summary:")
    print(f"  • Health check: ✅")
    print(f"  • Exam generation: ✅ ({exam_result.get('total_questions')} questions)")
    print(f"  • Grading: ✅ ({grade_result.get('percentage')}% score)")
    if grade_result.get('passed'):
        print(f"  • Certificate: ✅ Generated")
    print("\n🎉 System is working correctly!")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n\n⚠️  Test interrupted by user")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Unexpected error: {str(e)}")
        import traceback
        traceback.print_exc()
        sys.exit(1)
