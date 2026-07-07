/* ============================================================
   NightERP Pro — JavaScript global
   Fichier : assets/js/app.js
   ============================================================ */

// ===== TOAST =====
function toast(msg, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const icons = { success:'fa-circle-check', error:'fa-circle-xmark', info:'fa-circle-info' };
    const colors = { success:'var(--green)', error:'var(--red)', info:'var(--gold)' };
    const el = document.createElement('div');
    el.className = `toast-item ${type}`;
    el.innerHTML = `<i class="fa-solid ${icons[type]||'fa-info'}" style="color:${colors[type]}"></i>${msg}`;
    container.appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// ===== MODAL =====
function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.remove('hidden');
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.classList.add('hidden');
}
// Fermer en cliquant hors du modal
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.add('hidden');
    }
});

// ===== PREVIEW IMAGE UPLOAD =====
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview || !input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

// ===== POS PANIER (stocké côté client, envoyé par POST) =====
const POS = {
    cart: [],
    payMode: 'Espèces',

    
    addItem(id, nom, prix, image) {
        const ex = this.cart.find(c => c.id == id);
        if (ex) ex.qty++;
        else this.cart.push({ id, nom, prix, image, qty: 1 });
        this.renderCart();
    },

    removeItem(id) {
        this.cart = this.cart.filter(c => c.id != id);
        this.renderCart();
    },

    changeQty(id, d) {
        const it = this.cart.find(c => c.id == id);
        if (!it) return;
        it.qty += d;
        if (it.qty <= 0) this.removeItem(id);
        else this.renderCart();
    },

    setPayMode(m) {
        this.payMode = m;
        document.querySelectorAll('.pay-btn').forEach(b => b.classList.remove('active'));
        const btn = document.getElementById('pay-' + m.toLowerCase().replace(/ /g,'_'));
        if (btn) btn.classList.add('active');
        this.updateTotal();
    },

    updateTotal() {
        const sub  = this.cart.reduce((s, c) => s + c.prix * c.qty, 0);
        const disc = parseFloat(document.getElementById('cart-discount')?.value || 0);
        const tot  = sub * (1 - disc / 100);
        const fmt  = n => Math.round(n).toLocaleString('fr-FR') + ' F';
        if (document.getElementById('cart-subtotal')) document.getElementById('cart-subtotal').textContent = fmt(sub);
        if (document.getElementById('cart-total'))    document.getElementById('cart-total').textContent    = fmt(tot);
    },

    renderCart() {
        const el = document.getElementById('cart-items');
        if (!el) return;
        if (this.cart.length === 0) {
            el.innerHTML = `<div style="text-align:center;padding:30px;color:var(--txt2)">
                <i class="fa-solid fa-cart-plus" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>Panier vide</div>`;
            this.updateTotal();
            return;
        }
        const fmt = n => Math.round(n).toLocaleString('fr-FR') + ' F';
        el.innerHTML = this.cart.map(it => `
          <div class="cart-item">
            <div style="width:36px;height:36px;flex-shrink:0">
              ${it.image
                ? `<img src="${it.image}" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid var(--border)">`
                : `<span style="font-size:24px">📦</span>`}
            </div>
            <div class="cart-name">${it.nom}<br>
              <span style="font-size:11px;color:var(--txt3)">${fmt(it.prix)} × ${it.qty}</span>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
              <div class="cart-total">${fmt(it.prix * it.qty)}</div>
              <div class="cart-qty">
                <button class="qty-btn" onclick="POS.changeQty(${it.id},-1)">−</button>
                <span style="font-size:13px;min-width:20px;text-align:center">${it.qty}</span>
                <button class="qty-btn" onclick="POS.changeQty(${it.id},1)">+</button>
                <button class="qty-btn" onclick="POS.removeItem(${it.id})" style="color:var(--red)">
                  <i class="fa-solid fa-trash" style="font-size:10px"></i>
                </button>
              </div>
            </div>
          </div>`).join('');
        this.updateTotal();
    },

    // Soumettre la vente via un formulaire caché
    submit() {
        if (this.cart.length === 0) { toast('Le panier est vide', 'error'); return; }
        const disc   = parseFloat(document.getElementById('cart-discount')?.value || 0);
        const tableId = document.getElementById('pos-table')?.value || '';

        document.getElementById('hidden-cart').value    = JSON.stringify(this.cart);
        document.getElementById('hidden-pay').value     = this.payMode;
        document.getElementById('hidden-disc').value    = disc;
        document.getElementById('hidden-table').value   = tableId;
        document.getElementById('pos-form').submit();
    }
};

// ===== FILTRE PRODUITS POS =====
function filterPOS(val) {
    const cards = document.querySelectorAll('.prod-card');
    const q = val.toLowerCase();
    cards.forEach(c => {
        const name = c.querySelector('.prod-name')?.textContent.toLowerCase() || '';
        c.style.display = name.includes(q) ? '' : 'none';
    });
}

function filterPOSCat(cat, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');
    const cards = document.querySelectorAll('.prod-card');
    cards.forEach(c => {
        c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none';
    });
}

// ===== AUTO-FERMETURE FLASH =====
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.alert').forEach(a => {
        setTimeout(() => { a.style.transition = 'opacity .5s'; a.style.opacity = '0'; setTimeout(() => a.remove(), 500); }, 4000);
    });
});

// Délégation de clic pour les cartes produit (fallback robuste)
document.addEventListener('click', function(e) {
    const card = e.target.closest ? e.target.closest('.prod-card') : null;
    if (!card) return;
    if (typeof POS === 'undefined' || !POS.addItem) return;
    // Récupérer données depuis les attributs data-*
    const id = card.dataset.id;
    const nom = card.dataset.nom || '';
    const prix = parseFloat(card.dataset.prix || 0) || 0;
    const img = card.dataset.img || null;
    POS.addItem(id, nom, prix, img);
});

// Petit log pour vérifier le chargement du script
console.log('assets/js/app.js loaded');

// ===== THEME (sombre / clair) =====
function applyTheme(name) {
    if (name === 'light') document.body.classList.add('theme-light');
    else document.body.classList.remove('theme-light');
    const ico = document.querySelector('#theme-toggle i');
    if (ico) {
        ico.classList.remove('fa-moon','fa-sun');
        ico.classList.add(name === 'light' ? 'fa-sun' : 'fa-moon');
    }
}

function initTheme() {
    const stored = localStorage.getItem('erp_theme') || 'dark';
    applyTheme(stored);
    const btn = document.getElementById('theme-toggle');
    if (btn) {
        btn.addEventListener('click', function(){
            const cur = document.body.classList.contains('theme-light') ? 'light' : 'dark';
            const next = cur === 'light' ? 'dark' : 'light';
            applyTheme(next);
            try { localStorage.setItem('erp_theme', next); } catch(e){}
        });
    }
}

document.addEventListener('DOMContentLoaded', initTheme);
