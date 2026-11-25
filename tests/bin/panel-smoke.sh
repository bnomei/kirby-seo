#!/usr/bin/env bash
# Run against the disposable fixture in this orb, never a live Kirby installation.
set -euo pipefail
amp orb services ensure
panel=$(jq -er '.links[0].url' .amp/portals/panel.json)
site=${panel%/panel}
export AGENT_BROWSER_SESSION="seo-smoke-$$"
ab() { agent-browser "$@"; }
trap 'ab close >/dev/null' EXIT

ab open "$panel"
ab set viewport 1280 780 2
ab wait 'input[type="email"]'
ab fill 'input[type="email"]' editor@example.test
ab fill 'input[type="password"]' "$(<tests/runtime/panel-password)" >/dev/null
ab find role button click --name 'Log in'
ab wait 'button[aria-label="SEO"]'
ab click 'button[aria-label="SEO"]'
ab wait --text 'Sitemap: Included'
ab press Escape
ab find role link click --name Listed
ab wait --text 'Search preview'

action() {
  ab click 'button[aria-label="SEO"]'
  ab wait "a[href*=\"/action/$1\"]"
  ab click "a[href*=\"/action/$1\"]"
}

ab wait '.k-seo-view-button'
if [[ $(ab get count '.k-seo-view-button--problem') == 1 ]]; then
  action allow
  ab wait '.k-seo-view-button--ok'
fi
action hide
ab wait '.k-seo-view-button--problem'
ab reload
ab wait '.k-seo-view-button--problem'
html=$(curl -fsS "$site/listed")
xml=$(curl -fsS "$site/sitemap.xml")
[[ $html == *'content="noindex, nofollow"'* && $xml == *'</urlset>'* && $xml != *"<loc>$site/listed</loc>"* ]]
action allow
ab wait '.k-seo-view-button--ok'
html=$(curl -fsS "$site/listed")
xml=$(curl -fsS "$site/sitemap.xml")
[[ $html == *'content="index, follow"'* && $xml == *"<loc>$site/listed</loc>"* ]]
echo 'Panel hide/allow persisted across requests and matched live HTML and sitemap.'
