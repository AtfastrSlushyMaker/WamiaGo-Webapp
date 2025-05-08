import os
import shutil
import base64
from PIL import Image, ImageDraw, ImageFont

# Create test directory and images
TEST_DIR = "simple_test_images"
os.makedirs(TEST_DIR, exist_ok=True)

def create_test_image(filename, text, size=(300, 300), color=(255, 255, 255)):
    """Create a simple test image with text"""
    img = Image.new('RGB', size, color=color)
    draw = ImageDraw.Draw(img)
    draw.text((50, 150), text, fill=(0, 0, 0))
    
    filepath = os.path.join(TEST_DIR, filename)
    img.save(filepath)
    print(f"Created test image: {filepath}")
    return filepath

# Create test images
test_image1 = create_test_image("test1.jpg", "Test Face 1")
test_image2 = create_test_image("test2.jpg", "Test Face 2")

# Setup test data directory
DATA_DIR = os.path.join(TEST_DIR, "data")
USERS_DIR = os.path.join(DATA_DIR, "users")
os.makedirs(USERS_DIR, exist_ok=True)

print("\n=== Testing FacialRecognitionSystem locally ===")

# Import the facial recognition system directly
from app import FacialRecognitionSystem

# Override data directory
import app
app.DATA_DIR = DATA_DIR

# Initialize the system
face_system = FacialRecognitionSystem()

# Test image to base64
def image_to_base64(image_path):
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode('utf-8')

# Test registration
user_id = "test_user_123"
image_base64 = image_to_base64(test_image1)
metadata = {"name": "Test User", "email": "test@example.com"}

print(f"\n1. Registering user {user_id}")
success, message = face_system.register_face(user_id, image_base64, metadata)
print(f"   Result: {'✅ Success' if success else '❌ Failed'}")
print(f"   Message: {message}")

# Test verification (same image)
print(f"\n2. Verifying same face for user {user_id}")
result = face_system.verify_face(image_base64, user_id)
print(f"   Verified: {'✅ Yes' if result['verified'] else '❌ No'}")
print(f"   Confidence: {result['confidence']:.4f}")
print(f"   Message: {result['message']}")

# Test verification (different image)
print(f"\n3. Verifying different face for user {user_id}")
image_base64_2 = image_to_base64(test_image2)
result = face_system.verify_face(image_base64_2, user_id)
print(f"   Verified: {'✅ Yes' if result['verified'] else '❌ No'}")
print(f"   Confidence: {result['confidence']:.4f}")
print(f"   Message: {result['message']}")

# Test verification (any user)
print(f"\n4. Verifying face without specifying user")
result = face_system.verify_face(image_base64)
print(f"   Verified: {'✅ Yes' if result['verified'] else '❌ No'}")
print(f"   Confidence: {result['confidence']:.4f}")
print(f"   Message: {result['message']}")

# Check if user file was created
user_file = os.path.join(USERS_DIR, f"{user_id}.json")
print(f"\n5. Checking if user file was created: {user_file}")
print(f"   Exists: {'✅ Yes' if os.path.exists(user_file) else '❌ No'}")

# Optionally clean up test data
print("\n=== Test completed ===")
keep_test_data = input("Keep test data? (y/n): ").lower() == 'y'
if not keep_test_data:
    print(f"Cleaning up test directory: {TEST_DIR}")
    shutil.rmtree(TEST_DIR)
    print("Test directory removed") 