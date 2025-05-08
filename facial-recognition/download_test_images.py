#!/usr/bin/env python
import os
import requests
import urllib.request
from PIL import Image
from io import BytesIO

# Create test directory
test_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test_images")
os.makedirs(test_dir, exist_ok=True)

def download_image(url, filename):
    """Download image from URL and save to test_images directory"""
    try:
        print(f"Downloading {url} to {filename}...")
        response = requests.get(url)
        if response.status_code == 200:
            img = Image.open(BytesIO(response.content))
            img.save(os.path.join(test_dir, filename))
            print(f"✅ Successfully downloaded {filename}")
            return True
        else:
            print(f"❌ Failed to download image: {response.status_code}")
            return False
    except Exception as e:
        print(f"❌ Error downloading image: {str(e)}")
        return False

# Download test images from a public repository with sample faces
# Using public domain images from Pexels
urls = [
    # Person 1 - different angles
    ("https://images.pexels.com/photos/614810/pexels-photo-614810.jpeg", "person1_front.jpg"),
    ("https://images.pexels.com/photos/428364/pexels-photo-428364.jpeg", "person2_front.jpg"),
    # Different lighting/poses
    ("https://images.pexels.com/photos/91227/pexels-photo-91227.jpeg", "person3_front.jpg"),
    # Groups (multiple faces)
    ("https://images.pexels.com/photos/1212984/pexels-photo-1212984.jpeg", "group1.jpg"),
]

success_count = 0
for url, filename in urls:
    if download_image(url, filename):
        success_count += 1

print(f"\nDownloaded {success_count} out of {len(urls)} test images")
print(f"Images saved to: {test_dir}")
print("Run test_api.py to test the facial recognition system with these images") 