FROM python:3.10-slim

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    cmake \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libglib2.0-0 \
    libx11-dev \
    libopenblas-dev \
    liblapack-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY . .

# Create directories
RUN mkdir -p /app/data /app/models

# Create a separate script to download models
RUN echo "import os\nimport dlib\nimport urllib.request\nimport bz2\n\nprint('Initializing dlib detector...')\ndlib.get_frontal_face_detector()\n\nprint('Downloading shape predictor model...')\npredictor_path = '/app/models/shape_predictor_68_face_landmarks.dat'\nif not os.path.exists(predictor_path):\n    urllib.request.urlretrieve('http://dlib.net/files/shape_predictor_68_face_landmarks.dat.bz2', '/tmp/shape_predictor.bz2')\n    with open(predictor_path, 'wb') as f_out, bz2.BZ2File('/tmp/shape_predictor.bz2', 'rb') as f_in:\n        f_out.write(f_in.read())\n    os.remove('/tmp/shape_predictor.bz2')\nprint('Model downloaded and extracted to', predictor_path)" > /app/download_models.py

# Run the model download script
RUN python /app/download_models.py

# Expose the port
EXPOSE 5001

# Start the application
CMD ["python", "app.py"]