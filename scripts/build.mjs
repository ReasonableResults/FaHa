#!/usr/bin/env node
/**
 * FaHa build.
 * Reads content/pages/*.md (front matter + a small markdown dialect) and emits:
 *   prototype/                      static HTML for Vercel preview
 *   wordpress/elementor-templates/  one Elementor template JSON per page
 *   wordpress/import/faha-content.xml  WXR import: pages (with Elementor data + HTML fallback), menu
 *   wordpress/wp-content/themes/faha/assets/site.css  copy of design/site.css
 * No dependencies: Node 18+.
 */
import { readFileSync, writeFileSync, mkdirSync, readdirSync, existsSync, copyFileSync, rmSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const SRC = join(ROOT, 'content/pages');
const IMG_SRC = join(ROOT, 'content/source-archive/images');
const OUT = join(ROOT, 'prototype');
const EL_OUT = join(ROOT, 'wordpress/elementor-templates');
const WXR_OUT = join(ROOT, 'wordpress/import');
const THEME_ASSETS = join(ROOT, 'wordpress/wp-content/themes/faha/assets');

const SITE = {
  name: 'Fredericksburg Area Homeschool Association',
  short: 'FaHa',
  tagline: 'Secular homeschool community, Fredericksburg VA',
  email: 'helpdesk@fahahome.com',
  address: ['10408 Courthouse Rd', 'PMB #42', 'Spotsylvania, VA 22553'],
  membership: 'https://www.zeffy.com/en-US/d/payments',
  donate: 'https://www.zeffy.com/en-US/donation-form/ongoing-support-2',
  wpUrl: 'https://fahahome.com',
};

/* ---------- front matter + markdown dialect ---------- */
function parseFrontMatter(text) {
  const m = text.match(/^---\n([\s\S]*?)\n---\n?([\s\S]*)$/);
  const meta = {};
  if (!m) return { meta, body: text };
  for (const line of m[1].split('\n')) {
    const i = line.indexOf(':');
    if (i < 0) continue;
    meta[line.slice(0, i).trim()] = line.slice(i + 1).trim();
  }
  return { meta, body: m[2] };
}

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
function inline(s) {
  s = esc(s);
  s = s.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  s = s.replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (_, t, u) => {
    const ext = /^https?:/.test(u) && !u.startsWith(SITE.wpUrl);
    return `<a href="${u}"${ext ? ' rel="noopener"' : ''}>${t}</a>`;
  });
  return s;
}
const slugify = (s) => s.toLowerCase().replace(/&amp;|&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

/** Parse body into a block tree: [{type, ...}] */
function parseBlocks(body) {
  const lines = body.split('\n');
  const blocks = [];
  let i = 0;
  // One source line is one paragraph. Content files never wrap mid-paragraph.
  const readPara = () => lines[i++].trim();
  while (i < lines.length) {
    const line = lines[i];
    if (!line.trim()) { i++; continue; }
    let m;
    if ((m = line.match(/^:::(\w+)(.*)$/))) {
      const kind = m[1];
      const args = {};
      for (const a of m[2].trim().match(/\w+=[^\s]*(?:\s+(?![a-z]+=)[^\s]*)*/g) || []) {
        const k = a.slice(0, a.indexOf('='));
        args[k] = a.slice(a.indexOf('=') + 1);
      }
      i++;
      const inner = [];
      while (i < lines.length && lines[i].trim() !== ':::') inner.push(lines[i++]);
      i++;
      blocks.push({ type: kind, args, children: parseBlocks(inner.join('\n')) });
      continue;
    }
    if ((m = line.match(/^(#{2,4}) (.*)$/))) {
      const text = m[2];
      const lm = text.match(/^\[([^\]]+)\]\(([^)]+)\)$/);
      const dash = text.split(' — ');
      blocks.push({ type: 'h', level: m[1].length, text: lm ? lm[1] : dash[0], href: lm ? lm[2] : null, sub: !lm && dash[1] ? dash[1] : null, id: slugify(lm ? lm[1] : dash[0]) });
      i++; continue;
    }
    if ((m = line.match(/^!\[([^\]]*)\]\(([^)]+)\)$/))) { blocks.push({ type: 'img', alt: m[1], src: m[2] }); i++; continue; }
    if ((m = line.match(/^— (.*)$/))) { blocks.push({ type: 'cite', text: m[1] }); i++; continue; }
    if ((m = line.match(/^Price: (.*)$/))) { blocks.push({ type: 'price', text: m[1] }); i++; continue; }
    const para = readPara();
    if (para.startsWith('LOREM:')) blocks.push({ type: 'p', placeholder: true, text: para.slice(6).trim() });
    else blocks.push({ type: 'p', text: para });
  }
  return blocks;
}

