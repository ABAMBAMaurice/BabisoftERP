'use strict';

/* ═══════════════════════════════════════════════════════════════════
   BabiSoft ERP — Metadata-driven SPA
   ═══════════════════════════════════════════════════════════════════ */

// ── Config ────────────────────────────────────────────────────────
const API_ROOT = (() => {
  const p = window.location.pathname;
  const i = p.lastIndexOf('/public/');
  return (i >= 0 ? p.substring(0, i) : '') + '/';
})();

const TOKEN_KEY  = 'bs_token';
const LOGIN_URL  = '../login/index.html';

// ── Auth guard — appelé avant init() ─────────────────────────────
const AUTH_TOKEN = localStorage.getItem(TOKEN_KEY) ?? '';
if (!AUTH_TOKEN) {
  window.location.replace(LOGIN_URL);
  throw new Error('Redirect to login');
}

const MODULES = [
  { name: 'Comptabilité', icon: 'book-open',    min: 60000,  max: 60999,  color: '#0078d4' },
  { name: 'Stock',        icon: 'package',       min: 61000,  max: 61999,  color: '#107c10' },
  { name: 'Ventes',       icon: 'shopping-cart', min: 62000,  max: 62999,  color: '#d83b01' },
  { name: 'Système',      icon: 'settings',      min: 999000, max: 9999999, color: '#8764b8' },
];

// ── App State ─────────────────────────────────────────────────────
const S = {
  pages:     [],
  schema:    null,
  rawData:   null,
  records:   [],
  record:    {},
  columns:   [],
  dirty:     {},
  isNew:     false,
  selRow:    -1,
  sortField: null,
  sortDir:   'asc',
};

// ── SVG Icons (inline for dynamic content) ───────────────────────
function ico(name, size = 14) {
  if (typeof feather === 'undefined') return '';
  return feather.icons[name]?.toSvg({ width: size, height: size, 'stroke-width': 1.8 }) ?? '';
}

// ── Page icon — assigned by caption keywords ──────────────────────
function pageIcon(page) {
  if (page.type === 'RoleCenter') return 'home';
  const c = (page.caption ?? '').toLowerCase();
  if (c.includes('client') || c.includes('contact'))       return 'users';
  if (c.includes('commande') || c.includes('devis'))       return 'shopping-cart';
  if (c.includes('facture'))                               return 'file-text';
  if (c.includes('relance'))                               return 'bell';
  if (c.includes('article') && !c.includes('grand'))       return 'package';
  if (c.includes('mouvement') || c.includes('transfert'))  return 'truck';
  if (c.includes('inventaire'))                            return 'clipboard';
  if (c.includes('plan comptable') || c.includes('compte')) return 'book';
  if (c.includes('grand livre') || c.includes('écriture')) return 'book-open';
  if (c.includes('saisie') || c.includes('pièce'))         return 'edit-3';
  if (c.includes('licence'))                               return 'key';
  if (c.includes('utilisateur') || c.includes('user'))     return 'user';
  if (c.includes('profil') || c.includes('rôle'))          return 'shield';
  if (c.includes('souche') || c.includes('numéro'))        return 'hash';
  if (c.includes('paiement') || c.includes('règlement'))   return 'credit-card';
  if (c.includes('fournisseur'))                           return 'briefcase';
  if (c.includes('banque'))                                return 'credit-card';
  return page.type === 'List' ? 'list' : 'file';
}

// ── Utils ─────────────────────────────────────────────────────────
function getModule(id) {
  return MODULES.find(m => id >= m.min && id <= m.max)
      ?? { name: 'Module', icon: 'grid', color: '#605e5c' };
}

function isBoolStr(v) {
  return v === '0' || v === '1' || v === 'true' || v === 'false';
}
function toBool(v) {
  return v === '1' || v === 'true';
}

