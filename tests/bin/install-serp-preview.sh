#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PLUGIN_DIR="$ROOT_DIR/tests/site/plugins/serp-preview"
TMP_DIR="$(mktemp -d)"
REPO_DIR="$TMP_DIR/kirby-serp-preview"

cleanup() {
  rm -rf "$TMP_DIR"
}

trap cleanup EXIT

git clone --depth 1 https://github.com/johannschopplich/kirby-serp-preview.git "$REPO_DIR"

mkdir -p "$PLUGIN_DIR"

rsync -a --delete \
  --exclude '.git' \
  --exclude '.github' \
  --exclude '.vscode' \
  --exclude 'node_modules' \
  --exclude 'scripts' \
  --exclude 'src/panel' \
  --exclude 'package.json' \
  --exclude 'pnpm-lock.yaml' \
  --exclude 'postcss.config.cjs' \
  --exclude 'tailwind.config.cjs' \
  --exclude 'eslint.config.mjs' \
  --exclude 'prettier.config.mjs' \
  --exclude '.php-cs-fixer.dist.php' \
  "$REPO_DIR/" "$PLUGIN_DIR/"

sed -i.bak '/^use Closure;$/d' "$PLUGIN_DIR/src/extensions/api.php"
sed -i.bak '/^use Closure;$/d' "$PLUGIN_DIR/src/extensions/sections.php"
rm -f "$PLUGIN_DIR/src/extensions/api.php.bak" "$PLUGIN_DIR/src/extensions/sections.php.bak"
