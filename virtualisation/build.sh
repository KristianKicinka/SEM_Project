#!/bin/bash
set -euo pipefail

# -----------------------------------------------------------------------------
# Docker build script
# Builds multiple Docker images for the project from specific Dockerfiles.
#
# Images:
#   1. PHP + Python + Wireshark environment
#   2. Final Hash App application
#   3. Android emulator VM instances
#
# File: build.sh
# Author: Kristián Kičinka (xkicin02)
# -----------------------------------------------------------------------------

# Enable Docker BuildKit for faster builds and better layer caching
export DOCKER_BUILDKIT=1

# -------------------------------------------------------------------
# Define image tags
# -------------------------------------------------------------------
TAG_PY_PHP_WS="sem_python_wireshark_php8.2:latest"
TAG_HASHAPP="sem_hashapp:latest"
TAG_EMULATORS="sem_emulator_vm:latest"

# -------------------------------------------------------------------
# Detect the docker group ID (used in Hash App image build)
# If the group does not exist, skip passing the build argument
# -------------------------------------------------------------------
DOCKER_GROUP_ID=""
if getent group docker >/dev/null 2>&1; then
  DOCKER_GROUP_ID="$(getent group docker | cut -d: -f3)"
fi

# -------------------------------------------------------------------
# Build: PHP + Python + Wireshark environment
# -------------------------------------------------------------------
echo "==> Building image: PHP + Python + Wireshark"
docker build \
  -t "$TAG_PY_PHP_WS" \
  -f ./Dockerfile_php_python_wireshark \
  ..

# -------------------------------------------------------------------
# Build: Final Hash App
# Pass DOCKER_GROUP_ID only if available
# -------------------------------------------------------------------
echo "==> Building image: Hash App"
if [[ -n "$DOCKER_GROUP_ID" ]]; then
  docker build --no-cache \
    -t "$TAG_HASHAPP" \
    -f ./Dockerfile_hashapp \
    --build-arg "DOCKER_GROUP_ID=$DOCKER_GROUP_ID" \
    ..
else
  echo "Warning: group 'docker' does not exist – building without DOCKER_GROUP_ID."
  docker build --no-cache \
    -t "$TAG_HASHAPP" \
    -f ./Dockerfile_hashapp \
    ..
fi

# -------------------------------------------------------------------
# Build: Android emulator VM instances
# -------------------------------------------------------------------
echo "==> Building image: Android Emulator VM"
docker build --no-cache \
  -t "$TAG_EMULATORS" \
  -f ./Dockerfile_emulators \
  ..

echo "All images have been built successfully."