function esc(str) {
  return String(str ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;')
    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function fmtNum(v) {
  const n = parseFloat(v);
  if (isNaN(n)) return v;
  return n.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function keyParams(schema, record) {
  const p = {};
  for (const k of (schema.key ?? [])) {
    if (record[k] !== undefined) p[k] = record[k];
  }
  return p;
}

function toQS(params) {
  return Object.entries(params)
    .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    .join('&');
}

function fromQS(qs) {
  const p = {};
  for (const part of qs.split('&')) {
    const [k, v] = part.split('=');
    if (k) p[decodeURIComponent(k)] = decodeURIComponent(v ?? '');
  }
  return p;
}

function recCountLabel(n) {
  return `${n.toLocaleString('fr-FR')} enregistrement${n !== 1 ? 's' : ''}`;
}

// ── API ───────────────────────────────────────────────────────────
async function apiFetch(path, opts = {}) {
  const headers = {
    'Content-Type':  'application/json',
    'Accept':        'application/json',
    'Authorization': `Bearer ${AUTH_TOKEN}`,
    ...(opts.headers ?? {}),
  };
  const res = await fetch(API_ROOT + path, { ...opts, headers });
  if (res.status === 401 || res.status === 403) {
    // Session expirée ou invalide → déconnexion forcée
    localStorage.removeItem(TOKEN_KEY);
    window.location.replace(LOGIN_URL);
    throw new Error('Session expirée');
  }
  const json = await res.json().catch(() => ({ message: res.statusText }));
  if (!res.ok) throw new Error(json.message ?? `HTTP ${res.status}`);
  return json;
}

const Api = {
  pages:     ()          => apiFetch('meta/pages'),
  schema:    (id)        => apiFetch(`meta/pages/${id}/schema`),
  data:      (id, qs='') => apiFetch(`meta/pages/${id}${qs ? '?' + qs : ''}`),
  create:    (id, body)  => apiFetch(`meta/pages/${id}/record`, { method: 'POST', body: JSON.stringify(body) }),
  update:    (id, body)  => apiFetch(`meta/pages/${id}/record`, { method: 'PUT',  body: JSON.stringify(body) }),
  remove:    (id, body)  => apiFetch(`meta/pages/${id}/record`, { method: 'DELETE', body: JSON.stringify(body) }),
  action:    (id, name, body) => apiFetch(`meta/pages/${id}/action/${name}`, { method: 'POST', body: JSON.stringify(body) }),
};

// ── Toast ─────────────────────────────────────────────────────────
const TOAST_META = {
  ok:  { icon: 'check-circle' },
  err: { icon: 'alert-circle' },
  wrn: { icon: 'alert-triangle' },
  '':  { icon: 'info' },
};

const Toast = {
  show(msg, cls = '', dur = 4500) {
    const meta = TOAST_META[cls] ?? TOAST_META[''];
    const el = document.createElement('div');
    el.className = `toast ${cls}`;
    el.innerHTML = `
      <span class="toast-ico ${cls || 'inf'}">${ico(meta.icon, 15)}</span>
      <span class="toast-msg">${esc(msg)}</span>
      <button class="toast-x" onclick="this.closest('.toast').remove()">×</button>`;
    document.getElementById('toasts').appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; }, dur - 350);
    setTimeout(() => el.remove(), dur);
  },
  ok:  (m) => Toast.show(m, 'ok'),
  err: (m) => Toast.show(m, 'err', 7000),
  wrn: (m) => Toast.show(m, 'wrn'),
};

// ── Modal ─────────────────────────────────────────────────────────
const Modal = {
  show(title, bodyHtml, btns = []) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('modal-body').innerHTML = bodyHtml;
    document.getElementById('modal-footer').innerHTML = btns.map(b =>
      `<button class="btn ${esc(b.cls ?? 'btn-default')}"
        onclick="${esc(b.fn)}">${esc(b.label)}</button>`
    ).join('');
    document.getElementById('modal').classList.remove('hidden');
  },
  close() {
    document.getElementById('modal').classList.add('hidden');
  },
  confirm(title, msg, onOk) {
    Modal._pending = onOk;
    Modal.show(title, `<p>${esc(msg)}</p>`, [
      { label: 'Annuler',   cls: 'btn-default', fn: 'Modal.close()' },
      { label: 'Confirmer', cls: 'btn-danger',  fn: 'Modal._pending(); Modal.close();' },
    ]);
  },
  _pending: () => {},
};
window.Modal = Modal;

// ── Breadcrumb ────────────────────────────────────────────────────
const Crumb = {
  set(items) {
    const nav = document.getElementById('breadcrumb');
    if (!nav) return;
    nav.innerHTML = items.map((item, i) => {
      const last = i === items.length - 1;
      return last
        ? `<span class="bc-crumb current">${esc(item.label)}</span>`
        : `<span class="bc-crumb" onclick="Nav.go('${item.hash}')">${esc(item.label)}</span>
           <span class="bc-sep">›</span>`;
    }).join('');
  },
};

// ── Sidebar nav ───────────────────────────────────────────────────
function buildSidebar(pages) {
  const visible = pages.filter(p => p.type === 'List' || p.type === 'RoleCenter');

  const groups = {};
  for (const p of visible) {
    const mod = getModule(p.id);
    if (!groups[mod.name]) groups[mod.name] = { ...mod, items: [] };
    groups[mod.name].items.push(p);
  }

  const nav = document.getElementById('sidebar-nav');
  nav.innerHTML = Object.values(groups).map(g => `
    <div class="sb-module" data-mod="${esc(g.name)}">
      <button class="sb-module-hd" onclick="toggleMod(this)">
        <span class="sb-dot" style="background:${g.color}"></span>
        <span class="sb-module-name">${esc(g.name)}</span>
        <span class="sb-chevron">${ico('chevron-down', 12)}</span>
      </button>
      <div class="sb-items">
        ${g.items.map(p => `
          <button class="sb-item" data-page="${p.id}" onclick="Nav.toPage(${p.id})">
            <span class="sb-item-ico">${ico(pageIcon(p), 14)}</span>
            <span class="sb-item-label">${esc(p.caption)}</span>
          </button>
        `).join('')}
      </div>
    </div>
  `).join('');

  feather.replace({ 'stroke-width': 1.8 });
}

