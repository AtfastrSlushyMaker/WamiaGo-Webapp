#!/usr/bin/env python
import os
import json
import base64
import logging
import time
import uuid
from io import BytesIO
import numpy as np
import dlib
from flask import Flask, request, jsonify
from PIL import Image
import cv2

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Initialize Flask app
app = Flask(__name__)

# Configuration
DATA_DIR = os.environ.get('DATA_DIR', '/app/data')
MODEL_DIR = os.environ.get('MODEL_DIR', '/app/models')
SHAPE_PREDICTOR_PATH = os.environ.get('SHAPE_PREDICTOR_PATH', 
                                    os.path.join(MODEL_DIR, 'shape_predictor_68_face_landmarks.dat'))
FACE_REC_MODEL_PATH = os.environ.get('FACE_REC_MODEL_PATH', 
                                   os.path.join(MODEL_DIR, 'dlib_face_recognition_resnet_model_v1.dat'))
THRESHOLD = float(os.environ.get('THRESHOLD', 0.6))
DEBUG = os.environ.get('DEBUG', 'false').lower() in ('true', '1', 't')

# Ensure data directory exists
os.makedirs(DATA_DIR, exist_ok=True)
os.makedirs(os.path.join(DATA_DIR, 'users'), exist_ok=True)
os.makedirs(MODEL_DIR, exist_ok=True)

# Download face recognition model if it doesn't exist
if not os.path.exists(FACE_REC_MODEL_PATH):
    logger.info("Downloading face recognition model...")
    import urllib.request
    import bz2
    urllib.request.urlretrieve(
        'http://dlib.net/files/dlib_face_recognition_resnet_model_v1.dat.bz2', 
        '/tmp/face_rec_model.bz2'
    )
    with open(FACE_REC_MODEL_PATH, 'wb') as f_out, bz2.BZ2File('/tmp/face_rec_model.bz2', 'rb') as f_in:
        f_out.write(f_in.read())
    os.remove('/tmp/face_rec_model.bz2')
    logger.info(f"Model downloaded and extracted to {FACE_REC_MODEL_PATH}")

# Initialize dlib's face detector, shape predictor and face recognition model
face_detector = dlib.get_frontal_face_detector()
shape_predictor = dlib.shape_predictor(SHAPE_PREDICTOR_PATH)
face_rec_model = dlib.face_recognition_model_v1(FACE_REC_MODEL_PATH)