/* ---------- image resolution ---------- */
function resolveImage(id) {
  if (/^https?:|^\//.test(id)) return { src: id, placeholder: false };
  for (const ext of ['jpg', 'jpeg', 'png', 'webp']) {
    if (existsSync(join(IMG_SRC, `${id}.${ext}`))) return { src: `/assets/img/${id}.${ext}`, file: `${id}.${ext}`, placeholder: false };
  }
  return { src: `/assets/img/placeholder-person.svg`, placeholder: true };
}

/* ---------- HTML rendering (prototype + WXR fallback) ---------- */
function renderBlocks(blocks, ctx = {}) {
  let out = '';
  for (let k = 0; k < blocks.length; k++) {
    const b = blocks[k];
    switch (b.type) {
      case 'h': out += `<h${b.level} id="${b.id}">${b.href ? `<a href="${b.href}"${/^https?:/.test(b.href) ? ' rel="noopener"' : ''}>${inline(b.text)}</a>` : inline(b.text)}</h${b.level}>\n`; break;
      case 'p': out += b.placeholder ? `<p class="is-placeholder">${inline(b.text)}</p>\n` : `<p>${inline(b.text)}</p>\n`; break;
      case 'img': { const r = resolveImage(b.src); out += `<img src="${r.src}" alt="${esc(b.alt)}" loading="lazy" width="600" height="600"${r.placeholder ? ' class="placeholder-img"' : ''}>\n`; break; }
      case 'price': out += `<p class="meta">${inline(b.text)}</p>\n`; break;
      case 'cite': out += `<cite>${inline(b.text)}</cite>\n`; break;
      case 'paths': out += `<div class="grid paths">${groupCards(b.children, 'card')}</div>\n`; break;
      case 'cards': out += `<div class="grid cards">${groupCards(b.children, 'card')}</div>\n`; break;
      case 'board': out += `<div class="board">${groupCards(b.children, 'person')}</div>\n`; break;
      case 'steps': out += `<ol class="steps">${groupItems(b.children).map(g => `<li>${renderBlocks(g)}</li>`).join('')}</ol>\n`; break;
      case 'faq': out += `<div class="faq">${renderBlocks(b.children)}</div>\n`; break;
      case 'quote': out += `<blockquote class="quote">${renderBlocks(b.children)}</blockquote>\n`; break;
      case 'notice': out += `<div class="notice">${renderBlocks(b.children)}</div>\n`; break;
      case 'embed': out += renderEmbed(b.args); break;
      case 'video': out += renderVideo(b.args); break;
      default: out += renderBlocks(b.children || []);
    }
  }
  return out;
}
function groupItems(children) {
  const groups = [];
  for (const c of children) { if (c.type === 'h') groups.push([c]); else if (groups.length) groups.at(-1).push(c); else groups.push([c]); }
  return groups;
}
function groupCards(children, cls) {
  return groupItems(children).map(g => {
    const h = g[0];
    if (cls === 'person') {
      const img = g.find(x => x.type === 'img');
      const rest = g.filter(x => x !== h && x !== img);
      const r = img ? resolveImage(img.src) : resolveImage('none');
      return `<article class="person"><img src="${r.src}" alt="${esc(img ? img.alt : h.text)}" loading="lazy" width="600" height="600"${r.placeholder ? ' class="placeholder-img"' : ''}><h3>${inline(h.text)}</h3>${h.sub ? `<p class="role">${inline(h.sub)}</p>` : ''}${renderBlocks(rest)}</article>`;
    }
    // Structured "Question? answer" rows become a definition list.
    const rest = g.slice(1);
    const qa = rest.filter(x => x.type === 'p' && /^[A-Z][^?]{2,30}\? /.test(x.text));
    let body;
    if (qa.length >= 3) body = `<dl>${qa.map(x => { const q = x.text.indexOf('? '); return `<dt>${inline(x.text.slice(0, q + 1))}</dt><dd>${inline(x.text.slice(q + 2))}</dd>`; }).join('')}</dl>` + renderBlocks(rest.filter(x => !qa.includes(x)));
    else body = renderBlocks(rest);
    const title = h.href ? `<a href="${h.href}"${/^https?:/.test(h.href) ? ' rel="noopener"' : ''}>${inline(h.text)}</a>` : inline(h.text);
    return `<article class="card"><h3>${title}</h3>${body}</article>`;
  }).join('');
}
function renderEmbed({ url = '', label = 'Open embedded form' }) {
  if (!url) return `<div class="embed is-placeholder"><p class="embed-open">${esc(label)}</p></div>\n`;
  return `<div class="embed" data-embed="${esc(url)}"><p class="embed-open"><a class="btn" href="${esc(url.replace('/embed/', '/en-US/'))}" rel="noopener" data-embed-toggle>${esc(label)}</a></p><p class="embed-note">Opens here if scripts are on, otherwise in a new tab. Nothing loads until you ask for it.</p></div>\n`;
}
function videoId(url) { const m = url.match(/(?:v=|youtu\.be\/|embed\/)([\w-]{6,})/); return m ? m[1] : url; }
function renderVideo({ url = '', title = 'Video' }) {
  const id = videoId(url);
  return `<div class="video" data-video="${esc(id)}"><img src="https://i.ytimg.com/vi/${esc(id)}/hqdefault.jpg" alt="" loading="lazy" width="480" height="360"><button type="button" data-video-play aria-label="Play video: ${esc(title)}"><span>▶ Play: ${esc(title)}</span></button></div>\n`;
}

/* ---------- page shell ---------- */
const JS = `document.addEventListener('click',function(e){var t=e.target.closest('[data-embed-toggle]');if(t){var w=t.closest('[data-embed]');if(w){e.preventDefault();var f=document.createElement('iframe');f.src=w.getAttribute('data-embed');f.loading='lazy';f.title=t.textContent;w.replaceChildren(f);}}var p=e.target.closest('[data-video-play]');if(p){var v=p.closest('[data-video]');var f2=document.createElement('iframe');f2.src='https://www.youtube-nocookie.com/embed/'+v.getAttribute('data-video')+'?rel=0';f2.title=p.getAttribute('aria-label');f2.allow='encrypted-media; picture-in-picture';f2.allowFullscreen=true;v.replaceChildren(f2);}});`;

const LOGO = `<svg class="brand-mark" viewBox="0 0 44 44" aria-hidden="true"><rect width="44" height="44" rx="6" fill="#0f4c5c"/><path d="M10 30 22 12l12 18H10z" fill="#fff"/><circle cx="22" cy="27" r="3" fill="#c2410c"/></svg>`;
const PLACEHOLDER_PERSON = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600"><rect width="600" height="600" fill="#e7eeeb"/><circle cx="300" cy="230" r="110" fill="#c5d3cd"/><path d="M110 560c20-120 100-180 190-180s170 60 190 180z" fill="#c5d3cd"/><text x="300" y="585" font-family="system-ui,sans-serif" font-size="26" text-anchor="middle" fill="#46565e">photo pending</text></svg>`;

function navHtml(pages, current) {
  const top = pages.filter(p => !p.meta.parent && p.meta.hide_from_nav !== 'true').sort((a, b) => +a.meta.menu_order - +b.meta.menu_order);
  return `<ul class="nav">${top.map(p => `<li><a href="${p.url}"${p.slug === current ? ' aria-current="page"' : ''}>${esc(p.meta.nav_title || p.meta.title)}</a></li>`).join('')}</ul>`;
}
function subnavHtml(pages, page) {
  const parentSlug = page.meta.parent || (pages.some(p => p.meta.parent === page.slug) ? page.slug : null);
  if (!parentSlug) return '';
  const parent = pages.find(p => p.slug === parentSlug);
  const kids = pages.filter(p => p.meta.parent === parentSlug).sort((a, b) => +a.meta.menu_order - +b.meta.menu_order);
  return `<nav class="toc" aria-label="${esc(parent.meta.title)} pages"><strong><a href="${parent.url}">${esc(parent.meta.nav_title || parent.meta.title)}</a></strong><ol>${kids.map(k => `<li><a href="${k.url}"${k.slug === page.slug ? ' aria-current="page"' : ''}>${esc(k.meta.nav_title || k.meta.title)}</a></li>`).join('')}</ol></nav>`;
}
function heroHtml(meta, home) {
  const btn = (l, u, ghost) => l ? `<a class="btn${ghost ? ' btn--ghost' : ''}" href="${u}"${/^https?:/.test(u) ? ' rel="noopener"' : ''}>${esc(l)}</a>` : '';
  const actions = (meta.cta_label || meta.cta2_label) ? `<p class="actions">${btn(meta.cta_label, meta.cta_url)}${btn(meta.cta2_label, meta.cta2_url, true)}</p>` : '';
  return `<section class="hero${home ? ' hero--home' : ''}"><div class="wrap">${meta.hero_kicker ? `<p class="kicker">${esc(meta.hero_kicker)}</p>` : ''}<h1>${esc(meta.hero_heading || meta.title)}</h1>${meta.hero_lede ? `<p class="lede">${esc(meta.hero_lede)}</p>` : ''}${actions}</div></section>`;
}
function shell(page, pages, bodyHtml) {
  const m = page.meta;
  const home = page.slug === 'home';
  return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>${esc(home ? SITE.name : `${m.title} · ${SITE.short}`)}</title>
<meta name="description" content="${esc(m.hero_lede || SITE.tagline)}">
${m.noindex === 'true' ? '<meta name="robots" content="noindex">' : ''}
<link rel="stylesheet" href="/assets/site.css">
<link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="utility"><div class="wrap"><a href="${SITE.membership}" rel="noopener">Check membership status</a><a href="${SITE.donate}" rel="noopener">Donate</a><a href="mailto:${SITE.email}">${SITE.email}</a></div></div>
  <div class="wrap masthead">
    <a class="brand" href="/">${LOGO}<span>${SITE.name}<small>${SITE.tagline}</small></span></a>
    <nav class="nav-desktop" aria-label="Primary">${navHtml(pages, page.slug)}</nav>
    <details class="nav-toggle"><summary>Menu</summary><nav aria-label="Primary">${navHtml(pages, page.slug)}</nav></details>
  </div>
</header>
<main id="main">
${heroHtml(m, home)}
<div class="wrap content">
${subnavHtml(pages, page)}
<div class="${m.template === 'hub' || home ? '' : 'prose'}">
${bodyHtml}</div>
</div>
</main>
<footer class="site-footer"><div class="wrap">
  <div class="footer-grid">
    <div><h2>Get involved</h2><ul><li><a href="/join/">Join</a></li><li><a href="/support/">Donate</a></li><li><a href="/support/funding-request/">Funding request</a></li><li><a href="/contact/">Contact us</a></li></ul></div>
    <div><h2>Resources</h2><ul><li><a href="/resources/virginia-homeschool-basics/">Virginia basics</a></li><li><a href="/resources/unschooling/">Unschooling</a></li><li><a href="/resources/eclectic/">Eclectic</a></li><li><a href="/resources/structured-programs/">Structured programs</a></li><li><a href="/resources/homeschool-days-and-discounts/">Days and discounts</a></li></ul></div>
    <div><h2>${SITE.name}</h2><address>${SITE.address.join('<br>')}<br><a href="mailto:${SITE.email}">${SITE.email}</a></address></div>
  </div>
  <p class="fine">FaHa is a 501(c)(3) nonprofit. Secular, inclusive, member-run. What's shared in the group stays in the group.</p>
</div></footer>
<script>${JS}</script>
</body>
</html>
`;
}

/* ---------- Elementor JSON ---------- */
let idc = 0;
const eid = () => (0x100000 + (++idc * 7919) % 0xefffff).toString(16).slice(0, 7);
const W = (widgetType, settings) => ({ id: eid(), elType: 'widget', settings, elements: [], widgetType });
const CON = (elements, settings = {}) => ({ id: eid(), elType: 'container', settings: { content_width: 'boxed', ...settings }, elements, isInner: false });
const heading = (b) => W('heading', { title: b.href ? `<a href="${b.href}">${esc(b.text)}</a>` : esc(b.text), header_size: `h${b.level}`, _element_id: b.id });
const textEditor = (html) => W('text-editor', { editor: html });
function elementorBlocks(blocks) {
  const out = [];
  let buf = '';
  const flush = () => { if (buf) { out.push(textEditor(buf)); buf = ''; } };
  for (const b of blocks) {
    if (b.type === 'h' && b.level === 2) { flush(); out.push(heading(b)); }
    else if (['paths', 'cards', 'board', 'steps', 'faq', 'quote', 'notice'].includes(b.type)) { flush(); out.push(W('html', { html: renderBlocks([b]) })); }
    else if (b.type === 'embed') { flush(); out.push(W('shortcode', { shortcode: `[faha_embed url="${b.args.url || ''}" label="${(b.args.label || '').replace(/"/g, '')}"]` })); }
    else if (b.type === 'video') { flush(); out.push(W('shortcode', { shortcode: `[faha_video url="${b.args.url || ''}" title="${(b.args.title || '').replace(/"/g, '')}"]` })); }
    else buf += renderBlocks([b]);
  }
  flush();
  return out;
}
function elementorTemplate(page) {
  idc = 0;
  const m = page.meta;
  const hero = CON([
    ...(m.hero_kicker ? [W('heading', { title: esc(m.hero_kicker), header_size: 'p', _css_classes: 'kicker' })] : []),
    W('heading', { title: esc(m.hero_heading || m.title), header_size: 'h1' }),
    ...(m.hero_lede ? [textEditor(`<p class="lede">${esc(m.hero_lede)}</p>`)] : []),
    ...(m.cta_label ? [W('button', { text: m.cta_label, link: { url: m.cta_url, is_external: /^https?:/.test(m.cta_url) ? 'on' : '' }, _css_classes: 'btn' })] : []),
    ...(m.cta2_label ? [W('button', { text: m.cta2_label, link: { url: m.cta2_url }, _css_classes: 'btn btn--ghost' })] : []),
  ], { _css_classes: page.slug === 'home' ? 'hero hero--home' : 'hero', background_background: 'classic', background_color: page.slug === 'home' ? '#0f4c5c' : '#f3f6f4' });
  const body = CON(elementorBlocks(page.blocks), { _css_classes: m.template === 'hub' || page.slug === 'home' ? 'content' : 'content prose' });
  return { version: '0.4', title: m.title, type: 'page', content: [hero, body], page_settings: { hide_title: 'yes' } };
}

