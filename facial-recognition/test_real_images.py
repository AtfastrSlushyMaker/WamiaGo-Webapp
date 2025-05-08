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

def run_full_test():
    """Run a full test of the API with real face images"""
    # Test directory with the pre-downloaded images
    test_dir = "test_images"
    
    # Use the downloaded face images
    person1_image = os.path.join(test_dir, "person1_front.jpg")
    person2_image = os.path.join(test_dir, "person2_front.jpg")
    
    if not os.path.exists(person1_image) or not os.path.exists(person2_image):
        print("Error: Test images not found. Run download_test_images.py first.")
        return False
    
    print("\n===== STARTING FACIAL RECOGNITION API TESTS WITH REAL FACES =====")
    
    # Step 1: Check health
    if not test_health():
        print("❌ Health check failed. Is the API running?")
        return False
    
    print("\n✅ Health check passed.")
    
    # Step 2: List users (before adding any)
    test_list_users()
    
    # Step 3: Register a face
    metadata = {
        "name": "Test Person 1",
        "email": "person1@example.com"
    }
    print("\nRegistering first person (person1_front.jpg)...")
    user_id = test_register(person1_image, metadata=metadata)
    
    if not user_id:
        print("❌ Face registration failed for person 1.")
        return False
    
    print(f"\n✅ Face registration passed for person 1. User ID: {user_id}")
    
    # Step 4: List users (after adding one)
    test_list_users()
    
    # Step 5: Verify the same face (should match)
    print("\nVerifying same person...")
    if test_verify(person1_image, user_id):
        print("\n✅ Face verification with same image passed.")
    else:
        print("\n❌ Face verification with same image failed.")
    
    # Step 6: Verify a different face (should not match)
    print("\nVerifying different person...")
    if not test_verify(person2_image, user_id):
        print("\n✅ Face verification with different image correctly failed.")
    else:
        print("\n❌ Face verification with different image incorrectly passed.")
    
    # Step 7: Verify without specifying user_id (should find the best match)
    print("\nVerifying without specifying user ID (should find person 1)...")
    test_verify(person1_image)
    
    # Step 8: Register second person
    metadata2 = {
        "name": "Test Person 2",
        "email": "person2@example.com"
    }
    print("\nRegistering second person (person2_front.jpg)...")
    user_id2 = test_register(person2_image, metadata=metadata2)
    
    if not user_id2:
        print("❌ Face registration failed for person 2.")
    else:
        print(f"\n✅ Face registration passed for person 2. User ID: {user_id2}")
        
        # Verify person 2
        print("\nVerifying person 2...")
        if test_verify(person2_image, user_id2):
            print("\n✅ Face verification for person 2 passed.")
        else:
            print("\n❌ Face verification for person 2 failed.")
        
        # Delete person 2
        if test_delete_user(user_id2):
            print("\n✅ User deletion passed for person 2.")
        else:
            print("\n❌ User deletion failed for person 2.")
    
    # Step 9: Delete person 1
    if test_delete_user(user_id):
        print("\n✅ User deletion passed for person 1.")
    else:
        print("\n❌ User deletion failed for person 1.")
    
    # Step 10: List users (after deleting)
    test_list_users()
    
    print("\n===== FACIAL RECOGNITION API TESTS COMPLETED =====")
    return True

if __name__ == "__main__":
    run_full_test() 