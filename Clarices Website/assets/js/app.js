/**
 * app.js — Injili Apparel frontend application
 *
 * This file replaces the inline <script> block in index.html.
 * It preserves the entire existing UI/UX (panels, animations, cart,
 * checkout flow) but replaces all mock JS state with real API calls.
 *
 * API base — all fetch() calls go to /injili/api/...
 * The SITE_ROOT constant is set inline in index.html so it works
 * regardless of how XAMPP is configured.
 */

/* ── API helpers ─────────────────────────────────────────────────
   Thin wrappers around fetch() that handle JSON encoding/decoding
   and return the parsed response body.
─────────────────────────────────────────────────────────────────── */

/**
 * Makes a GET request to a backend API endpoint.
 * @param {string} path  Path relative to SITE_ROOT, e.g. '/api/products/list.php'
 * @returns {Promise<object>}  Parsed JSON response
 */
async function apiGet(path) {
  const res = await fetch(SITE_ROOT + path);
  return res.json();
}

/**
 * Makes a POST request with a JSON body to a backend API endpoint.
 * @param {string} path  Path relative to SITE_ROOT
 * @param {object} body  Object to JSON-encode as the request body
 * @returns {Promise<object>}  Parsed JSON response
 */
async function apiPost(path, body) {
  const res = await fetch(SITE_ROOT + path, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  return res.json();
}

/* ── Application state ───────────────────────────────────────────
   Everything that needs to survive across panel interactions.
   isLoggedIn and user are populated from the session API on load.
─────────────────────────────────────────────────────────────────── */
const state = {
  isLoggedIn:      false,
  user:            null,          // { id, name, email, phone, initials, since }
  cart:            {},            // keyed by "productId-variantId-size"
  products:        [],            // loaded from /api/products/list.php
  activeProduct:   null,
  pdSelectedSize:  null,
  pdSelectedVariant: null,        // { id, color_name, color_hex }
  coStep:          1,
  deliveryFee:     200,
  currentOrderId:  null,          // set after /api/orders/create.php
  currentOrderNum: null,
  wished:          new Set(),
};

/* ── Boot sequence ───────────────────────────────────────────────
   On page load: restore session, then load products.
─────────────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', async () => {
  await restoreSession();
  await loadProducts();
  updateCartBadge();
});

/**
 * Calls /api/auth/session.php to restore the customer's session.
 * If logged in, updates the UI to show their name.
 */
async function restoreSession() {
  try {
    const data = await apiGet('/api/auth/session.php');
    if (data.loggedIn && data.user) {
      state.isLoggedIn = true;
      state.user       = data.user;
      updateAuthUI();
    }
  } catch (e) {
    /* Non-fatal — guest state is fine */
  }
}

/**
 * Fetches all active products from the database and stores them in state.
 * Then renders the product grid and the featured drop section.
 */
async function loadProducts() {
  try {
    const data = await apiGet('/api/products/list.php');
    if (data.success && data.products) {
      state.products = data.products;
      renderGrid();
    }
  } catch (e) {
    console.error('Failed to load products:', e);
  }
}

/* ── Product Grid ────────────────────────────────────────────────
   Renders the 4-column (desktop) product grid from state.products.
   Preserves the existing CSS tee mockup, hover-add UI, and animations.
─────────────────────────────────────────────────────────────────── */

/**
 * Renders product cards into #product-grid.
 * Each card uses the existing CSS classes and HTML structure
 * so the existing styling applies without modification.
 */
function renderGrid() {
  const grid = document.getElementById('product-grid');
  if (!grid || !state.products.length) return;

  const bgMap = { 0: 'navy', 1: 'stone', 2: 'charcoal', 3: 'dusty' };

  grid.innerHTML = state.products.map((p, idx) => {
    const firstVariant = p.variants?.[0] || {};
    const sizes        = firstVariant.sizes || ['S','M','L','XL','XXL'];
    const isAlmostGone = p.tag === 'Almost Gone';

    return `
    <div class="product-card reveal" onclick="openProductDetail(${p.id})">
      <span class="product-tag${isAlmostGone ? ' sold-out-tag' : ''}">${esc(p.tag)}</span>
      <div class="product-img-wrap">
        ${p.image_path
          ? `<div class="product-img-bg" style="background:#1C2329;padding:0">
              <img src="${p.image_path}"
                    alt="${p.name}"
                    style="width:100%;height:100%;object-fit:cover;display:block">
            </div>`
          : `<div class="product-img-bg bg-${p.id===1?'navy':p.id===2?'stone':p.id===3?'charcoal':p.id===4?'dusty':p.id===5?'navy':p.id===6?'stone':'charcoal'}">
              <div class="mini-tee ${p.tee_class}">
                <div class="mini-collar"></div>
                <div class="mini-tee-body"></div>
                <div class="product-graphic-abs" style="top:52%">${p.graphic_html}</div>
              </div>
            </div>`
        }
        <div class="product-hover-add" onclick="event.stopPropagation()">
          <div class="hover-size-row">
            ${sizes.map(s => `
              <button class="hover-size-btn" onclick="hoverAddSize(this,${p.id},'${esc(s)}')">${esc(s)}</button>
            `).join('')}
          </div>
          <button class="hover-add-btn" onclick="quickAddToCart(${p.id},this)">
            <i class="fa-solid fa-bag-shopping"></i> Add to Bag
          </button>
        </div>
      </div>
      <div class="product-info">
        <div class="product-name">${esc(p.name)}</div>
        <div class="product-verse-tag">${esc(p.verse)}</div>
        <div class="product-bottom">
          <span class="product-price">KSh ${p.price_ksh.toLocaleString()}</span>
          <div class="color-dots">${(p.colorVals || []).map(c =>
            `<span class="cdot" style="background:${esc(c)}"></span>`
          ).join('')}</div>
        </div>
      </div>
    </div>`;
  }).join('');

  observeReveal();
}

/** Escape HTML to prevent XSS when inserting database content into innerHTML */
function esc(str) {
  const d = document.createElement('div');
  d.textContent = String(str ?? '');
  return d.innerHTML;
}

/* ── Panel system ────────────────────────────────────────────────
   Unchanged from original — opens/closes slide-in drawers.
─────────────────────────────────────────────────────────────────── */
let activePanel = null;

function openPanel(name, sub) {
  if (activePanel) closePanel(activePanel + '-panel');
  closeNavDrawer();
  if (name === 'auth') {
    if (!state.isLoggedIn) { switchAuthTab(sub || 'signin'); showPanelEl('auth-panel'); }
    else { renderAccountPanel(); showPanelEl('account-panel'); return; }
  } else if (name === 'account') {
    renderAccountPanel(); showPanelEl('account-panel');
  } else if (name === 'cart') {
    renderCart(); showPanelEl('cart-panel');
  } else if (name === 'checkout') {
    closePanel('cart-panel');
    renderCheckout(); showPanelEl('checkout-panel');
  }
  activePanel = name;
}

function showPanelEl(id) {
  document.getElementById(id).classList.add('open');
  document.getElementById('backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closePanel(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
  document.getElementById('backdrop').classList.remove('open');
  document.body.style.overflow = '';
  activePanel = null;
}

function closeAllPanels() {
  ['auth-panel','product-panel','cart-panel','checkout-panel','account-panel'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
  });
  closeNavDrawer();
  document.getElementById('backdrop').classList.remove('open');
  document.body.style.overflow = '';
  activePanel = null;
}

function openNavDrawer() {
  document.getElementById('nav-drawer').classList.add('open');
  document.getElementById('backdrop').classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeNavDrawer() {
  document.getElementById('nav-drawer').classList.remove('open');
  if (!activePanel) {
    document.getElementById('backdrop').classList.remove('open');
    document.body.style.overflow = '';
  }
}
function scrollToSection(id) {
  closeNavDrawer();
  setTimeout(() => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' }), 200);
}

/* ── Auth ────────────────────────────────────────────────────────
   All auth actions now hit real API endpoints.
─────────────────────────────────────────────────────────────────── */
function switchAuthTab(tab) {
  document.getElementById('form-signin').style.display    = tab === 'signin'   ? '' : 'none';
  document.getElementById('form-register').style.display  = tab === 'register' ? '' : 'none';
  document.getElementById('tab-signin').classList.toggle('active',   tab === 'signin');
  document.getElementById('tab-register').classList.toggle('active', tab === 'register');
  document.getElementById('auth-panel-title').textContent = tab === 'signin' ? 'Welcome Back' : 'Create Account';
}

/**
 * Handles the sign-in form submission.
 * POSTs to /api/auth/login.php and updates state on success.
 */
async function handleSignIn() {
  const email = document.getElementById('si-email').value;
  const pass  = document.getElementById('si-pass').value;
  if (!email || !pass) { showToast('Please fill in all fields'); return; }

  const data = await apiPost('/api/auth/login.php', { email, password: pass });

  if (data.success) {
    state.isLoggedIn = true;
    state.user       = data.user;
    updateAuthUI();
    closePanel('auth-panel');
    showToast('Welcome back, ' + data.user.name.split(' ')[0] + '! ✦', true);
  } else {
    showToast(data.message || 'Sign-in failed');
  }
}

/**
 * Handles the registration form submission.
 * POSTs to /api/auth/register.php and logs the user in on success.
 */
async function handleRegister() {
  const first = document.getElementById('reg-first').value;
  const last  = document.getElementById('reg-last').value;
  const email = document.getElementById('reg-email').value;
  const phone = document.getElementById('reg-phone').value;
  const pass  = document.getElementById('reg-pass').value;

  if (!first || !last || !email || !phone || !pass) { showToast('Please fill in all fields'); return; }

  const data = await apiPost('/api/auth/register.php', {
    first_name: first, last_name: last, email, phone, password: pass,
  });

  if (data.success) {
    state.isLoggedIn = true;
    state.user       = data.user;
    updateAuthUI();
    closePanel('auth-panel');
    showToast(`Welcome, ${first}! ✦`, true);
  } else {
    showToast(data.message || 'Registration failed');
  }
}

/**
 * Signs the customer out — clears session via API and resets state.
 */
async function handleSignOut() {
  await apiPost('/api/auth/logout.php', {});
  state.isLoggedIn = false;
  state.user       = null;
  updateAuthUI();
  closeAllPanels();
  showToast('Signed out. See you next drop.');
}

/**
 * Updates all parts of the UI that depend on auth state.
 * Called after sign-in, registration, sign-out, and session restore.
 */
function updateAuthUI() {
  const loggedIn = state.isLoggedIn;
  const guestSect = document.getElementById('nd-guest-section');
  const userSect  = document.getElementById('nd-user-section');
  const signoutSect = document.getElementById('nd-signout-section');

  if (guestSect)   guestSect.style.display   = loggedIn ? 'none' : '';
  if (userSect)    userSect.style.display     = loggedIn ? '' : 'none';
  if (signoutSect) signoutSect.style.display  = loggedIn ? '' : 'none';

  if (loggedIn && state.user) {
    const avatar   = document.getElementById('nd-avatar');
    const userName = document.getElementById('nd-user-name');
    const userEmail= document.getElementById('nd-user-email');
    if (avatar)    avatar.textContent    = state.user.initials;
    if (userName)  userName.textContent  = state.user.name;
    if (userEmail) userEmail.textContent = state.user.email;
  }
}

/* ── Product Detail Panel ────────────────────────────────────────
   Reads from state.products (already loaded) — no extra API call needed.
─────────────────────────────────────────────────────────────────── */

/**
 * Opens the product detail side panel for the given product ID.
 * @param {number} id  Product ID matching state.products[].id
 */
function openProductDetail(id) {
  const p = state.products.find(x => x.id === id);
  if (!p) return;
  state.activeProduct   = p;
  state.pdSelectedSize  = null;
  state.pdSelectedVariant = p.variants?.[0] || null;

  document.getElementById('pd-panel-title').textContent = p.name;
  document.getElementById('pd-tag').textContent         = p.tag;
  document.getElementById('pd-name').textContent        = p.name;
  document.getElementById('pd-verse').textContent       = p.verse;
  document.getElementById('pd-price').textContent       = `KSh ${p.price_ksh.toLocaleString()}`;
  document.getElementById('acc-material').textContent   = p.material;
  document.getElementById('acc-fit').textContent        = p.fit;

  const firstVariant = p.variants?.[0] || {};
  document.getElementById('pd-selected-color').textContent = firstVariant.color_name || '';
  document.getElementById('pd-selected-size').textContent  = 'Select';
  document.getElementById('pd-stock-msg').textContent      = '';

  /* Wishlist button state */
  const wb = document.getElementById('pd-wish-btn');
  wb.className = 'pd-wish-btn';
  wb.innerHTML = '<i class="fa-regular fa-heart"></i> Add to Wishlist';
  if (state.wished.has(id)) {
    wb.classList.add('wished');
    wb.innerHTML = '<i class="fa-solid fa-heart"></i> In Wishlist';
  }

  /* Image area — CSS tee mockup */
  document.getElementById('pd-image-area').innerHTML = `
    <div style="width:200px;height:250px;position:relative">
      <div class="mini-tee ${esc(p.tee_class)}" style="width:100%;height:100%">
        <div class="mini-collar" style="width:60px;height:30px;top:-15px"></div>
        <div class="mini-tee-body" style="width:155px;height:200px;border-radius:0 0 10px 10px"></div>
        <div style="position:absolute;top:52%;left:50%;transform:translate(-50%,-50%);text-align:center;width:140px;z-index:3">${p.graphic_html}</div>
      </div>
    </div>`;

  /* Colour thumbnails */
  document.getElementById('pd-thumbs').innerHTML = (p.variants || []).map((v, i) => `
    <div class="pd-thumb ${i === 0 ? 'active' : ''}" onclick="selectPdVariant(${id},${v.id},this)">
      <div class="pd-thumb-inner" style="background:${esc(v.color_hex)};width:100%;height:100%"></div>
    </div>`).join('');

  /* Colour dot selectors */
  document.getElementById('pd-colors').innerHTML = (p.variants || []).map((v, i) => `
    <div class="pd-color ${i === 0 ? 'active' : ''}"
         style="background:${esc(v.color_hex)};border-color:rgba(0,0,0,.15)"
         title="${esc(v.color_name)}"
         onclick="selectPdVariant(${id},${v.id},null,this)"></div>
  `).join('');

  /* Size buttons — all sizes available (made-to-order, no inventory) */
  const sizes = firstVariant.sizes || ['S','M','L','XL','XXL'];
  document.getElementById('pd-sizes').innerHTML = sizes.map(s => `
    <button class="pd-size-btn" onclick="selectSize('${s}',this)">${s}</button>
  `).join('');

  showPanelEl('product-panel');
  activePanel = 'product';
}

/**
 * Handles colour/variant selection in the product detail panel.
 * Updates the selected colour label and highlights the active swatch.
 * @param {number} pid       Product ID
 * @param {number} variantId Variant ID (from product_variants table)
 * @param {Element|null} thumbEl   The thumbnail element clicked (or null)
 * @param {Element|null} colorEl   The colour dot element clicked (or null)
 */
function selectPdVariant(pid, variantId, thumbEl, colorEl) {
  const p       = state.products.find(x => x.id === pid);
  const variant = p?.variants?.find(v => v.id === variantId);
  if (!variant) return;

  state.pdSelectedVariant = variant;
  document.getElementById('pd-selected-color').textContent = variant.color_name;

  if (thumbEl) {
    document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
    thumbEl.classList.add('active');
  }
  if (colorEl) {
    document.querySelectorAll('.pd-color').forEach(c => c.classList.remove('active'));
    colorEl.classList.add('active');
  }

  /* Update size buttons for the new variant's available sizes */
  document.getElementById('pd-sizes').innerHTML = (variant.sizes || []).map(s => `
    <button class="pd-size-btn" onclick="selectSize('${s}',this)">${s}</button>
  `).join('');
  state.pdSelectedSize = null;
  document.getElementById('pd-selected-size').textContent = 'Select';
  document.getElementById('pd-stock-msg').textContent     = '';
}

/** Handles size button click in the product detail panel */
function selectSize(size, btn) {
  document.querySelectorAll('.pd-size-btn').forEach(b => b.classList.remove('active-size'));
  btn.classList.add('active-size');
  state.pdSelectedSize = size;
  document.getElementById('pd-selected-size').textContent = size;
  document.getElementById('pd-stock-msg').textContent     = 'Made to order in your size.';
  document.getElementById('pd-stock-msg').className       = 'pd-stock-msg pd-stock-ok';
}

/** Adds the currently configured item from the detail panel to the cart */
function pdAddToCart() {
  if (!state.pdSelectedSize) { showToast('Please select a size'); return; }
  const p = state.activeProduct;
  const v = state.pdSelectedVariant;
  addToCart(p.id, v?.id, p.name, v?.color_name || '', p.price_ksh, state.pdSelectedSize);
  closePanel('product-panel');
}

/** Toggles wishlist membership for the active product */
function toggleWishlist() {
  if (!state.activeProduct) return;
  const id  = state.activeProduct.id;
  const btn = document.getElementById('pd-wish-btn');
  if (state.wished.has(id)) {
    state.wished.delete(id);
    btn.className = 'pd-wish-btn';
    btn.innerHTML = '<i class="fa-regular fa-heart"></i> Add to Wishlist';
    showToast('Removed from wishlist');
  } else {
    state.wished.add(id);
    btn.classList.add('wished');
    btn.innerHTML = '<i class="fa-solid fa-heart"></i> In Wishlist';
    showToast('Added to wishlist ✦', true);
  }
}

/* ── Accordion (product detail) ─────────────────────────────────── */
function toggleAcc(trigger) {
  const body   = trigger.nextElementSibling;
  const isOpen = trigger.classList.contains('open');
  document.querySelectorAll('.pd-acc-trigger.open').forEach(t => {
    t.classList.remove('open');
    t.nextElementSibling.classList.remove('open');
  });
  if (!isOpen) { trigger.classList.add('open'); body.classList.add('open'); }
}

/* ── Cart ────────────────────────────────────────────────────────
   Cart is stored client-side in state.cart (no persistence between
   page loads for guests). For logged-in users a future phase will
   persist the cart server-side.
─────────────────────────────────────────────────────────────────── */

/**
 * Adds a product to the cart. Key format: `productId-variantId-size`
 * so the same design in different colours or sizes is tracked separately.
 */
function addToCart(productId, variantId, name, colorName, price, size) {
  const key = `${productId}-${variantId}-${size}`;
  if (state.cart[key]) {
    state.cart[key].qty++;
  } else {
    state.cart[key] = { productId, variantId, name, colorName, price, size, qty: 1 };
  }
  updateCartBadge();
  showToast(`"${name}" (${size}) added to bag ✦`, true);
}

/** Quick-add from the hover overlay on the product grid card */
function quickAddToCart(id, btn) {
  const card   = btn.closest('.product-hover-add');
  const active = card.querySelector('.hover-size-btn.act');
  if (!active) { showToast('Please select a size first'); return; }

  const p = state.products.find(x => x.id === id);
  const v = p?.variants?.[0] || {};
  addToCart(id, v.id, p.name, v.color_name || '', p.price_ksh, active.textContent.trim());
}

function hoverAddSize(btn, id, size) {
  btn.closest('.hover-size-row').querySelectorAll('.hover-size-btn').forEach(b => b.classList.remove('act'));
  btn.classList.add('act');
}

/** Direct add from the featured drop section (pre-selects size M) */
function pdirectAddToCart(id) {
  const p = state.products.find(x => x.id === id);
  if (!p) return;
  const v = p.variants?.[0] || {};
  addToCart(id, v.id, p.name, v.color_name || '', p.price_ksh, 'M');
}

function updateCartBadge() {
  const total = Object.values(state.cart).reduce((s, i) => s + i.qty, 0);
  ['cart-count', 'nd-cart-count'].forEach(elId => {
    const el = document.getElementById(elId);
    if (el) {
      el.textContent = total;
      if (elId === 'cart-count') {
        el.classList.add('bump');
        setTimeout(() => el.classList.remove('bump'), 300);
      }
    }
  });
}

/** Renders the cart panel contents from state.cart */
function renderCart() {
  const items     = Object.values(state.cart);
  const cartFoot  = document.getElementById('cart-foot');
  const cartEmpty = document.getElementById('cart-empty');
  const cartList  = document.getElementById('cart-items-list');

  if (items.length === 0) {
    cartEmpty.style.display = '';
    cartFoot.style.display  = 'none';
    cartList.innerHTML      = '';
    return;
  }

  cartEmpty.style.display = 'none';
  cartFoot.style.display  = '';

  const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);
  const key0     = Object.keys(state.cart)[0];

  cartList.innerHTML = items.map(item => {
    const key     = `${item.productId}-${item.variantId}-${item.size}`;
    const product = state.products.find(p => p.id === item.productId);
    const bg      = product?.bg_color || '#eee';
    return `
    <div class="cart-item">
      <div class="cart-item-img" style="background:${esc(bg)}">
        <span style="font-size:.8rem;opacity:.5">✦</span>
      </div>
      <div class="cart-item-info">
        <div class="cart-item-name">${esc(item.name)}</div>
        <div class="cart-item-meta">Size: ${esc(item.size)} · ${esc(item.colorName)}</div>
        <div class="cart-qty-row">
          <button class="qty-btn" onclick="changeQty('${key}',-1)">−</button>
          <span class="qty-num">${item.qty}</span>
          <button class="qty-btn" onclick="changeQty('${key}',1)">+</button>
          <button class="cart-remove" onclick="removeFromCart('${key}')"><i class="fa-regular fa-trash-can"></i></button>
        </div>
      </div>
      <div class="cart-item-price">KSh ${(item.price * item.qty).toLocaleString()}</div>
    </div>`;
  }).join('');

  const deliveryFee = subtotal >= 5000 ? 0 : 200;
  const free        = deliveryFee === 0;
  document.getElementById('cart-summary').innerHTML = `
    <div class="cart-summary-row"><span>Subtotal</span><span>KSh ${subtotal.toLocaleString()}</span></div>
    <div class="cart-summary-row"><span>Delivery</span><span style="color:${free ? '#27AE60' : ''}">${free ? 'Free' : 'KSh 200'}</span></div>
    ${free ? '<div style="font-size:.7rem;color:#27AE60;padding:.25rem 1.6rem 0">🎉 Free delivery on orders over KSh 5,000!</div>' : ''}
    <div class="cart-summary-row total"><span>Total</span><strong>KSh ${(subtotal + deliveryFee).toLocaleString()}</strong></div>`;
}

function changeQty(key, delta) {
  if (!state.cart[key]) return;
  state.cart[key].qty = Math.max(0, state.cart[key].qty + delta);
  if (state.cart[key].qty === 0) delete state.cart[key];
  updateCartBadge();
  renderCart();
}

function removeFromCart(key) {
  delete state.cart[key];
  updateCartBadge();
  renderCart();
}

/* ── Checkout ────────────────────────────────────────────────────
   3-step flow: Details → Delivery → Payment
   On payment, creates order via API then initiates STK push.
─────────────────────────────────────────────────────────────────── */

function renderCheckout() {
  state.coStep     = 1;
  state.deliveryFee = 200;
  updateCoProgress();

  const items    = Object.values(state.cart);
  const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);

  const miniHTML = `
    ${items.map(i => `
      <div class="co-order-item">
        <span>${esc(i.name)} ×${i.qty} (${esc(i.size)}, ${esc(i.colorName)})</span>
        <span>KSh ${(i.price * i.qty).toLocaleString()}</span>
      </div>`).join('')}
    <div class="co-order-total-row">
      <span>Order Total</span>
      <strong>KSh ${(subtotal + state.deliveryFee).toLocaleString()}</strong>
    </div>`;

  const miniEl  = document.getElementById('co-mini-summary');
  const finalEl = document.getElementById('co-final-summary');
  if (miniEl)  miniEl.innerHTML  = miniHTML;
  if (finalEl) finalEl.innerHTML = miniHTML;

  document.getElementById('pay-btn-label').textContent =
    `Pay KSh ${(subtotal + state.deliveryFee).toLocaleString()}`;

  /* Pre-fill from session if logged in */
  if (state.isLoggedIn && state.user) {
    document.getElementById('co-name').value  = state.user.name;
    document.getElementById('co-email').value = state.user.email;
    document.getElementById('co-phone').value = state.user.phone || '';
    document.getElementById('co-mpesa-phone').value = state.user.phone || '';
  }

  ['co-step-1','co-step-2','co-step-3','co-step-4'].forEach((id, i) => {
    document.getElementById(id).className = `co-step ${i === 0 ? 'active' : ''}`;
  });

  document.getElementById('co-progress').style.display = '';
  document.getElementById('co-back-btn').style.display = '';
}

function updateCoProgress() {
  for (let i = 1; i <= 3; i++) {
    const dot = document.getElementById(`pdot-${i}`);
    const lbl = document.getElementById(`plbl-${i}`);
    dot.className = `progress-dot${i < state.coStep ? ' done' : i === state.coStep ? ' active' : ''}`;
    dot.textContent = i < state.coStep ? '✓' : i;
    lbl.className = `progress-label${i === state.coStep ? ' active' : ''}`;
  }
  const backBtn = document.getElementById('co-back-btn');
  backBtn.style.opacity = state.coStep === 1 ? '0.3' : '1';
  backBtn.disabled      = state.coStep === 1;
}

function coNext(from) {
  if (from === 1) {
    if (!document.getElementById('co-name').value || !document.getElementById('co-email').value) {
      showToast('Please fill in your details');
      return;
    }
  }
  if (from === 2) {
    const mode = document.querySelector('input[name=delivery]:checked')?.value;
    if (mode !== 'pickup' && !document.getElementById('co-addr').value) {
      showToast('Please enter your delivery address');
      return;
    }
  }
  state.coStep = from + 1;
  showStep(state.coStep);
  updateCoProgress();
}

function coBack() {
  if (state.coStep <= 1) { closePanel('checkout-panel'); openPanel('cart'); return; }
  state.coStep--;
  showStep(state.coStep);
  updateCoProgress();
}

function showStep(n) {
  for (let i = 1; i <= 4; i++) {
    document.getElementById(`co-step-${i}`).className = `co-step${i === n ? ' active' : ''}`;
  }
  document.getElementById('co-progress').style.display = n === 4 ? 'none' : '';
}

function selectDelivery(radio, elId, fee) {
  document.querySelectorAll('.delivery-opt').forEach(e => e.classList.remove('selected'));
  document.getElementById(elId).classList.add('selected');
  state.deliveryFee = fee;

  document.getElementById('address-section').style.display =
    radio.value === 'pickup' ? 'none' : '';

  const items    = Object.values(state.cart);
  const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);
  document.getElementById('pay-btn-label').textContent =
    `Pay KSh ${(subtotal + fee).toLocaleString()}`;

  document.getElementById('co-final-summary').innerHTML = `
    ${items.map(i => `
      <div class="co-order-item">
        <span>${esc(i.name)} ×${i.qty}</span>
        <span>KSh ${(i.price * i.qty).toLocaleString()}</span>
      </div>`).join('')}
    <div class="co-order-item">
      <span class="co-order-label">Delivery</span>
      <span>${fee === 0 ? '<span style="color:#27AE60">Free</span>' : `KSh ${fee}`}</span>
    </div>
    <div class="co-order-total-row">
      <span>Total</span>
      <strong>KSh ${(subtotal + fee).toLocaleString()}</strong>
    </div>`;
}