/* ---------- WXR ---------- */
const cdata = (s) => `<![CDATA[${String(s).replace(/]]>/g, ']]]]><![CDATA[>')}]]>`;
function wxr(pages) {
  const now = new Date();
  const date = now.toISOString().slice(0, 19).replace('T', ' ');
  let id = 100;
  const items = [];
  const byslug = {};
  for (const p of pages) { byslug[p.slug] = ++id; }
  for (const p of pages) {
    const m = p.meta;
    const el = elementorTemplate(p);
    const html = heroHtml(m, p.slug === 'home') + renderBlocks(p.blocks);
    items.push(`<item>
<title>${cdata(m.title)}</title>
<link>${SITE.wpUrl}${p.url}</link>
<pubDate>${now.toUTCString()}</pubDate>
<dc:creator>${cdata('admin')}</dc:creator>
<guid isPermaLink="false">${SITE.wpUrl}/?page_id=${byslug[p.slug]}</guid>
<description></description>
<content:encoded>${cdata(html)}</content:encoded>
<excerpt:encoded>${cdata(m.hero_lede || '')}</excerpt:encoded>
<wp:post_id>${byslug[p.slug]}</wp:post_id>
<wp:post_date>${cdata(date)}</wp:post_date>
<wp:post_date_gmt>${cdata(date)}</wp:post_date_gmt>
<wp:comment_status>${cdata('closed')}</wp:comment_status>
<wp:ping_status>${cdata('closed')}</wp:ping_status>
<wp:post_name>${cdata(p.slug)}</wp:post_name>
<wp:status>${cdata('publish')}</wp:status>
<wp:post_parent>${m.parent ? byslug[m.parent] : 0}</wp:post_parent>
<wp:menu_order>${m.menu_order || 0}</wp:menu_order>
<wp:post_type>${cdata('page')}</wp:post_type>
<wp:post_password>${cdata('')}</wp:post_password>
<wp:is_sticky>0</wp:is_sticky>
<wp:postmeta><wp:meta_key>${cdata('_elementor_edit_mode')}</wp:meta_key><wp:meta_value>${cdata('builder')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_elementor_template_type')}</wp:meta_key><wp:meta_value>${cdata('wp-page')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_elementor_version')}</wp:meta_key><wp:meta_value>${cdata('3.25.0')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_elementor_data')}</wp:meta_key><wp:meta_value>${cdata(JSON.stringify(el.content))}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_elementor_page_settings')}</wp:meta_key><wp:meta_value>${cdata('a:1:{s:10:"hide_title";s:3:"yes";}')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_wp_page_template')}</wp:meta_key><wp:meta_value>${cdata(p.slug === 'home' || m.template === 'hub' ? 'page-templates/full-width.php' : 'default')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('faha_hero_kicker')}</wp:meta_key><wp:meta_value>${cdata(m.hero_kicker || '')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('faha_hero_lede')}</wp:meta_key><wp:meta_value>${cdata(m.hero_lede || '')}</wp:meta_value></wp:postmeta>
</item>`);
  }
  // Primary menu
  const menuTermId = 5;
  const top = pages.filter(p => !p.meta.parent && p.meta.hide_from_nav !== 'true').sort((a, b) => +a.meta.menu_order - +b.meta.menu_order);
  for (const p of top) {
    const mid = ++id;
    items.push(`<item>
<title>${cdata(p.meta.nav_title || p.meta.title)}</title>
<link>${SITE.wpUrl}${p.url}</link>
<pubDate>${now.toUTCString()}</pubDate>
<dc:creator>${cdata('admin')}</dc:creator>
<guid isPermaLink="false">${SITE.wpUrl}/?p=${mid}</guid>
<description></description>
<content:encoded>${cdata('')}</content:encoded>
<excerpt:encoded>${cdata('')}</excerpt:encoded>
<wp:post_id>${mid}</wp:post_id>
<wp:post_date>${cdata(date)}</wp:post_date>
<wp:post_date_gmt>${cdata(date)}</wp:post_date_gmt>
<wp:comment_status>${cdata('closed')}</wp:comment_status>
<wp:ping_status>${cdata('closed')}</wp:ping_status>
<wp:post_name>${cdata(String(mid))}</wp:post_name>
<wp:status>${cdata('publish')}</wp:status>
<wp:post_parent>0</wp:post_parent>
<wp:menu_order>${p.meta.menu_order}</wp:menu_order>
<wp:post_type>${cdata('nav_menu_item')}</wp:post_type>
<wp:post_password>${cdata('')}</wp:post_password>
<wp:is_sticky>0</wp:is_sticky>
<category domain="nav_menu" nicename="primary">${cdata('Primary')}</category>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_type')}</wp:meta_key><wp:meta_value>${cdata('post_type')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_menu_item_parent')}</wp:meta_key><wp:meta_value>${cdata('0')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_object_id')}</wp:meta_key><wp:meta_value>${cdata(String(byslug[p.slug]))}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_object')}</wp:meta_key><wp:meta_value>${cdata('page')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_target')}</wp:meta_key><wp:meta_value>${cdata('')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_classes')}</wp:meta_key><wp:meta_value>${cdata('a:1:{i:0;s:0:"";}')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_xfn')}</wp:meta_key><wp:meta_value>${cdata('')}</wp:meta_value></wp:postmeta>
<wp:postmeta><wp:meta_key>${cdata('_menu_item_url')}</wp:meta_key><wp:meta_value>${cdata('')}</wp:meta_value></wp:postmeta>
</item>`);
  }
  return `<?xml version="1.0" encoding="UTF-8" ?>
<!-- WordPress eXtended RSS generated by scripts/build.mjs for the FaHa rebuild. Import via Tools > Import > WordPress. -->
<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">
<channel>
<title>${SITE.name}</title>
<link>${SITE.wpUrl}</link>
<description>${SITE.tagline}</description>
<pubDate>${now.toUTCString()}</pubDate>
<language>en-US</language>
<wp:wxr_version>1.2</wp:wxr_version>
<wp:base_site_url>${SITE.wpUrl}</wp:base_site_url>
<wp:base_blog_url>${SITE.wpUrl}</wp:base_blog_url>
<wp:author><wp:author_id>1</wp:author_id><wp:author_login>${cdata('admin')}</wp:author_login><wp:author_email>${cdata(SITE.email)}</wp:author_email><wp:author_display_name>${cdata('FaHa')}</wp:author_display_name></wp:author>
<wp:term><wp:term_id>${menuTermId}</wp:term_id><wp:term_taxonomy>${cdata('nav_menu')}</wp:term_taxonomy><wp:term_slug>${cdata('primary')}</wp:term_slug><wp:term_name>${cdata('Primary')}</wp:term_name></wp:term>
<generator>faha-build</generator>
${items.join('\n')}
</channel>
</rss>
`;
}

