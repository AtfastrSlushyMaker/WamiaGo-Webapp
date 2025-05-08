#!/usr/bin/env python
import requests
import base64
import json
import os
import time
from PIL import Image
from io import BytesIO

# Configuration
API_URL = "http://localhost:5001"  # Update if deployed elsewhere

def image_to_base64(image_path):
    """Convert an image file to base64 string"""
    with open(image_path, "rb") as image_file:
        encoded_string = base64.b64encode(image_file.read()).decode('utf-8')
    return encoded_string

def test_health():
    """Test the health endpoint"""
    print("\n----- Testing Health Endpoint -----")
    response = requests.get(f"{API_URL}/health")
    print(f"Status Code: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    return response.status_code == 200

def test_register(image_path, user_id=None, metadata=None):
    """Test registering a face"""
    print(f"\n----- Testing Face Registration -----")
    print(f"Image: {image_path}")
    
    if not os.path.exists(image_path):
        print(f"Error: Image file not found: {image_path}")
        return None
    
    # Convert image to base64
    image_base64 = image_to_base64(image_path)
    
    # Prepare payload
    payload = {
        "image": image_base64
    }
    
    if user_id:
        payload["user_id"] = user_id
    
    if metadata:
        payload["metadata"] = metadata
    
    # Send request
    response = requests.post(f"{API_URL}/register", json=payload)
    print(f"Status Code: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    if response.status_code == 200 and response.json().get('success'):
        return response.json().get('user_id')
    return None

def test_verify(image_path, user_id=None):
    """Test verifying a face"""
    print(f"\n----- Testing Face Verification -----")
    print(f"Image: {image_path}")
    print(f"User ID: {user_id if user_id else 'Any'}")
    
    if not os.path.exists(image_path):
        print(f"Error: Image file not found: {image_path}")
        return False
    
    # Convert image to base64
    image_base64 = image_to_base64(image_path)
    
    # Prepare payload
    payload = {
        "image": image_base64
    }
    
    if user_id:
        payload["user_id"] = user_id
    
    # Send request
    response = requests.post(f"{API_URL}/verify", json=payload)
    print(f"Status Code: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    
    if response.status_code == 200:
        return response.json().get('verified', False)
    return False

def test_list_users():
    """Test listing all users"""
    print("\n----- Testing List Users -----")
    response = requests.get(f"{API_URL}/users")
    print(f"Status Code: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    return response.status_code == 200

def test_delete_user(user_id):
    """Test deleting a user"""
    print(f"\n----- Testing Delete User -----")
    print(f"User ID: {user_id}")
    
    response = requests.delete(f"{API_URL}/users/{user_id}")
    print(f"Status Code: {response.status_code}")
    print(f"Response: {json.dumps(response.json(), indent=2)}")
    return response.status_code == 200 and response.json().get('success', False)

def create_test_image(output_path, text="Test Face"):
    """Create a sample test image with text (no face, just for API testing)"""
    from PIL import Image, ImageDraw, ImageFont
    
    # Create a blank image with white background
    img = Image.new('RGB', (400, 400), color=(255, 255, 255))
    d = ImageDraw.Draw(img)
    
    # Draw some text
    d.text((50, 200), text, fill=(0, 0, 0))
    
    # Save the image
    img.save(output_path)
    print(f"Created test image: {output_path}")
    return output_path

def run_full_test():
    """Run a full test of the API"""
    # Create a test directory if it doesn't exist
    test_dir = "test_images"
    os.makedirs(test_dir, exist_ok=True)
    
    # Check if we need to create test images
    test_image1 = os.path.join(test_dir, "test_face1.jpg")
    test_image2 = os.path.join(test_dir, "test_face2.jpg")
    
    print("\n===== STARTING FACIAL RECOGNITION API TESTS =====")
    
    # Step 1: Check health
    if not test_health():
        print("❌ Health check failed. Is the API running?")
        return False
    
    print("\n✅ Health check passed.")
    
    # Step 2: List users (before adding any)
    test_list_users()
    
    # Step 3: Check if we have test images, otherwise create placeholders
    if not os.path.exists(test_image1):
        print("\n⚠️ No test images found. Using generated images (no real faces).")
        print("For proper testing, place real face images in the test_images directory.")
        create_test_image(test_image1, "Test Face 1")
    
    if not os.path.exists(test_image2):
        create_test_image(test_image2, "Test Face 2")
    
    # Step 4: Register a face
    metadata = {
        "name": "Test User",
        "email": "test@example.com"
    }
    user_id = test_register(test_image1, metadata=metadata)
    
    if not user_id:
        print("❌ Face registration failed.")
        return False
    
    print(f"\n✅ Face registration passed. User ID: {user_id}")
    
    # Step 5: List users (after adding one)
    test_list_users()
    
    # Step 6: Verify the same face (should match)
    if test_verify(test_image1, user_id):
        print("\n✅ Face verification with same image passed.")
    else:
        print("\n❌ Face verification with same image failed.")
    
    # Step 7: Verify a different face (should not match)
    if not test_verify(test_image2, user_id):
        print("\n✅ Face verification with different image correctly failed.")
    else:
        print("\n❌ Face verification with different image incorrectly passed.")
    
    # Step 8: Verify without specifying user_id (should find the best match)
    test_verify(test_image1)
    
    # Step 9: Delete the user
    if test_delete_user(user_id):
        print("\n✅ User deletion passed.")
    else:
        print("\n❌ User deletion failed.")
    
    # Step 10: List users (after deleting)
    test_list_users()
    
    print("\n===== FACIAL RECOGNITION API TESTS COMPLETED =====")
    return True

if __name__ == "__main__":
    run_full_test() 