function selectPayment(method) {
  document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
  document.getElementById(`pm-${method}`).classList.add('selected');
  document.querySelectorAll('.payment-fields').forEach(f => f.classList.remove('show'));
  document.getElementById(`pf-${method}`).classList.add('show');
  document.querySelectorAll('input[name=payment]').forEach(r => { r.checked = r.value === method; });
}

/**
 * Main payment handler — called when the "Pay" button is clicked.
 *
 * Flow:
 *   1. Create the order via /api/orders/create.php
 *   2. Initiate STK push via /api/payment/mpesa_stk.php
 *   3. Show spinner and poll /api/orders/get.php every 3 seconds
 *   4. When payment_status = 'paid', show confirmation screen
 *   5. If polling times out (60s), show manual fallback message
 */
async function handlePayment() {
  const method = document.querySelector('input[name=payment]:checked')?.value;
  const phone  = document.getElementById('co-mpesa-phone').value ||
                 document.getElementById('co-phone').value;

  if (method === 'mpesa' && !phone) {
    showToast('Please enter your M-Pesa number');
    return;
  }

  /* Gather order data from the checkout form */
  const deliveryMode = document.querySelector('input[name=delivery]:checked')?.value || 'doorstep';
  const deliveryModeMap = { standard: 'doorstep', express: 'doorstep', pickup: 'agent_pickup' };

  const orderPayload = {
    customer_name:     document.getElementById('co-name').value,
    customer_email:    document.getElementById('co-email').value,
    customer_phone:    document.getElementById('co-phone').value || phone,
    delivery_mode:     deliveryModeMap[deliveryMode] || 'doorstep',
    delivery_address:  document.getElementById('co-addr')?.value || '',
    delivery_city:     document.getElementById('co-city')?.value || '',
    delivery_area:     document.getElementById('co-area')?.value || '',
    delivery_notes:    document.getElementById('co-notes')?.value || '',
    items: Object.values(state.cart).map(item => ({
      product_id: item.productId,
      variant_id: item.variantId,
      size:        item.size,
      quantity:    item.qty,
    })),
  };

  /* Show processing overlay */
  const proc    = document.getElementById('processing');
  const navRow  = document.querySelector('#co-step-3 .co-nav-row');
  proc.classList.add('show');
  if (navRow) navRow.style.display = 'none';
  document.getElementById('processing-msg').textContent = 'Creating your order…';

  try {
    /* Step 1: Create the order */
    const orderData = await apiPost('/api/orders/create.php', orderPayload);

    if (!orderData.success) {
      proc.classList.remove('show');
      if (navRow) navRow.style.display = '';
      showToast(orderData.message || 'Order creation failed');
      return;
    }

    state.currentOrderId  = orderData.order_id;
    state.currentOrderNum = orderData.order_number;

    if (method === 'mpesa') {
      /* Step 2: Initiate STK push */
      document.getElementById('processing-msg').textContent =
        `Sending M-Pesa prompt to ${phone.slice(0, 4)}*** ***…`;

      const stkData = await apiPost('/api/payment/mpesa_stk.php', {
        order_id: orderData.order_id,
        phone,
      });

      if (!stkData.success) {
        proc.classList.remove('show');
        if (navRow) navRow.style.display = '';
        showToast(stkData.message || 'M-Pesa failed. Try again or pay via WhatsApp.');
        return;
      }

      document.getElementById('processing-msg').textContent =
        'Check your phone and enter your M-Pesa PIN…';

      /* Step 3: Poll for payment confirmation */
      await pollForPayment(orderData.order_id);

    } else {
      /* Manual payment path */
      completePurchase();
    }

  } catch (err) {
    proc.classList.remove('show');
    if (navRow) navRow.style.display = '';
    showToast('Connection error. Please try again.');
    console.error(err);
  }
}

