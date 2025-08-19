#!/bin/bash
set -euo pipefail

# -----------------------------------------------------------------------------
# @file clean.sh — stop and clean up your Docker stack
# @author Kristián Kičinka (xkicin02)
#
# Supports:
#   --stop            Stop services (non-destructive)
#   --down            docker compose down (remove containers & networks)
#   --down-v          docker compose down -v (also remove volumes)
#   --rm-images       remove project images (tags listed below)
#   --full            down -v + remove images + prune (full cleanup)
#   -y|--yes          skip confirmations (non-interactive)
#   -f FILE           use a specific docker-compose file (default: ./docker-compose.yml)
#
# Project-specific bits adapted to your compose:
#   Services/containers: hashapp_web, hashapp_db, hashapp_redis, emulator_01, emulator_02
#   Networks: em_net_01, em_net_02
#   Named volumes: shared_dir
#   Images: sem_hashapp:latest, sem_emulator_vm:latest, sem_python_wireshark_php8.2:latest
# -----------------------------------------------------------------------------

# ---------- CONFIG (adjust if you change tags/names) -------------------------
PROJECT_IMAGES=(
  "sem_hashapp:latest"
  "sem_emulator_vm:latest"
  "sem_python_wireshark_php8.2:latest"
)

PROJECT_NETWORKS=(
  "em_net_01"
  "em_net_02"
)

PROJECT_VOLUMES=(
  "shared_dir"
)

COMPOSE_FILE="./docker-compose.yml"

# ---------- UI helpers -------------------------------------------------------
bold() { printf "\033[1m%s\033[0m" "$*"; }
log()  { echo; echo "==> $(bold "$*")"; }
ok()   { echo "✔ $*"; }
warn() { echo "⚠ $*"; }
die()  { echo "✖ ERROR: $*" >&2; exit 1; }

confirm() {
  local msg="$1"
  if [[ "${ASSUME_YES:-0}" == "1" ]]; then
    echo "$msg — proceeding (auto-yes)."
    return 0
  fi
  read -r -p "$msg [y/N]: " ans
  [[ "${ans:-}" =~ ^[Yy]$ ]]
}

usage() {
  cat <<EOF
Usage: $(basename "$0") [options]

Options:
  --stop            Stop services (non-destructive)
  --down            docker compose down (remove containers & networks)
  --down-v          docker compose down -v (also remove volumes)
  --rm-images       Remove project images
  --full            Full cleanup: down -v + remove images + prune
  -y, --yes         Skip confirmations
  -f FILE           Path to docker-compose.yml (default: ./docker-compose.yml)
  -h, --help        Show this help

Examples:
  $(basename "$0") --stop
  $(basename "$0") --down
  $(basename "$0") --down-v --rm-images
  $(basename "$0") --full -y
EOF
}

# ---------- parse args -------------------------------------------------------
DO_STOP=0
DO_DOWN=0
DO_DOWN_V=0
DO_RM_IMAGES=0
DO_FULL=0
ASSUME_YES=0

while [[ $# -gt 0 ]]; do
  case "$1" in
    --stop) DO_STOP=1; shift ;;
    --down) DO_DOWN=1; shift ;;
    --down-v) DO_DOWN_V=1; shift ;;
    --rm-images) DO_RM_IMAGES=1; shift ;;
    --full) DO_FULL=1; shift ;;
    -y|--yes) ASSUME_YES=1; shift ;;
    -f) COMPOSE_FILE="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) die "Unknown option: $1 (use -h for help)" ;;
  esac
done

if [[ ! -f "$COMPOSE_FILE" ]]; then
  die "Compose file not found: $COMPOSE_FILE"
fi

run_dc() { docker compose -f "$COMPOSE_FILE" "$@"; }

image_exists()   { docker image inspect "$1" >/dev/null 2>&1; }
network_exists() { docker network inspect "$1" >/dev/null 2>&1; }
volume_exists()  { docker volume inspect "$1" >/dev/null 2>&1; }

# ---------- actions ----------------------------------------------------------
stop_services() {
  log "Stopping services (docker compose stop)"
  run_dc stop || true
  ok "Services stopped."
}

down_stack() {
  log "Bringing stack down (remove containers & networks)"
  run_dc down || true
  ok "Containers and compose-managed networks removed."
}

down_stack_with_volumes() {
  log "Bringing stack down WITH volumes (-v) — this deletes named volumes (DATA LOSS)"
  if confirm "This will delete volumes: ${PROJECT_VOLUMES[*]}. Continue?"; then
    run_dc down -v || true
    ok "Containers, networks and volumes removed."
  else
    warn "Skipped removing volumes."
    run_dc down || true
    ok "Containers and networks removed (volumes kept)."
  fi
}

remove_project_images() {
  log "Removing project images"
  for img in "${PROJECT_IMAGES[@]}"; do
    if image_exists "$img"; then
      echo " - Deleting image: $img"
      docker rmi -f "$img" || true
    else
      echo " - Image not present: $img"
    fi
  done
  ok "Image cleanup complete."
}

extra_prune() {
  log "Pruning dangling resources (safe)"
  docker system prune -f || true
  ok "System prune done."
}

remove_leftover_networks() {
  # Compose should remove its networks; this catches leftovers just in case.
  log "Removing leftover custom networks (if any)"
  for net in "${PROJECT_NETWORKS[@]}"; do
    if network_exists "$net"; then
      echo " - Removing network: $net"
      docker network rm "$net" || true
    else
      echo " - Network not present: $net"
    fi
  done
  ok "Network cleanup complete."
}

# ---------- main flow --------------------------------------------------------
if (( DO_FULL )); then
  log "FULL mode selected — full cleanup"
  echo "This will:"
  echo " - stop and remove containers"
  echo " - remove compose networks"
  echo " - remove named volumes (DATA LOSS)"
  echo " - remove project images: ${PROJECT_IMAGES[*]}"
  echo " - prune dangling resources"
  if confirm "Proceed with FULL cleanup?"; then
    run_dc stop || true
    down_stack_with_volumes
    remove_project_images
    remove_leftover_networks
    extra_prune
    ok "Full cleanup complete."
    exit 0
  else
    die "Aborted by user."
  fi
fi

# Non-full granular options:
# If no option provided, default to --stop (non-destructive).
if (( DO_STOP==0 && DO_DOWN==0 && DO_DOWN_V==0 && DO_RM_IMAGES==0 )); then
  DO_STOP=1
fi

(( DO_STOP ))      && stop_services
(( DO_DOWN ))      && down_stack
(( DO_DOWN_V ))    && down_stack_with_volumes
(( DO_RM_IMAGES )) && remove_project_images

ok "Done."