function toggleMod(btn) {
  btn.classList.toggle('collapsed');
  const items = btn.nextElementSibling;
  items.style.display = btn.classList.contains('collapsed') ? 'none' : '';
}
window.toggleMod = toggleMod;

function setActiveNav(pageId) {
  document.querySelectorAll('.sb-item').forEach(el => {
    el.classList.toggle('active', el.dataset.page == pageId);
  });
}

// ── Navigation ────────────────────────────────────────────────────
const Nav = {
  toPage: (id)           => { location.hash = `#page/${id}`; },
  toCard: (id, params)   => { location.hash = `#page/${id}/${toQS(params)}`; },
  toNew:  (id)           => { location.hash = `#page/${id}/new`; },
  go:     (hash)         => { location.hash = hash; },

  parse(hash = location.hash) {
    const m = hash.replace(/^#/, '').match(/^page\/(\d+)(?:\/(.+))?$/);
    if (!m) return null;
    return { pageId: +m[1], rest: m[2] ?? null };
  },
};
window.Nav = Nav;

// ── Loader ────────────────────────────────────────────────────────
function showLoader(caption = '') {
  document.getElementById('page-root').innerHTML = `
    <div class="loader">
      <div class="spinner"></div>
      <p>${caption ? esc(caption) + '…' : 'Chargement…'}</p>
    </div>`;
}

// ── Page header HTML ──────────────────────────────────────────────
function pageHdHtml(caption, sub = '', iconName = '') {
  return `
    <div class="page-hd">
      <div class="page-title-row">
        ${iconName ? `<div class="page-title-icon">${ico(iconName, 16)}</div>` : ''}
        <div class="page-title-text">
          <div class="page-title">${esc(caption)}</div>
          ${sub ? `<div class="page-sub">${esc(sub)}</div>` : ''}
        </div>
      </div>
      <div class="action-bar" id="action-bar"></div>
    </div>`;
}

// ── Action bar ────────────────────────────────────────────────────
function fillActionBar(schema, context = 'list') {
  const bar = document.getElementById('action-bar');
  if (!bar) return;

  const parts = [];

  if (context === 'card') {
    parts.push(`<button class="btn btn-ghost" onclick="history.back()">${ico('arrow-left')} Retour</button>`);
    parts.push('<div class="action-sep"></div>');
  }

  if (schema.allowInsert !== false) {
    const targetId = context === 'list' ? (schema.cardPage ?? schema.id) : schema.id;
    parts.push(`<button class="btn btn-primary" onclick="App.onNew(${targetId})">${ico('plus')} Nouveau</button>`);
  }
  if (context === 'card' && schema.allowDelete !== false && !S.isNew) {
    parts.push(`<button class="btn btn-ghost text-danger" onclick="App.onDelete()">${ico('trash-2')} Supprimer</button>`);
  }

  if ((schema.actions ?? []).length > 0) {
    parts.push('<div class="action-sep"></div>');
  }

  App._actions = {};
  for (const a of (schema.actions ?? [])) {
    App._actions[a.name] = a;
    const cls = `btn ${a.style ?? 'inverse-dark'}`;
    const ic  = a.icon ? ico(a.icon) : '';
    parts.push(
      `<button class="${cls}" onclick="App.runAction('${esc(a.name)}')">${ic} ${esc(a.caption)}</button>`
    );
  }

  bar.innerHTML = parts.join('');
  feather.replace({ 'stroke-width': 1.8 });
}

// ══════════════════════════════════════════════════════════════════
// RENDERERS
// ══════════════════════════════════════════════════════════════════

// ── List renderer ─────────────────────────────────────────────────
function renderList(schema, records) {
  S.selRow = -1;

  const repeater = schema.layout.find(g => g.type === 'repeater') ?? schema.layout[0];
  const cols     = repeater?.fields
    ?? (records[0] ? Object.keys(records[0]).map(k => ({ field: k, caption: k })) : []);
  S.columns = cols;

  const theadCols = cols.map(c =>
    `<th class="${c.align === 'right' ? 'cr' : c.align === 'center' ? 'ca' : ''}"
         data-field="${esc(c.field)}"
         style="${c.width ? `width:${c.width}px` : ''}"
         onclick="App.sortCol('${esc(c.field)}', this)">
       ${esc(c.caption ?? c.field)}
     </th>`
  ).join('');

  const tbody = buildTbody(records, cols);
  const label = recCountLabel(records.length);
  const modIcon = pageIcon(schema);

  document.getElementById('page-root').innerHTML = `
    <div class="bc-page">
      ${pageHdHtml(schema.caption, '', modIcon)}
      <div class="page-toolbar">
        <div class="search-wrap">
          ${ico('search', 14)}
          <input class="search-input" type="text" id="list-search"
                 placeholder="Filtrer les résultats…"
                 oninput="App.onSearch(this.value)" autocomplete="off">
        </div>
        <button class="btn btn-ghost" onclick="App.reload()" title="Actualiser">
          ${ico('refresh-cw', 14)}
        </button>
        <span class="toolbar-count" id="rec-count">${label}</span>
      </div>
      <div class="page-body">
        <table class="bc-tbl">
          <thead><tr>${theadCols}</tr></thead>
          <tbody id="tbl-body">${tbody}</tbody>
        </table>
        ${records.length === 0 ? `
          <div class="empty">
            <div class="empty-ico">${ico('inbox', 24)}</div>
            <h3>Aucun enregistrement</h3>
            <p>Cette liste est vide. Cliquez sur <strong>Nouveau</strong> pour créer le premier enregistrement.</p>
          </div>` : ''}
      </div>
      <div class="list-foot">
        <span>${ico('database', 12)} <span id="rec-foot">${label}</span></span>
      </div>
    </div>`;

  fillActionBar(schema, 'list');
  feather.replace({ 'stroke-width': 1.8 });
}

function buildTbody(records, cols) {
  return records.map((rec, i) => {
    const cells = cols.map(c => {
      const raw  = String(rec[c.field] ?? '');
      const isB  = isBoolStr(raw);
      const isR  = c.align === 'right' || c.type === 'decimal' || c.type === 'integer';
      const cls  = [
        isR                  ? 'cr' : c.align === 'center' ? 'ca' : '',
        c.style === 'strong' ? 'cs' : '',
      ].filter(Boolean).join(' ');

      let content;
      if (isB) {
        content = toBool(raw)
          ? `<span class="c-bool-y">${ico('check', 12)}</span>`
          : `<span class="c-bool-n">—</span>`;
      } else if (isR && raw !== '' && !isNaN(raw)) {
        content = c.style === 'strong'
          ? `<strong>${fmtNum(raw)}</strong>`
          : fmtNum(raw);
      } else {
        content = c.style === 'strong' ? `<strong>${esc(raw)}</strong>` : esc(raw);
      }
      return `<td class="${cls}">${content}</td>`;
    }).join('');

    return `<tr data-i="${i}" onclick="App.rowClick(${i})">${cells}</tr>`;
  }).join('');
}

// ── Card renderer ─────────────────────────────────────────────────
function renderCard(schema, record, isNew = false) {
  S.dirty  = {};
  S.isNew  = isNew;
  S.record = record;

  const keyVal  = schema.key?.[0] ? (record[schema.key[0]] ?? '') : '';
  const sub     = isNew ? 'Nouveau' : String(keyVal);
  const modIcon = pageIcon(schema);

  const groups = schema.layout.filter(g => (g.type ?? 'group') !== 'repeater');

  const tabsHtml = groups.map((g, gi) => {
    const fieldsHtml = (g.fields ?? []).map(f => cfHtml(f, record[f.field], schema)).join('');
    const fieldCount = (g.fields ?? []).length;
    return `
      <div class="fasttab" id="ft${gi}">
        <div class="ft-hd" onclick="toggleFT(${gi})">
          <span class="ft-title">${esc(g.caption ?? g.name)}</span>
          <span class="ft-count">${fieldCount}</span>
          <span class="ft-chevron">${ico('chevron-up', 14)}</span>
        </div>
        <div class="ft-body" id="ftb${gi}">${fieldsHtml}</div>
      </div>`;
  }).join('');

  document.getElementById('page-root').innerHTML = `
    <div class="bc-page">
      ${pageHdHtml(schema.caption, sub, modIcon)}
      <div class="save-bar hidden" id="save-bar">
        <span class="save-bar-msg">${ico('alert-circle', 14)} Modifications non enregistrées</span>
        <button class="btn btn-primary" onclick="App.onSave()">${ico('save')} Enregistrer</button>
        <button class="btn btn-default" onclick="App.onDiscard()">Ignorer</button>
      </div>
      <div class="card-body">${tabsHtml}</div>
    </div>`;

  fillActionBar(schema, 'card');
  feather.replace({ 'stroke-width': 1.8 });
}

function cfHtml(fieldMeta, value, schema) {
  const name      = fieldMeta.field;
  const caption   = fieldMeta.caption ?? name;
  const editable  = (fieldMeta.editable !== false) && (schema.editable !== false) && !fieldMeta.flowField;
  const raw       = String(value ?? '');
  const isB       = isBoolStr(raw);
  const cls       = [
    'cf-val',
    fieldMeta.style === 'strong' ? 'fw7' : '',
    fieldMeta.align === 'right'  ? 'ar'  : '',
    !editable ? 'readonly' : '',
  ].filter(Boolean).join(' ');

  let inner;
  if (isB) {
    const checked = toBool(raw);
    inner = `<label class="cf-bool">
      <input type="checkbox" ${checked ? 'checked' : ''} ${!editable ? 'disabled' : ''}
             onchange="App.fieldChange('${esc(name)}', this.checked?'1':'0')">
      <span>${checked ? 'Oui' : 'Non'}</span>
    </label>`;
  } else {
    inner = `<input class="${cls}"
      type="text" name="${esc(name)}"
      value="${esc(raw)}"
      ${!editable ? 'readonly' : ''}
      ${!editable ? 'tabindex="-1"' : ''}
      oninput="App.fieldChange('${esc(name)}', this.value)">`;
  }

  return `<div class="cf" id="cf-${esc(name)}" data-field="${esc(name)}">
    <span class="cf-label">${esc(caption)}</span>
    ${inner}
  </div>`;
}

function toggleFT(gi) {
  const hd  = document.querySelector(`#ft${gi} .ft-hd`);
  const body = document.getElementById(`ftb${gi}`);
  hd.classList.toggle('closed');
  body.classList.toggle('hidden');
}
window.toggleFT = toggleFT;

// ── RoleCenter renderer ───────────────────────────────────────────
function renderRoleCenter(schema, widgets) {
  const widgetsHtml = widgets.map(w => {
    const mod = w.linkedPageId ? getModule(w.linkedPageId) : { color: '#0078d4' };
    const wcIcon = w.icon ?? 'bar-chart-2';
    return `
    <div class="rc-widget" onclick="${w.linkedPageId ? `Nav.toPage(${w.linkedPageId})` : ''}">
      <div class="rc-widget-ico" style="background:${mod.color}20; color:${mod.color}">
        ${ico(wcIcon, 16)}
      </div>
      <div class="rc-val">${esc(String(w.value ?? 0))}</div>
      <div class="rc-cap">${esc(w.caption)}</div>
    </div>`;
  }).join('');

  document.getElementById('page-root').innerHTML = `
    <div class="bc-page">
      ${pageHdHtml(schema.caption, '', 'home')}
      <div class="rc-body">
        <div class="rc-section">
          <div class="rc-section-title">Indicateurs</div>
          <div class="rc-grid">${widgetsHtml || '<p class="muted">Aucun indicateur disponible.</p>'}</div>
        </div>
      </div>
    </div>`;

  fillActionBar(schema);
  feather.replace({ 'stroke-width': 1.8 });
}

// ══════════════════════════════════════════════════════════════════
// APP CONTROLLER
// ══════════════════════════════════════════════════════════════════
const App = {
  _schema:  null,
  _pageId:  null,
  _qs:      '',
  _actions: {},

  async load(pageId, qs = '') {
    showLoader();
    setActiveNav(pageId);
    this._pageId = pageId;
    this._qs     = qs;

    try {
      const [sr, dr] = await Promise.all([
        Api.schema(pageId),
        Api.data(pageId, qs),
      ]);

      const schema = sr.schema;
      this._schema = schema;
      S.schema     = schema;
      S.rawData    = dr;

      const mod = getModule(pageId);
      const modLabel = document.getElementById('module-label');
      if (modLabel) modLabel.textContent = mod.name;

      const type = dr.PageType ?? schema.type;

      if (type === 'List' || type === 'ListPart') {
        S.records = Array.isArray(dr.record) ? dr.record : [];
        renderList(schema, S.records);
        Crumb.set([
          { label: mod.name,       hash: '#' },
          { label: schema.caption, hash: `#page/${pageId}` },
        ]);

      } else if (type === 'Card' || type === 'Document') {
        const record = (dr.record && typeof dr.record === 'object' && !Array.isArray(dr.record))
          ? dr.record : {};
        S.record = record;
        const isNew = Object.keys(record).length === 0;
        renderCard(schema, record, isNew);

        const keyVal = schema.key?.[0] ? (record[schema.key[0]] ?? 'Nouveau') : schema.caption;
        Crumb.set([
          { label: mod.name,       hash: '#' },
          { label: schema.caption, hash: `#page/${schema.cardPage ?? pageId}` },
          { label: String(keyVal), hash: `#page/${pageId}/${qs}` },
        ]);

      } else if (type === 'RoleCenter') {
        const widgets = Array.isArray(dr.record) ? dr.record : [];
        renderRoleCenter(schema, widgets);
        Crumb.set([{ label: schema.caption, hash: `#page/${pageId}` }]);
      }

    } catch (err) {
      document.getElementById('page-root').innerHTML = `
        <div class="err-page">
          <div class="err-ico">${ico('alert-circle', 28)}</div>
          <h3>Erreur de chargement</h3>
          <p>${esc(err.message)}</p>
          <button class="btn btn-default" onclick="App.load(${pageId},'${qs}')">
            ${ico('refresh-cw')} Réessayer
          </button>
        </div>`;
      console.error('[App.load]', err);
    }
  },

  // ── Reload current page ────────────────────────────────────────
  reload() {
    if (this._pageId) this.load(this._pageId, this._qs);
  },

  // ── Load for new record ────────────────────────────────────────
  async loadNew(pageId) {
    showLoader('Préparation du formulaire');
    setActiveNav(pageId);
    this._pageId = pageId;
    this._qs     = '';
    S.isNew      = true;

    try {
      const sr = await Api.schema(pageId);
      const schema = sr.schema;
      this._schema = schema;
      S.schema     = schema;

      const mod = getModule(pageId);
      const modLabel = document.getElementById('module-label');
      if (modLabel) modLabel.textContent = mod.name;

      renderCard(schema, {}, true);
      Crumb.set([
        { label: mod.name,       hash: '#' },
        { label: schema.caption, hash: `#page/${pageId}` },
        { label: 'Nouveau',      hash: `#page/${pageId}/new` },
      ]);
    } catch (err) {
      Toast.err(err.message);
      history.back();
    }
  },

  // ── Row click on List ─────────────────────────────────────────
  rowClick(i) {
    document.querySelectorAll('.bc-tbl tbody tr').forEach(r => r.classList.remove('sel'));
    document.querySelector(`.bc-tbl tbody tr[data-i="${i}"]`)?.classList.add('sel');
    S.selRow = i;

    const schema = this._schema;
    if (!schema?.cardPage) return;
    const rec    = S.records[i];
    if (!rec) return;
    const params = keyParams(schema, rec);
    if (Object.keys(params).length > 0) Nav.toCard(schema.cardPage, params);
  },

  // ── New ───────────────────────────────────────────────────────
  onNew(targetPageId) {
    const id = targetPageId ?? this._schema?.cardPage ?? this._pageId;
    if (id) Nav.toNew(id);
  },

  // ── Field change ──────────────────────────────────────────────
  fieldChange(name, val) {
    S.dirty[name] = val;

    const cfEl = document.getElementById(`cf-${name}`);
    cfEl?.classList.add('dirty');

    if (isBoolStr(val)) {
      const span = cfEl?.querySelector('.cf-bool span');
      if (span) span.textContent = toBool(val) ? 'Oui' : 'Non';
    }

    document.getElementById('save-bar')?.classList.remove('hidden');
  },

  // ── Save ──────────────────────────────────────────────────────
  async onSave() {
    const schema = this._schema;
    if (!schema) return;
    if (Object.keys(S.dirty).length === 0) { Toast.wrn('Aucune modification à enregistrer.'); return; }

    let body = { ...S.dirty };

    if (!S.isNew) {
      for (const k of (schema.key ?? [])) {
        if (S.record[k] !== undefined) body[k] = S.record[k];
      }
    }

    try {
      const res = S.isNew
        ? await Api.create(schema.id, body)
        : await Api.update(schema.id, body);

      Toast.ok(res.message ?? 'Enregistrement sauvegardé.');

      const wasNew = S.isNew;
      S.dirty  = {};
      S.isNew  = false;

      document.getElementById('save-bar')?.classList.add('hidden');
      document.querySelectorAll('.cf.dirty').forEach(el => el.classList.remove('dirty'));

      if (wasNew) {
        const keyField = schema.key?.[0];
        if (keyField && body[keyField]) {
          Nav.toCard(schema.id, { [keyField]: body[keyField] });
        } else {
          history.back();
        }
      } else {
        App.load(schema.id, this._qs);
      }
    } catch (err) {
      Toast.err('Erreur : ' + err.message);
    }
  },

  // ── Discard ───────────────────────────────────────────────────
  onDiscard() {
    if (Object.keys(S.dirty).length === 0) { history.back(); return; }
    Modal.confirm(
      'Ignorer les modifications ?',
      'Les modifications non enregistrées seront perdues.',
      () => { S.dirty = {}; App.load(this._schema?.id ?? this._pageId, this._qs); }
    );
  },

  // ── Delete ────────────────────────────────────────────────────
  onDelete() {
    const schema = this._schema;
    const rec    = S.record;
    if (!schema || !rec) return;

    const kp  = keyParams(schema, rec);
    const lbl = Object.values(kp).join(', ') || 'cet enregistrement';

    Modal.confirm(
      'Supprimer l\'enregistrement',
      `Supprimer <strong>${esc(lbl)}</strong> ? Cette action est irréversible.`,
      async () => {
        try {
          await Api.remove(schema.id, kp);
          Toast.ok('Enregistrement supprimé.');
          history.back();
        } catch (err) {
          Toast.err('Erreur : ' + err.message);
        }
      }
    );
  },

  // ── Run custom action ─────────────────────────────────────────
  async runAction(name) {
    const meta   = this._actions[name];
    const schema = this._schema;
    if (!meta || !schema) return;

    const doRun = async () => {
      try {
        const body = { ...S.record, ...S.dirty };
        const res  = await Api.action(schema.id, name, body);

        if (res.navigate) {
          const { pageId, params } = res.navigate;
          Nav.toCard(pageId, params ?? {});
          return;
        }
        if (res.data) {
          Modal.show(meta.caption, `
            <pre style="font-size:12px;overflow:auto;max-height:380px;white-space:pre-wrap;background:var(--surface-3);padding:12px;border-radius:var(--r-sm)">${esc(JSON.stringify(res.data, null, 2))}</pre>`,
            [{ label: 'Fermer', cls: 'btn-default', fn: 'Modal.close()' }]
          );
          return;
        }

        Toast.ok(res.message ?? 'Action effectuée.');
        if (res.refreshPage) App.load(schema.id, this._qs);
      } catch (err) {
        Toast.err('Erreur : ' + err.message);
      }
    };

    if (meta.confirm) {
      Modal.confirm(meta.caption, meta.confirm, doRun);
    } else {
      await doRun();
    }
  },

  // ── List search ───────────────────────────────────────────────
  onSearch(q) {
    const term = q.toLowerCase().trim();
    const rows = document.querySelectorAll('.bc-tbl tbody tr[data-i]');
    let   n    = 0;

    rows.forEach((row, i) => {
      const rec   = S.records[i];
      const match = !term || Object.values(rec ?? {}).some(v => String(v).toLowerCase().includes(term));
      row.style.display = match ? '' : 'none';
      if (match) n++;
    });

    const label = recCountLabel(n);
    const cnt = document.getElementById('rec-count');
    const ft  = document.getElementById('rec-foot');
    if (cnt) cnt.textContent = label;
    if (ft)  ft.textContent  = label;
  },

  // ── Logout ────────────────────────────────────────────────────
  async logout() {
    try {
      await fetch(API_ROOT + 'auth/logout', {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${AUTH_TOKEN}`, 'Accept': 'application/json' },
      });
    } catch {}
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem('bs_tenant');
    localStorage.removeItem('bs_name');
    localStorage.removeItem('bs_admin');
    window.location.replace(LOGIN_URL);
  },

  // ── Sort column ───────────────────────────────────────────────
  sortCol(field, th) {
    const prev = th.classList.contains('asc') ? 'asc' : th.classList.contains('desc') ? 'desc' : null;
    const dir  = prev === 'asc' ? 'desc' : 'asc';

    document.querySelectorAll('.bc-tbl th').forEach(h => h.classList.remove('asc', 'desc'));
    th.classList.add(dir);

    S.records.sort((a, b) => {
      const va = String(a[field] ?? '');
      const vb = String(b[field] ?? '');
      const n  = !isNaN(va) && !isNaN(vb);
      const cmp = n ? +va - +vb : va.localeCompare(vb, 'fr', { sensitivity: 'base' });
      return dir === 'asc' ? cmp : -cmp;
    });

    const tbody = document.getElementById('tbl-body');
    if (tbody) {
      tbody.innerHTML = buildTbody(S.records, S.columns);
      feather.replace({ 'stroke-width': 1.8 });
    }
  },
};
window.App = App;

// ── Global Search ─────────────────────────────────────────────────
const Search = {
  _idx:  0,
  _hits: [],

  toggle() {
    const ov = document.getElementById('search-overlay');
    const hidden = ov.classList.toggle('hidden');
    if (!hidden) {
      const inp = document.getElementById('search-input');
      inp.value = '';
      inp.focus();
      this.query('');
    }
  },

  query(q) {
    const term  = q.toLowerCase().trim();
    this._hits  = term
      ? S.pages.filter(p =>
          p.caption.toLowerCase().includes(term) ||
          p.name.toLowerCase().includes(term)
        )
      : S.pages.filter(p => p.type === 'List' || p.type === 'RoleCenter');

    this._hits  = this._hits.slice(0, 10);
    this._idx   = 0;
    this._render();
  },

  _render() {
    const el = document.getElementById('search-results');
    if (!el) return;

    if (!this._hits.length) {
      el.innerHTML = `<div style="padding:24px;text-align:center;color:var(--tx-3);font-size:13px">Aucun résultat</div>`;
      return;
    }

    el.innerHTML = this._hits.map((p, i) => {
      const mod = getModule(p.id);
      return `
      <div class="sr-item ${i === this._idx ? 'focused' : ''}" onclick="Search.pick(${i})">
        <div class="sr-item-ico">${ico(pageIcon(p), 14)}</div>
        <span class="sr-caption">${esc(p.caption)}</span>
        <span class="sr-type" style="color:${mod.color};background:${mod.color}18">${esc(mod.name)}</span>
      </div>`;
    }).join('');
  },

  pick(i) {
    const p = this._hits[i];
    if (!p) return;
    this.toggle();
    Nav.toPage(p.id);
  },

  onKey(e) {
    if (e.key === 'Escape') { this.toggle(); return; }
    if (e.key === 'ArrowDown') { this._idx = Math.min(this._idx + 1, this._hits.length - 1); this._render(); e.preventDefault(); }
    if (e.key === 'ArrowUp')   { this._idx = Math.max(this._idx - 1, 0); this._render(); e.preventDefault(); }
    if (e.key === 'Enter')     { this.pick(this._idx); }
  },
};
window.Search = Search;

// ── User menu ─────────────────────────────────────────────────────
function toggleUserMenu() {
  document.getElementById('user-dropdown').classList.toggle('hidden');
}
window.toggleUserMenu = toggleUserMenu;

// Fermer le menu si clic ailleurs
document.addEventListener('click', e => {
  const menu = document.getElementById('user-menu');
  if (menu && !menu.contains(e.target)) {
    document.getElementById('user-dropdown')?.classList.add('hidden');
  }
});

function fillUserMenu(user) {
  const name   = user.Full_name    || localStorage.getItem('bs_name') || '—';
  const email  = user.Email        || '—';
  const tenant = user.Nom_entreprise || user.Tenant_code || '';
  const initials = name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase() || 'BS';

  const avatar = document.getElementById('user-avatar');
  if (avatar) avatar.textContent = initials;

  const ddName   = document.getElementById('dd-name');
  const ddEmail  = document.getElementById('dd-email');
  const ddTenant = document.getElementById('dd-tenant');
  if (ddName)   ddName.textContent   = name;
  if (ddEmail)  ddEmail.textContent  = email;
  if (ddTenant) ddTenant.textContent = tenant;
}

// ── Keyboard shortcuts ────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault();
    Search.toggle();
  }
  if (e.key === 'Escape') {
    if (!document.getElementById('search-overlay').classList.contains('hidden')) return;
    Modal.close();
  }
});

// ── Hash router ───────────────────────────────────────────────────
async function handleHash() {
  const parsed = Nav.parse();

  if (!parsed) {
    const first = S.pages.find(p => p.type === 'List' || p.type === 'RoleCenter');
    if (first) Nav.toPage(first.id);
    return;
  }

  const { pageId, rest } = parsed;

  if (rest === 'new') {
    await App.loadNew(pageId);
  } else if (rest) {
    await App.load(pageId, rest);
  } else {
    await App.load(pageId);
  }
}

window.addEventListener('hashchange', handleHash);

// ── Boot ──────────────────────────────────────────────────────────
async function init() {
  try {
    // Charger le profil utilisateur + pages en parallèle
    const [meRes, pagesRes] = await Promise.all([
      apiFetch('auth/me').catch(() => null),
      Api.pages(),
    ]);

    if (meRes?.result) fillUserMenu(meRes.result);

    const res = pagesRes;
    S.pages = res.data ?? [];

    buildSidebar(S.pages);

    if (!location.hash) {
      const first = S.pages.find(p => p.type === 'List' || p.type === 'RoleCenter');
      if (first) { Nav.toPage(first.id); return; }
    }

    await handleHash();
  } catch (err) {
    document.getElementById('page-root').innerHTML = `
      <div class="err-page" style="padding-top:120px">
        <div class="err-ico">${ico('wifi-off', 28)}</div>
        <h3>Connexion impossible</h3>
        <p>${esc(err.message)}</p>
        <button class="btn btn-primary" onclick="init()">
          ${ico('refresh-cw')} Réessayer
        </button>
      </div>`;
    console.error('[init]', err);
  }
}

document.addEventListener('DOMContentLoaded', init);
