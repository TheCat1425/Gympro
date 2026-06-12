// =============================================
//  MARKETPLACE — Products & Cart
// =============================================

let allProducts = [];
let activeCategory = '';
let cartItems = [];
let cartTotal = 0;
let cartCount = 0;

document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  updateNavbarUser();
  initCategoryPills();
  initCartDrawer();
  await fetchProducts();
  await refreshCart();
});

async function fetchProducts() {
  const grid = document.getElementById('productsGrid');
  if (grid) grid.innerHTML = '<div class="page-loading" style="grid-column:1/-1"><div class="spinner spinner--lg"></div></div>';

  try {
    let url = API_BASE + 'products/get_products.php';
    if (activeCategory) url += '?category=' + encodeURIComponent(activeCategory);

    const res = await fetch(url);
    const data = await res.json();

    if (data.success) {
      allProducts = data.products;
      renderProducts(allProducts);
    }
  } catch (err) {
    showToast('error', 'Failed to load products.');
  }
}

function initCategoryPills() {
  document.querySelectorAll('.category-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      document.querySelectorAll('.category-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeCategory = pill.dataset.category || '';
      fetchProducts();
    });
  });
}

function renderProducts(products) {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;

  if (products.length === 0) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-state__icon">📦</div>
        <h3 class="empty-state__title">No products found</h3>
        <p class="empty-state__text">Check back soon for new arrivals!</p>
      </div>`;
    return;
  }

  const categoryIcons = { supplements: '💊', food: '🍎', gear: '🏋️', apparel: '👕', accessories: '🎒' };

  grid.innerHTML = products.map(p => {
    const isAvailable = p.status === 'available' && p.stock > 0;
    let stockClass = 'available', stockText = `${p.stock} in stock`;
    if (p.stock === 0 || p.status === 'sold_out') { stockClass = 'out'; stockText = 'Sold Out'; }
    else if (p.stock <= 5) { stockClass = 'low'; stockText = `Only ${p.stock} left!`; }

    return `
      <div class="product-card">
        <div class="product-card__image">${p.image || categoryIcons[p.category] || '📦'}</div>
        <div class="product-card__body">
          <span class="product-card__category">${categoryIcons[p.category] || '📦'} ${p.category}</span>
          <h3 class="product-card__name">${p.name}</h3>
          <p class="product-card__desc">${p.description || ''}</p>
          <div class="product-card__footer">
            <span class="product-card__price">${formatCurrency(p.price)}</span>
            <span class="product-card__stock product-card__stock--${stockClass}">${stockText}</span>
          </div>
          <div style="margin-top:16px">
            ${isAvailable
              ? `<button class="btn btn--primary btn--sm btn--full" onclick="addToCart(${p.id})">🛒 Add to Cart</button>`
              : `<button class="btn btn--secondary btn--sm btn--full btn--disabled" disabled>Unavailable</button>`
            }
          </div>
        </div>
      </div>`;
  }).join('');
}

// ---------- Cart ----------
function initCartDrawer() {
  const cartBtn = document.getElementById('cartToggle');
  const cartOverlay = document.getElementById('cartOverlay');
  const cartClose = document.getElementById('cartClose');

  if (cartBtn) cartBtn.addEventListener('click', () => toggleCart(true));
  if (cartOverlay) cartOverlay.addEventListener('click', () => toggleCart(false));
  if (cartClose) cartClose.addEventListener('click', () => toggleCart(false));
}

function toggleCart(open) {
  const overlay = document.getElementById('cartOverlay');
  const drawer = document.getElementById('cartDrawer');
  if (overlay) overlay.classList.toggle('active', open);
  if (drawer) drawer.classList.toggle('active', open);
  document.body.style.overflow = open ? 'hidden' : '';
}

async function refreshCart() {
  try {
    const res = await fetch(API_BASE + 'products/get_cart.php');
    const data = await res.json();
    if (data.success) {
      cartItems = data.items;
      cartTotal = data.totalPrice;
      cartCount = data.totalItems;
      renderCart();
      updateCartBadge();
    }
  } catch (err) { /* silent */ }
}

function renderCart() {
  const body = document.getElementById('cartBody');
  const totalEl = document.getElementById('cartTotalPrice');
  if (!body) return;

  if (cartItems.length === 0) {
    body.innerHTML = `
      <div class="empty-state" style="padding:40px 0">
        <div class="empty-state__icon">🛒</div>
        <h3 class="empty-state__title" style="font-size:1rem">Cart is empty</h3>
        <p class="empty-state__text" style="font-size:0.85rem">Browse products and add items!</p>
      </div>`;
  } else {
    body.innerHTML = cartItems.map(item => `
      <div class="cart-item">
        <div class="cart-item__image">${item.image || '📦'}</div>
        <div class="cart-item__info">
          <div class="cart-item__name">${item.name}</div>
          <div class="cart-item__price">${formatCurrency(item.price)} × ${item.quantity} = ${formatCurrency(item.lineTotal)}</div>
        </div>
        <div class="cart-item__controls">
          <button class="cart-item__qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity - 1})">−</button>
          <span class="cart-item__qty">${item.quantity}</span>
          <button class="cart-item__qty-btn" onclick="updateCartQty(${item.id}, ${item.quantity + 1})">+</button>
          <button class="btn btn--xs btn--danger" onclick="removeFromCart(${item.id})" style="margin-left:8px">🗑</button>
        </div>
      </div>`
    ).join('');
  }

  if (totalEl) totalEl.textContent = formatCurrency(cartTotal);

  const checkoutBtn = document.getElementById('checkoutBtn');
  if (checkoutBtn) checkoutBtn.disabled = cartItems.length === 0;
}

function updateCartBadge() {
  const badge = document.getElementById('cartBadgeCount');
  if (badge) {
    badge.textContent = cartCount;
    badge.style.display = cartCount > 0 ? 'flex' : 'none';
  }
}

async function addToCart(productId) {
  if (!requireLogin()) return;

  try {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    const res = await fetch(API_BASE + 'products/add_to_cart.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', data.message);
      await refreshCart();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to add to cart.');
  }
}

async function updateCartQty(productId, newQty) {
  try {
    if (newQty <= 0) {
      const formData = new FormData();
      formData.append('product_id', productId);
      await fetch(API_BASE + 'products/remove_from_cart.php', { method: 'POST', body: formData });
    } else {
      const formData = new FormData();
      formData.append('product_id', productId);
      formData.append('quantity', newQty);
      await fetch(API_BASE + 'products/update_cart.php', { method: 'POST', body: formData });
    }
    await refreshCart();
  } catch (err) {
    showToast('error', 'Failed to update cart.');
  }
}

async function removeFromCart(productId) {
  try {
    const formData = new FormData();
    formData.append('product_id', productId);
    const res = await fetch(API_BASE + 'products/remove_from_cart.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
      showToast('success', 'Item removed from cart.');
      await refreshCart();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to remove item.');
  }
}

async function checkout() {
  if (!requireLogin()) return;
  if (cartItems.length === 0) return showToast('warning', 'Your cart is empty.');

  const checkoutBtn = document.getElementById('checkoutBtn');
  if (checkoutBtn) { checkoutBtn.innerHTML = '<span class="spinner"></span> Processing...'; checkoutBtn.disabled = true; }

  try {
    const res = await fetch(API_BASE + 'products/checkout.php', { method: 'POST' });
    const data = await res.json();

    if (data.success) {
      toggleCart(false);
      showToast('success', `Order #${data.order_id} placed! Total: ${formatCurrency(data.total)} 🎉`);
      showConfetti();
      await refreshCart();
      await fetchProducts();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Checkout failed. Please try again.');
  } finally {
    if (checkoutBtn) { checkoutBtn.innerHTML = '💳 Checkout'; checkoutBtn.disabled = false; }
  }
}
