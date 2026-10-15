#!/usr/bin/env bash
# ============================================
#  checker/run.sh  -  ejecuta el checker bajo xvfb (Linux)
# ============================================
set -e
cd "$(dirname "$0")/.."

if command -v xvfb-run >/dev/null 2>&1; then
  exec xvfb-run -a python3 checker/checker.py
else
  exec python3 checker/checker.py
fi