class FacialRecognitionSystem:
    def __init__(self):
        self.user_encodings = {}
        self.user_metadata = {}
        self.load_user_data()
        
    def load_user_data(self):
        """Load all user face encodings from the data directory"""
        users_dir = os.path.join(DATA_DIR, 'users')
        if not os.path.exists(users_dir):
            return
            
        for user_file in os.listdir(users_dir):
            if user_file.endswith('.json'):
                user_id = user_file.replace('.json', '')
                file_path = os.path.join(users_dir, user_file)
                
                try:
                    with open(file_path, 'r') as f:
                        user_data = json.load(f)
                        
                    if 'encoding' in user_data and 'metadata' in user_data:
                        # Convert the encoding back to numpy array
                        encoding = np.array(user_data['encoding'])
                        self.user_encodings[user_id] = encoding
                        self.user_metadata[user_id] = user_data['metadata']
                        logger.info(f"Loaded face data for user {user_id}")
                except Exception as e:
                    logger.error(f"Error loading user data for {user_id}: {str(e)}")
    
    def register_face(self, user_id, image_data, metadata=None):
        """Register a new face for a user"""
        try:
            # Convert base64 image to numpy array
            if isinstance(image_data, str) and image_data.startswith('data:image'):
                # Extract base64 part
                image_data = image_data.split(',', 1)[1]
                
            if isinstance(image_data, str):
                # Decode base64
                image_bytes = base64.b64decode(image_data)
                image = Image.open(BytesIO(image_bytes))
                # Convert PIL Image to numpy array
                image_array = np.array(image)
                # Convert RGB to BGR (for OpenCV compatibility)
                if len(image_array.shape) == 3 and image_array.shape[2] == 3:
                    image_array = cv2.cvtColor(image_array, cv2.COLOR_RGB2BGR)
            else:
                # Already a numpy array
                image_array = image_data
            
            # Find face locations in the image using dlib's detector
            # Convert to grayscale for better detection
            if len(image_array.shape) == 3:
                gray = cv2.cvtColor(image_array, cv2.COLOR_BGR2GRAY)
            else:
                gray = image_array
                
            faces = face_detector(gray, 1)  # 1 = upsample once for better detection of small faces
            
            if not faces:
                return False, "No face detected in the image"
            
            # Use the first face (assuming one face per image)
            face = faces[0]
            
            # Get facial landmarks
            shape = shape_predictor(image_array, face)
            
            # Compute face encoding (128D vector)
            face_encoding = face_rec_model.compute_face_descriptor(image_array, shape)
            face_encoding_np = np.array(face_encoding)
            
            # Save the face encoding
            user_data = {
                'encoding': face_encoding_np.tolist(),  # Convert numpy array to list for JSON serialization
                'metadata': metadata or {},
                'timestamp': time.time()
            }
            
            # Save to file
            file_path = os.path.join(DATA_DIR, 'users', f"{user_id}.json")
            with open(file_path, 'w') as f:
                json.dump(user_data, f)
            
            # Update in-memory data
            self.user_encodings[user_id] = face_encoding_np
            self.user_metadata[user_id] = metadata or {}
            
            return True, "Face registered successfully"
        except Exception as e:
            logger.error(f"Error registering face: {str(e)}")
            return False, f"Error registering face: {str(e)}"
    
    def verify_face(self, image_data, user_id=None):
        """Verify a face against a registered user or find matching user"""
        try:
            # Convert base64 image to numpy array
            if isinstance(image_data, str) and image_data.startswith('data:image'):
                # Extract base64 part
                image_data = image_data.split(',', 1)[1]
                
            if isinstance(image_data, str):
                # Decode base64
                image_bytes = base64.b64decode(image_data)
                image = Image.open(BytesIO(image_bytes))
                # Convert PIL Image to numpy array
                image_array = np.array(image)
                # Convert RGB to BGR (for OpenCV compatibility)
                if len(image_array.shape) == 3 and image_array.shape[2] == 3:
                    image_array = cv2.cvtColor(image_array, cv2.COLOR_RGB2BGR)
            else:
                # Already a numpy array
                image_array = image_data
            
            # Find face locations in the image using dlib's detector
            # Convert to grayscale for better detection
            if len(image_array.shape) == 3:
                gray = cv2.cvtColor(image_array, cv2.COLOR_BGR2GRAY)
            else:
                gray = image_array
                
            faces = face_detector(gray, 1)  # 1 = upsample once for better detection of small faces
            
            if not faces:
                return {
                    'verified': False,
                    'confidence': 0.0,
                    'message': "No face detected in the image"
                }
            
            # Use the first face (assuming one face per image)
            face = faces[0]
            
            # Get facial landmarks
            shape = shape_predictor(image_array, face)
            
            # Compute face encoding (128D vector)
            face_encoding = face_rec_model.compute_face_descriptor(image_array, shape)
            face_encoding_np = np.array(face_encoding)
            
            # If specific user_id is provided, verify against that user only
            if user_id is not None:
                if user_id not in self.user_encodings:
                    return {
                        'verified': False,
                        'confidence': 0.0,
                        'message': f"User ID {user_id} not found"
                    }
                
                # Calculate Euclidean distance (lower is better)
                stored_encoding = self.user_encodings[user_id]
                face_distance = np.linalg.norm(stored_encoding - face_encoding_np)
                
                # Convert to similarity score (higher is better)
                # Normalize to [0, 1] range where 1 is perfect match
                # Empirically, distances > 0.6 tend to be different people
                confidence = max(0, 1.0 - face_distance)
                
                # Check if this passes the threshold
                if confidence >= THRESHOLD:
                    return {
                        'verified': True,
                        'confidence': float(confidence),
                        'user_id': user_id,
                        'message': "Face verified successfully"
                    }
                else:
                    return {
                        'verified': False,
                        'confidence': float(confidence),
                        'user_id': user_id,
                        'message': "Face verification failed"
                    }
            else:
                # Find the best match among all registered users
                if not self.user_encodings:
                    return {
                        'verified': False,
                        'confidence': 0.0,
                        'message': "No registered users found"
                    }
                
                # Calculate distances to all registered users
                best_match_distance = float('inf')
                best_match_user_id = None
                
                for uid, encoding in self.user_encodings.items():
                    distance = np.linalg.norm(encoding - face_encoding_np)
                    if distance < best_match_distance:
                        best_match_distance = distance
                        best_match_user_id = uid
                
                # Convert to similarity score
                confidence = max(0, 1.0 - best_match_distance)
                
                # Check if this passes the threshold
                if confidence >= THRESHOLD:
                    return {
                        'verified': True,
                        'confidence': float(confidence),
                        'user_id': best_match_user_id,
                        'message': "Face matched to a registered user"
                    }
                else:
                    return {
                        'verified': False,
                        'confidence': float(confidence),
                        'message': "No matching face found above threshold"
                    }
        except Exception as e:
            logger.error(f"Error verifying face: {str(e)}")
            return {
                'verified': False,
                'confidence': 0.0,
                'message': f"Error verifying face: {str(e)}"
            }
    
    def delete_user(self, user_id):
        """Delete a user's face data"""
        try:
            file_path = os.path.join(DATA_DIR, 'users', f"{user_id}.json")
            
            if os.path.exists(file_path):
                os.remove(file_path)
                
            if user_id in self.user_encodings:
                del self.user_encodings[user_id]
                
            if user_id in self.user_metadata:
                del self.user_metadata[user_id]
                
            return True, "User deleted successfully"
        except Exception as e:
            logger.error(f"Error deleting user {user_id}: {str(e)}")
            return False, f"Error deleting user: {str(e)}"

