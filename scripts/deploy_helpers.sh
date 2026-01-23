#!/usr/bin/env bash
set -euo pipefail

command=${1:-}

cleanup() {
  releases_dir=${1:-}
  keep=${2:-5}

  if [[ -z "$releases_dir" || ! -d "$releases_dir" ]]; then
    echo "releases dir missing"
    exit 1
  fi

  mapfile -t releases < <(ls -1dt "${releases_dir}"/* 2>/dev/null || true)
  count=0
  for release in "${releases[@]}"; do
    count=$((count + 1))
    if (( count > keep )); then
      rm -rf "$release"
    fi
  done
}

case "$command" in
  cleanup)
    cleanup "${2:-}" "${3:-5}"
    ;;
  *)
    echo "Usage: deploy_helpers.sh cleanup <releases_dir> <keep>"
    exit 1
    ;;
esac
