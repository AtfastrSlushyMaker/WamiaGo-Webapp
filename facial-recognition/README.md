# Facial Recognition Service

A microservice for facial recognition with dlib and Flask.

## Features

- Register faces with metadata
- Verify faces against registered users
- Identify unknown faces by matching to registered users
- Delete user data
- List registered users

## Architecture

- **Flask API**: RESTful endpoints for facial recognition operations
- **dlib**: Core face detection and recognition functionality
- **Docker**: Containerized deployment

## API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/health` | GET | Health check and service status |
| `/register` | POST | Register a new face |
| `/verify` | POST | Verify a face against registered users |
| `/users` | GET | List all registered users |
| `/users/<user_id>` | DELETE | Delete a user's face data |

## Getting Started

### Prerequisites

- Docker and Docker Compose

### Installation

1. Clone the repository
2. Navigate to the project directory
3. Build and start the service:

```bash
docker-compose up --build
```

### Testing

Run the test scripts to verify functionality:

```bash
# Download test face images
python download_test_images.py

# Run API tests
python test_api.py
```

## API Usage Examples

### Register a Face

```bash
curl -X POST http://localhost:5001/register \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "user123",
    "image": "<base64-encoded-image>",
    "metadata": {
      "name": "John Doe",
      "email": "john@example.com"
    }
  }'
```

### Verify a Face

```bash
curl -X POST http://localhost:5001/verify \
  -H "Content-Type: application/json" \
  -d '{
    "image": "<base64-encoded-image>",
    "user_id": "user123"
  }'
```

## Configuration

The service can be configured using environment variables:

- `DATA_DIR`: Directory for storing user data (default: `/app/data`)
- `MODEL_DIR`: Directory for model files (default: `/app/models`)
- `THRESHOLD`: Confidence threshold for face verification (default: `0.6`)
- `HOST`: Host to bind the server (default: `0.0.0.0`)
- `PORT`: Port to run the server (default: `5001`)
- `DEBUG`: Enable debug mode (default: `false`)

## License

This project is open source and available under the MIT License. 