/* ---------- main ---------- */
function loadPages() {
  return readdirSync(SRC).filter(f => f.endsWith('.md')).map(f => {
    const { meta, body } = parseFrontMatter(readFileSync(join(SRC, f), 'utf8'));
    const slug = meta.slug;
    const url = slug === 'home' ? '/' : meta.parent ? `/${meta.parent}/${slug}/` : `/${slug}/`;
    return { file: f, slug, url, meta, blocks: parseBlocks(body) };
  });
}
function main() {
  const pages = loadPages();
  rmSync(OUT, { recursive: true, force: true });
  mkdirSync(join(OUT, 'assets/img'), { recursive: true });
  mkdirSync(EL_OUT, { recursive: true });
  mkdirSync(WXR_OUT, { recursive: true });
  mkdirSync(THEME_ASSETS, { recursive: true });

  const css = readFileSync(join(ROOT, 'design/site.css'), 'utf8');
  writeFileSync(join(OUT, 'assets/site.css'), css);
  writeFileSync(join(THEME_ASSETS, 'site.css'), css);
  writeFileSync(join(THEME_ASSETS, 'site.js'), JS + '\n');
  writeFileSync(join(OUT, 'assets/img/placeholder-person.svg'), PLACEHOLDER_PERSON);
  writeFileSync(join(THEME_ASSETS, 'placeholder-person.svg'), PLACEHOLDER_PERSON);
  writeFileSync(join(OUT, 'assets/img/favicon.svg'), LOGO.replace('class="brand-mark" ', 'xmlns="http://www.w3.org/2000/svg" '));
  if (existsSync(IMG_SRC)) for (const f of readdirSync(IMG_SRC)) if (/\.(jpe?g|png|webp)$/i.test(f)) copyFileSync(join(IMG_SRC, f), join(OUT, 'assets/img', f));

  const report = [];
  for (const p of pages) {
    const html = shell(p, pages, renderBlocks(p.blocks));
    const dir = p.url === '/' ? OUT : join(OUT, p.url);
    mkdirSync(dir, { recursive: true });
    writeFileSync(join(dir, 'index.html'), html);
    writeFileSync(join(EL_OUT, `${p.meta.parent ? p.meta.parent + '-' : ''}${p.slug}.json`), JSON.stringify(elementorTemplate(p), null, 1));
    const placeholders = (html.match(/is-placeholder/g) || []).length;
    report.push(`${p.url.padEnd(48)} ${String(Buffer.byteLength(html)).padStart(6)} B  placeholders: ${placeholders}`);
  }
  writeFileSync(join(OUT, '404.html'), shell({ slug: '404', url: '/404', meta: { title: 'Page not found', hero_heading: 'Page not found', hero_lede: 'The address may have changed in the rebuild. Try the menu above.' }, blocks: [] }, pages, ''));
  writeFileSync(join(WXR_OUT, 'faha-content.xml'), wxr(pages));
  console.log(report.join('\n'));
  console.log(`\n${pages.length} pages · css ${Buffer.byteLength(css)} B · js ${Buffer.byteLength(JS)} B`);
}
main();