# Initialize the facial recognition system
face_system = FacialRecognitionSystem()

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'ok',
        'users_loaded': len(face_system.user_encodings),
        'threshold': THRESHOLD,
        'models': {
            'shape_predictor': os.path.exists(SHAPE_PREDICTOR_PATH),
            'face_recognition_model': os.path.exists(FACE_REC_MODEL_PATH)
        }
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
    
    success, message = face_system.register_face(user_id, image, metadata)
    
    return jsonify({
        'success': success,
        'message': message,
        'user_id': user_id
    })

@app.route('/verify', methods=['POST'])
def verify_face():
    try:
        data = request.json
        
        if not data:
            logger.error("No JSON data received in verify request")
            return jsonify({
                'verified': False,
                'message': 'No data provided'
            }), 400
        
        # Log the keys in the request data but not the actual image content for privacy
        logger.info(f"Verify request received with keys: {list(data.keys())}")
        
        image_data = data.get('image')
        user_id = data.get('user_id')
        
        if not image_data:
            logger.error("No image data provided in verify request")
            return jsonify({
                'verified': False,
                'message': 'No image data provided'
            }), 400
            
        # Log image data format for debugging (without revealing the actual image)
        if isinstance(image_data, str):
            prefix = image_data[:30] + '...' if len(image_data) > 30 else image_data
            logger.info(f"Image data is string with prefix: {prefix}")
            
            # For base64 data URLs, log the format
            if image_data.startswith('data:image'):
                logger.info(f"Image appears to be data URL with type: {image_data.split(',')[0]}")
        else:
            logger.info(f"Image data is not a string but {type(image_data)}")
        
        # Perform face verification
        result = face_system.verify_face(image_data, user_id)
        
        # Log the result (without sensitive data)
        verification_result = 'verified' if result.get('verified', False) else 'not verified'
        logger.info(f"Face verification result: {verification_result} with confidence {result.get('confidence', 0)}")
        
        return jsonify(result)
    except Exception as e:
        logger.exception(f"Error in /verify endpoint: {str(e)}")
        return jsonify({
            'verified': False,
            'message': f'Server error: {str(e)}'
        }), 500

@app.route('/users/<user_id>', methods=['DELETE'])
def delete_user(user_id):
    """Delete a user's face data"""
    success, message = face_system.delete_user(user_id)
    
    return jsonify({
        'success': success,
        'message': message
    })

@app.route('/users', methods=['GET'])
def list_users():
    """List all registered users"""
    users = []
    
    for user_id, metadata in face_system.user_metadata.items():
        users.append({
            'user_id': user_id,
            'metadata': metadata
        })
    
    return jsonify({
        'success': True,
        'count': len(users),
        'users': users
    })

if __name__ == '__main__':
    host = os.environ.get('HOST', '0.0.0.0')
    port = int(os.environ.get('PORT', 5001))
    
    logger.info(f"Starting Facial Recognition API on {host}:{port}")
    logger.info(f"Loaded {len(face_system.user_encodings)} user face profiles")
    logger.info(f"Using dlib models: {SHAPE_PREDICTOR_PATH} and {FACE_REC_MODEL_PATH}")
    
    app.run(host=host, port=port, debug=DEBUG)