/**
 * Polls /api/orders/get.php every 3 seconds until payment_status = 'paid'
 * or until 20 attempts (60 seconds) have elapsed.
 *
 * @param {number} orderId  Internal order ID to poll
 */
async function pollForPayment(orderId) {
  const maxAttempts = 20;
  let   attempts    = 0;

  const poll = setInterval(async () => {
    attempts++;
    try {
      const data = await apiGet('/api/orders/get.php?id=' + orderId);
      if (data.success && data.order.payment_status === 'paid') {
        clearInterval(poll);
        completePurchase();
      } else if (attempts >= maxAttempts) {
        clearInterval(poll);
        /* Timeout — show confirmation anyway but note pending payment */
        document.getElementById('processing-msg').textContent =
          'Payment pending. We will notify you once confirmed.';
        setTimeout(completePurchase, 1500);
      }
    } catch (e) {
      /* Network hiccup — keep polling */
    }
  }, 3000);
}

/**
 * Shows the order confirmation screen (step 4).
 * Clears the cart and updates the order number display.
 */
function completePurchase() {
  const proc   = document.getElementById('processing');
  const navRow = document.querySelector('#co-step-3 .co-nav-row');

  proc.classList.remove('show');
  if (navRow) navRow.style.display = '';

  document.getElementById('confirm-order-num').textContent = state.currentOrderNum || '#INJ-2025-????';
  const name  = document.getElementById('co-name').value || 'there';
  const email = document.getElementById('co-email').value || 'your email';
  document.getElementById('confirm-name').textContent  = name.split(' ')[0];
  document.getElementById('confirm-email').textContent = email;

  /* Update the Track My Orders button to link to the tracking page */
  const trackBtn = document.querySelector('#co-step-4 .btn-track');
  if (trackBtn && state.currentOrderNum) {
    trackBtn.onclick = () => {
      window.open(SITE_ROOT + '/track.php?order=' + encodeURIComponent(state.currentOrderNum), '_blank');
    };
  }

  /* Clear the cart */
  state.cart = {};
  updateCartBadge();

  showStep(4);
  document.getElementById('co-progress').style.display = 'none';
  document.getElementById('co-back-btn').style.display = 'none';
}

