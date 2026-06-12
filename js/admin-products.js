// =============================================
//  ADMIN PRODUCTS — Product Management
// =============================================

let adminProducts = [];

document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  updateNavbarUser();

  if (!currentUser || currentUser.role !== 'admin') {
    showToast('error', 'Admin access required.');
    setTimeout(() => { window.location.href = 'index.html'; }, 1200);
    return;
  }

  await loadAdminProducts();
});

async function loadAdminProducts() {
  const tbody = document.getElementById('adminProductsBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="8"><div class="page-loading"><div class="spinner spinner--lg"></div></div></td></tr>';

  try {
    const res = await fetch(API_BASE + 'admin/get_all_products.php');
    const data = await res.json();

    if (data.success) {
      adminProducts = data.products;
      renderAdminProducts();
    }
  } catch (err) {
    showToast('error', 'Failed to load products.');
  }
}

function renderAdminProducts(filter = '') {
  const tbody = document.getElementById('adminProductsBody');
  if (!tbody) return;

  let filtered = adminProducts;
  if (filter) {
    const q = filter.toLowerCase();
    filtered = adminProducts.filter(p => p.name.toLowerCase().includes(q) || p.category.toLowerCase().includes(q));
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state" style="padding:40px"><div class="empty-state__icon">📦</div><h3 class="empty-state__title">No products found</h3></div></td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(p => `
    <tr>
      <td>#${p.id}</td>
      <td style="font-size:1.5rem">${p.image || '📦'}</td>
      <td style="font-weight:600; color:var(--text-primary)">${p.name}</td>
      <td><span class="status-badge status-badge--member">${capitalize(p.category)}</span></td>
      <td style="font-weight:700">${formatCurrency(p.price)}</td>
      <td>${p.stock}</td>
      <td><span class="status-badge status-badge--${p.status}">● ${p.status.replace('_', ' ')}</span></td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button class="btn btn--xs btn--secondary" onclick="editProduct(${p.id})">Edit</button>
          ${p.status === 'available'
            ? `<button class="btn btn--xs btn--warning" onclick="toggleProductStatus(${p.id}, 'sold_out')">Mark Sold Out</button>`
            : p.status === 'sold_out'
              ? `<button class="btn btn--xs btn--success" onclick="toggleProductStatus(${p.id}, 'available')">Restock</button>`
              : ''
          }
          <button class="btn btn--xs btn--danger" onclick="deleteProduct(${p.id}, '${p.name.replace(/'/g, "\\'")}')">Delete</button>
        </div>
      </td>
    </tr>`
  ).join('');
}

// ---------- Add Product ----------
function showAddProductModal() {
  document.getElementById('productModalTitle').textContent = 'Add New Product';
  document.getElementById('productForm').reset();
  document.getElementById('productFormId').value = '';
  openModal('productModal');
}

function editProduct(productId) {
  const p = adminProducts.find(x => x.id === productId);
  if (!p) return;

  document.getElementById('productModalTitle').textContent = 'Edit Product';
  document.getElementById('productFormId').value = p.id;
  document.getElementById('productName').value = p.name;
  document.getElementById('productDescription').value = p.description || '';
  document.getElementById('productCategory').value = p.category;
  document.getElementById('productPrice').value = p.price;
  document.getElementById('productStock').value = p.stock;
  document.getElementById('productImage').value = p.image || '';

  openModal('productModal');
}

async function submitProductForm() {
  const productId = document.getElementById('productFormId').value;
  const name = document.getElementById('productName').value.trim();
  const description = document.getElementById('productDescription').value.trim();
  const category = document.getElementById('productCategory').value;
  const price = document.getElementById('productPrice').value;
  const stock = document.getElementById('productStock').value;
  const image = document.getElementById('productImage').value.trim();

  if (!name) return showToast('warning', 'Product name is required.');
  if (!category) return showToast('warning', 'Category is required.');
  if (!price || parseFloat(price) < 0) return showToast('warning', 'Valid price is required.');

  const formData = new FormData();
  formData.append('name', name);
  formData.append('description', description);
  formData.append('category', category);
  formData.append('price', price);
  formData.append('stock', stock || 0);
  formData.append('image', image);

  const url = productId
    ? API_BASE + 'admin/update_product.php'
    : API_BASE + 'admin/add_product.php';

  if (productId) formData.append('product_id', productId);

  try {
    const res = await fetch(url, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      closeModal('productModal');
      showToast('success', data.message);
      await loadAdminProducts();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to save product.');
  }
}

async function toggleProductStatus(productId, newStatus) {
  try {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('status', newStatus);

    const res = await fetch(API_BASE + 'admin/toggle_product_status.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', data.message);
      await loadAdminProducts();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to update status.');
  }
}

async function deleteProduct(productId, productName) {
  const confirmed = await showConfirmModal({
    title: 'Remove product',
    message: `Delete "${productName}"? This will mark it as discontinued.`,
    confirmText: 'Delete Product',
    variant: 'danger',
    icon: '🗑️'
  });

  if (!confirmed) return;

  try {
    const formData = new FormData();
    formData.append('product_id', productId);

    const res = await fetch(API_BASE + 'admin/delete_product.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', data.message);
      await loadAdminProducts();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to delete product.');
  }
}

function searchProducts(query) {
  renderAdminProducts(query);
}
