/**
 * Converts JSX-style objects to HTML inline style strings.
 * Run: node docs/designs/build-catalog.mjs
 * Reads artboard JSX from artboards.mjs, outputs design-catalog.html
 */

const camelToKebab = (str) =>
  str.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase()
    .replace(/^webkit/, '-webkit')
    .replace(/^moz-osx/, '-moz-osx');

function styleObjToString(obj) {
  return Object.entries(obj)
    .filter(([k]) => k !== 'boxSizing')
    .map(([k, v]) => `${camelToKebab(k)}: ${v}`)
    .join('; ');
}

function jsxToHtml(node) {
  if (typeof node === 'string') return node.trim();
  if (!node) return '';
  const { tag = 'div', style = {}, children = [] } = node;
  const styleStr = styleObjToString(style);
  const inner = children.map(c => typeof c === 'string' ? c : jsxToHtml(c)).join('\n');
  return `<div style="${styleStr}">${inner}</div>`;
}

import { artboards } from './artboards.mjs';

const sections = [
  { title: 'Design System', keys: ['ds-typography', 'ds-primitives-a', 'ds-primitives-b'] },
  { title: 'Onboarding', keys: ['magic-link', 'type-selection', 'interest-tags', 'icebreaker', 'ready-screen'] },
  { title: 'Core Experience', keys: ['presence-feed', 'participant-detail', 'mutual-match', 'sessions', 'session-detail'] },
  { title: 'Kommunikation', keys: ['connections', 'chat', 'video-call'] },
  { title: 'Discovery & Profil', keys: ['booth-discovery', 'profile', 'organizer-dashboard'] },
];

let html = `<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hybrid Presence Platform — Design Katalog</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #F5F5F5; padding: 40px; }
h1 { font-size: 28px; font-weight: 700; color: #171717; margin-bottom: 4px; }
.subtitle { color: #737373; font-size: 14px; margin-bottom: 48px; }
h2 { font-size: 20px; font-weight: 600; color: #262626; margin: 48px 0 20px; }
.row { display: flex; gap: 24px; overflow-x: auto; padding-bottom: 16px; }
figure { flex-shrink: 0; }
figcaption { text-align: center; color: #737373; font-size: 12px; margin-top: 8px; font-weight: 500; }
.frame { border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.12); }
.desktop-wrap { width: 720px; height: auto; overflow: hidden; }
.desktop-wrap .frame { transform: scale(0.5); transform-origin: top left; }
</style>
</head>
<body>
<h1>Hybrid Presence Platform — Design Katalog</h1>
<p class="subtitle">Alle Screens und Design System Komponenten</p>
`;

for (const section of sections) {
  html += `<h2>${section.title}</h2>\n<div class="row">\n`;
  for (const key of section.keys) {
    const ab = artboards[key];
    if (!ab) { html += `<!-- MISSING: ${key} -->\n`; continue; }
    const isDesktop = ab.width > 500;
    const content = ab.html;
    if (isDesktop) {
      html += `<figure><div class="desktop-wrap"><div class="frame">${content}</div></div><figcaption>${ab.name}</figcaption></figure>\n`;
    } else {
      html += `<figure><div class="frame">${content}</div><figcaption>${ab.name}</figcaption></figure>\n`;
    }
  }
  html += `</div>\n`;
}

html += `</body></html>`;

import { writeFileSync } from 'fs';
writeFileSync('docs/designs/design-catalog.html', html);
console.log('Done: docs/designs/design-catalog.html');
