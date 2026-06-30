'use strict';

/* ═══════════════════════════════════════════════════════════════════
   BabiSoft ERP — Login Page
   ═══════════════════════════════════════════════════════════════════ */

// ── API root auto-detect ─────────────────────────────────────────
const API_ROOT = (() => {
  const p = window.location.pathname;
  const i = p.lastIndexOf('/public/');
  return (i >= 0 ? p.substring(0, i) : '') + '/';
})();

const ERP_URL  = '../erp/index.html';
const TOKEN_KEY = 'bs_token';

// ── Si déjà connecté → rediriger vers l'ERP ─────────────────────
(function checkExistingSession() {
  const t = localStorage.getItem(TOKEN_KEY);
  if (t) {
    fetch(API_ROOT + 'auth/me', {
      headers: { 'Authorization': 'Bearer ' + t, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => { if (d.status === 200) window.location.href = ERP_URL; })
    .catch(() => {});
  }
})();

// ── CSRF token (récupéré une fois, réutilisé pour login + signup) ─
let _csrf = '';
async function getCSRF() {
  if (_csrf) return _csrf;
  try {
    const r = await fetch(API_ROOT + 'X-CSRF-Token', { headers: { Accept: 'application/json' } });
    const d = await r.json();
    _csrf = d['csrf-token'] ?? '';
  } catch {}
  return _csrf;
}

// ── Utilitaires ─────────────────────────────────────────────────
function setLoading(btnId, loading) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  btn.disabled = loading;
  btn.querySelector('.btn-label').style.display  = loading ? 'none'  : '';
  btn.querySelector('.btn-spinner').classList.toggle('hidden', !loading);
}

function showError(id, msg) {
  const el = document.getElementById(id);
  el.textContent = msg;
  el.classList.remove('hidden');
}
function hideError(id) {
  document.getElementById(id)?.classList.add('hidden');
}

function showSuccess(title, msg) {
  document.querySelectorAll('.auth-form, .auth-tabs').forEach(el => el.classList.add('hidden'));
  const s = document.getElementById('auth-success');
  s.classList.remove('hidden');
  document.getElementById('success-title').textContent = title;
  document.getElementById('success-msg').textContent   = msg;
}

function onLoginSuccess(data) {
  const token   = data.result?.token ?? '';
  const user    = data.result?.utilisateur ?? {};
  const tenant  = user.Tenant_code ?? '';

  localStorage.setItem(TOKEN_KEY, token);
  if (tenant) localStorage.setItem('bs_tenant', tenant);
  if (user.Full_name)   localStorage.setItem('bs_name', user.Full_name);
  if (user.Is_admin !== undefined) localStorage.setItem('bs_admin', user.Is_admin ? '1' : '0');

  showSuccess('Connexion réussie !', 'Redirection en cours…');
  setTimeout(() => { window.location.href = ERP_URL; }, 900);
}

// ── Connexion ────────────────────────────────────────────────────
const Auth = {

  async login(e) {
    e.preventDefault();
    hideError('login-error');
    setLoading('login-btn', true);

    const email    = document.getElementById('login-email').value.trim();
    const password = document.getElementById('login-password').value;
    const csrf     = await getCSRF();

    try {
      const r = await fetch(API_ROOT + 'auth/login', {
        method:  'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'csrf-token':   csrf,
        },
        body: JSON.stringify({ Email: email, Password: password }),
      });
      const d = await r.json();

      if (d.status === 200) {
        _csrf = ''; // invalider le CSRF utilisé
        onLoginSuccess(d);
      } else {
        showError('login-error', d.message ?? 'Erreur de connexion.');
        _csrf = ''; // renouveler le CSRF pour la prochaine tentative
      }
    } catch (err) {
      showError('login-error', 'Impossible de joindre le serveur. Vérifiez votre connexion.');
    } finally {
      setLoading('login-btn', false);
    }
  },

  // ── Inscription ───────────────────────────────────────────────
  async signup(e) {
    e.preventDefault();
    hideError('signup-error');
    setLoading('signup-btn', true);

    const name    = document.getElementById('su-name').value.trim();
    const company = document.getElementById('su-company').value.trim();
    const email   = document.getElementById('su-email').value.trim();
    const pwd     = document.getElementById('su-password').value;
    const phone   = document.getElementById('su-phone').value.trim();
    const csrf    = await getCSRF();

    try {
      const r = await fetch(API_ROOT + 'auth/signup', {
        method:  'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept':        'application/json',
          'csrf-token':   csrf,
        },
        body: JSON.stringify({
          Full_name:    name,
          Company_name: company,
          Email:        email,
          Password:     pwd,
          Telephone:    phone || undefined,
        }),
      });
      const d = await r.json();

      if (d.status === 200) {
        _csrf = '';
        onLoginSuccess(d);
      } else {
        showError('signup-error', d.message ?? 'Erreur lors de la création du compte.');
        _csrf = '';
      }
    } catch (err) {
      showError('signup-error', 'Impossible de joindre le serveur. Vérifiez votre connexion.');
    } finally {
      setLoading('signup-btn', false);
    }
  },

  // ── Switcher d'onglet ─────────────────────────────────────────
  switchTab(tab) {
    document.querySelectorAll('.auth-tab').forEach(t => {
      t.classList.toggle('active', t.dataset.tab === tab);
    });
    document.getElementById('login-form').classList.toggle('hidden',  tab !== 'login');
    document.getElementById('signup-form').classList.toggle('hidden', tab !== 'signup');
    hideError('login-error');
    hideError('signup-error');
    document.getElementById('auth-success').classList.add('hidden');
    document.querySelectorAll('.auth-tabs').forEach(el => el.classList.remove('hidden'));
  },

  // ── Afficher / masquer le mot de passe ────────────────────────
  togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const isText = input.type === 'text';
    input.type = isText ? 'password' : 'text';
    btn.style.color = isText ? '' : '#0078d4';
  },

  // ── Indicateur force du mot de passe ─────────────────────────
  checkPwStrength(pw) {
    const fill  = document.getElementById('pw-fill');
    const label = document.getElementById('pw-label');
    if (!fill) return;

    let score = 0;
    if (pw.length >= 8)              score++;
    if (/[A-Z]/.test(pw))            score++;
    if (/[0-9]/.test(pw))            score++;
    if (/[^A-Za-z0-9]/.test(pw))    score++;
    if (pw.length >= 12)             score++;

    const levels = [
      { w: '0%',   c: 'transparent', t: '' },
      { w: '25%',  c: '#c50f1f',     t: 'Très faible' },
      { w: '50%',  c: '#d83b01',     t: 'Faible'      },
      { w: '75%',  c: '#d29200',     t: 'Moyen'       },
      { w: '90%',  c: '#107c10',     t: 'Fort'        },
      { w: '100%', c: '#107c10',     t: 'Très fort'   },
    ];
    const l = levels[Math.min(score, 5)];
    fill.style.width      = l.w;
    fill.style.background = l.c;
    label.textContent     = l.t;
    label.style.color     = l.c;
  },
};

window.Auth = Auth;

// Pré-charger le CSRF dès l'affichage de la page
document.addEventListener('DOMContentLoaded', () => getCSRF());
