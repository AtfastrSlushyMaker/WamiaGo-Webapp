#!/usr/bin/env python
"""
Simple test script for the Flask API without dlib dependencies.
This script creates a minimal Flask app that mimics the facial recognition API
for testing purposes only.
"""

import os
import base64
import json
import uuid
from flask import Flask, request, jsonify
from PIL import Image
from io import BytesIO

# Create a test directory
TEST_DIR = "test_api_data"
os.makedirs(TEST_DIR, exist_ok=True)
os.makedirs(os.path.join(TEST_DIR, "users"), exist_ok=True)

# Create a test image
def create_test_image(width=300, height=300):
    """Create a simple test image"""
    img = Image.new('RGB', (width, height), color=(255, 255, 255))
    img_bytes = BytesIO()
    img.save(img_bytes, format='JPEG')
    img_bytes.seek(0)
    return base64.b64encode(img_bytes.read()).decode('utf-8')

# Initialize Flask app
app = Flask(__name__)

# Simulated user data
users = {}

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'ok',
        'users_loaded': len(users),
        'threshold': 0.6,
        'note': 'This is a test API without actual face recognition'
    })

@app.route('/register', methods=['POST'])
def register_face():
    """Register a face for a user"""
    data = request.json
    
    if not data:
        return jsonify({'success': False, 'message': 'No data provided'}), 400
    
    user_id = data.get('user_id')
    image = data.get('image')
    metadata = data.get('metadata', {})
    
    if not user_id:
        user_id = str(uuid.uuid4())
    
    if not image:
        return jsonify({'success': False, 'message': 'No image provided'}), 400
    
    # In this test version, we just pretend to register the face
    users[user_id] = {
        'image': image[:50] + '...',  # Store just a bit of the image for mock purposes
        'metadata': metadata
    }
    
    # Save user data to file for persistence testing
    user_file = os.path.join(TEST_DIR, "users", f"{user_id}.json")
    with open(user_file, 'w') as f:
        json.dump({'metadata': metadata}, f)
    
    return jsonify({
        'success': True,
        'message': "Face registered successfully (TEST MODE)",
        'user_id': user_id
    })

@app.route('/verify', methods=['POST'])
def verify_face():
    """Verify a face against registered users"""
    data = request.json
    
    if not data:
        return jsonify({'success': False, 'message': 'No data provided'}), 400
    
    image = data.get('image')
    user_id = data.get('user_id')
    
    if not image:
        return jsonify({'success': False, 'message': 'No image provided'}), 400
    
    # In this test version, verify will always succeed for registered users
    if user_id:
        if user_id in users:
            return jsonify({
                'verified': True,
                'confidence': 0.9,
                'user_id': user_id,
                'message': "Face verified successfully (TEST MODE)"
            })
        else:
            return jsonify({
                'verified': False,
                'confidence': 0.1,
                'message': f"User ID {user_id} not found"
            })
    else:
        # If no user_id provided, return the first user if any exist
        if users:
            first_user_id = list(users.keys())[0]
            return jsonify({
                'verified': True,
                'confidence': 0.85,
                'user_id': first_user_id,
                'message': "Face matched to a registered user (TEST MODE)"
            })
        else:
            return jsonify({
                'verified': False,
                'confidence': 0.0,
                'message': "No registered users found"
            })

@app.route('/users/<user_id>', methods=['DELETE'])
def delete_user(user_id):
    """Delete a user's face data"""
    if user_id in users:
        del users[user_id]
        
        # Remove user file if it exists
        user_file = os.path.join(TEST_DIR, "users", f"{user_id}.json")
        if os.path.exists(user_file):
            os.remove(user_file)
        
        return jsonify({
            'success': True,
            'message': "User deleted successfully"
        })
    else:
        return jsonify({
            'success': False,
            'message': f"User ID {user_id} not found"
        })

@app.route('/users', methods=['GET'])
def list_users():
    """List all registered users"""
    user_list = []
    
    for user_id, user_data in users.items():
        user_list.append({
            'user_id': user_id,
            'metadata': user_data.get('metadata', {})
        })
    
    return jsonify({
        'success': True,
        'count': len(user_list),
        'users': user_list
    })

if __name__ == '__main__':
    print("Starting test Flask API for facial recognition...")
    print("Note: This is NOT actual facial recognition, just a mock API for testing!")
    print(f"Test data directory: {TEST_DIR}")
    
    # Create a test user for convenience
    test_user_id = "test_user_1"
    test_image = create_test_image()
    
    users[test_user_id] = {
        'image': test_image[:50] + '...',
        'metadata': {'name': 'Test User', 'note': 'This is a test user'}
    }
    
    user_file = os.path.join(TEST_DIR, "users", f"{test_user_id}.json")
    with open(user_file, 'w') as f:
        json.dump({'metadata': {'name': 'Test User'}}, f)
    
    print(f"Created test user: {test_user_id}")
    
    # Run the Flask app
    app.run(host='0.0.0.0', port=5001, debug=True) 