/* ── Account Panel ───────────────────────────────────────────────
   Loads real order history from /api/orders/list.php on open.
─────────────────────────────────────────────────────────────────── */

async function renderAccountPanel() {
  const loggedIn = state.isLoggedIn;
  document.getElementById('acct-guest-view').style.display = loggedIn ? 'none' : '';
  document.getElementById('acct-user-view').style.display  = loggedIn ? '' : 'none';

  if (!loggedIn || !state.user) return;

  document.getElementById('acct-avatar-large').textContent  = state.user.initials;
  document.getElementById('acct-display-name').textContent  = state.user.name;
  document.getElementById('acct-display-email').textContent = state.user.email;

  /* Load real orders */
  try {
    const data = await apiGet('/api/orders/list.php');
    if (!data.success) return;

    /* Update stats */
    const orderCountEl = document.querySelector('.acct-quick-stats .acct-stat:nth-child(1) .acct-stat-num');
    const spentEl      = document.querySelector('.acct-quick-stats .acct-stat:nth-child(3) .acct-stat-num');
    if (orderCountEl) orderCountEl.textContent = data.order_count;
    if (spentEl)      spentEl.innerHTML        = `KSh<br><span style="font-size:.9rem">${(data.total_spent).toLocaleString()}</span>`;

    /* Render orders list */
    const ordersList = document.getElementById('orders-section');
    if (!ordersList) return;

    const statusCls = {
      received:          'status-confirmed',
      in_production:     'status-processing',
      ready_for_dispatch:'status-processing',
      handed_to_pm:      'status-processing',
      in_transit:        'status-processing',
      out_for_delivery:  'status-processing',
      delivered:         'status-delivered',
    };
    const statusLabels = {
      received:          'Received',
      in_production:     'In Production',
      ready_for_dispatch:'Ready',
      handed_to_pm:      'With PM',
      in_transit:        'In Transit',
      out_for_delivery:  'Out for Delivery',
      delivered:         'Delivered',
    };

    if (!data.orders.length) {
      ordersList.innerHTML = '<span class="acct-section-title">My Orders</span><div style="font-size:.82rem;color:var(--muted);padding:.5rem 0">No orders yet. Start shopping!</div>';
      return;
    }

    ordersList.innerHTML = `<span class="acct-section-title">My Orders</span>` +
      data.orders.map(o => `
        <div class="order-card">
          <div class="order-card-head">
            <span class="order-num">${esc(o.order_number)}</span>
            <span class="order-status ${statusCls[o.status] || 'status-confirmed'}">
              ${statusLabels[o.status] || o.status}
            </span>
          </div>
          <div class="order-card-items">${esc(o.items_summary || '')}</div>
          <div class="order-card-meta">
            <span>${new Date(o.created_at).toLocaleDateString('en-KE',{day:'numeric',month:'short',year:'numeric'})}</span>
            <span>KSh ${Number(o.total_ksh).toLocaleString()}</span>
          </div>
          <div class="order-card-foot">
            <button class="order-reorder-btn" onclick="showToast('Feature coming soon!',true)">Reorder</button>
            <button class="order-track-btn" onclick="window.open(SITE_ROOT+'/track.php?order=${esc(o.order_number)}','_blank')">
              <i class="fa-solid fa-location-dot"></i> Track Order
            </button>
          </div>
        </div>`).join('');
  } catch (e) {
    console.error('Failed to load orders:', e);
  }
}

function switchAcctTab(tab) {
  if (tab === 'orders')   document.getElementById('orders-section')?.scrollIntoView({ behavior:'smooth', block:'start' });
  if (tab === 'wishlist') document.getElementById('wishlist-section')?.scrollIntoView({ behavior:'smooth', block:'start' });
}

/* ── Newsletter subscription ─────────────────────────────────── */
async function subscribeNewsletter(btn) {
  const input = btn.closest('.nl-form')?.querySelector('input[type=email]');
  const email = input?.value?.trim();
  if (!email) { showToast('Please enter your email'); return; }

  const data = await apiPost('/api/newsletter/subscribe.php', { email });
  showToast(data.message || 'Done!', data.success);
  if (data.success && input) input.value = '';
}

/* ── Scroll / Reveal ─────────────────────────────────────────── */
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', scrollY > 60);
});

function observeReveal() {
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
  }, { threshold: 0.1 });
  document.querySelectorAll('.reveal').forEach(el => obs.observe(el));
}

/* ── Toast ───────────────────────────────────────────────────── */
let toastTimer;
function showToast(msg, good) {
  const t    = document.getElementById('toast');
  const icon = t.querySelector('i');
  document.getElementById('toast-msg').textContent = msg;
  icon.className = good ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}

/* ── Card number formatting ──────────────────────────────────── */
document.addEventListener('input', e => {
  if (e.target.id === 'co-card-num') {
    e.target.value = e.target.value.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim().slice(0, 19);
  }
});
