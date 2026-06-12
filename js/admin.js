// =============================================
//  ADMIN PANEL — Dashboard, Bookings, Users
// =============================================

let adminBookings = [];
let adminUsers = [];
let adminClasses = [];

document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  updateNavbarUser();

  if (!currentUser || currentUser.role !== 'admin') {
    showToast('error', 'Admin access required.');
    setTimeout(() => { window.location.href = 'index.html'; }, 1200);
    return;
  }

  initAdminTabs();
  await loadDashboard();
});

function initAdminTabs() {
  document.querySelectorAll('.admin-sidebar__link').forEach(link => {
    link.addEventListener('click', (e) => {
      const tab = link.dataset.tab;

      if (!tab) return;

      e.preventDefault();
      document.querySelectorAll('.admin-sidebar__link').forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      document.querySelectorAll('.admin-tab-content').forEach(c => c.classList.remove('active'));
      const target = document.getElementById(tab);
      if (target) target.classList.add('active');

      if (tab === 'tab-bookings') loadBookings();
      if (tab === 'tab-users') loadUsers();
      if (tab === 'tab-dashboard') loadDashboard();
    });
  });
}

// ---------- Dashboard ----------
async function loadDashboard() {
  try {
    const res = await fetch(API_BASE + 'admin/get_dashboard_stats.php');
    const data = await res.json();
    if (data.success) {
      const s = data.stats;
      animateCounter('adminStatUsers', s.totalUsers);
      animateCounter('adminStatBookings', s.totalBookings);
      animateCounter('adminStatProducts', s.totalProducts);
      animateCounter('adminStatRevenue', s.totalRevenue);
      animateCounter('adminStatOrders', s.totalOrders);
      animateCounter('adminStatBlocked', s.blockedUsers);
      animateCounter('adminStatClasses', s.activeClasses);
      animateCounter('adminStatSoldOut', s.soldOutProducts);
    }
  } catch (err) {
    showToast('error', 'Failed to load dashboard stats.');
  }
}

// ---------- Bookings Management ----------
async function loadBookings() {
  const tbody = document.getElementById('adminBookingsBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="7"><div class="page-loading"><div class="spinner spinner--lg"></div></div></td></tr>';

  try {
    const [bookingsRes, classesRes] = await Promise.all([
      fetch(API_BASE + 'admin/get_all_bookings.php'),
      fetch(API_BASE + 'get_classes.php')
    ]);
    const bookingsData = await bookingsRes.json();
    const classesData = await classesRes.json();

    if (bookingsData.success) adminBookings = bookingsData.bookings;
    if (classesData.success) adminClasses = classesData.classes;

    renderAdminBookings();
  } catch (err) {
    showToast('error', 'Failed to load bookings.');
  }
}

function renderAdminBookings(filter = '') {
  const tbody = document.getElementById('adminBookingsBody');
  if (!tbody) return;

  let filtered = adminBookings;
  if (filter) {
    const q = filter.toLowerCase();
    filtered = adminBookings.filter(b =>
      b.userName.toLowerCase().includes(q) ||
      b.className.toLowerCase().includes(q) ||
      b.userEmail.toLowerCase().includes(q)
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:40px"><div class="empty-state__icon">📋</div><h3 class="empty-state__title">No bookings found</h3></div></td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(b => `
    <tr>
      <td>#${b.id}</td>
      <td>
        <div class="user-info-cell">
          <div class="user-avatar">${getInitials(b.userName)}</div>
          <div>
            <div class="user-info-cell__name">${b.userName}</div>
            <div class="user-info-cell__email">${b.userEmail}</div>
          </div>
        </div>
      </td>
      <td style="font-weight:600">${b.className}</td>
      <td>${b.day}</td>
      <td>${b.time}</td>
      <td><span class="status-badge status-badge--${b.status}">● ${capitalize(b.status)}</span></td>
      <td>
        <button class="btn btn--xs btn--danger" onclick="adminRemoveBooking(${b.id})">Remove</button>
      </td>
    </tr>`
  ).join('');
}

async function adminRemoveBooking(bookingId) {
  if (!confirm('Remove this booking?')) return;

  try {
    const formData = new FormData();
    formData.append('booking_id', bookingId);
    const res = await fetch(API_BASE + 'admin/remove_booking.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', 'Booking removed.');
      await loadBookings();
      await loadDashboard();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to remove booking.');
  }
}

// ---------- Add Booking Modal ----------
function showAddBookingModal() {
  const userSelect = document.getElementById('addBookingUser');
  const classSelect = document.getElementById('addBookingClass');

  // Populate users
  if (userSelect && adminUsers.length > 0) {
    userSelect.innerHTML = '<option value="">Select User</option>' +
      adminUsers.filter(u => u.status === 'active').map(u => `<option value="${u.id}">${u.fullName} (${u.email})</option>`).join('');
  }

  // Populate classes
  if (classSelect && adminClasses.length > 0) {
    classSelect.innerHTML = '<option value="">Select Class</option>' +
      adminClasses.map(c => `<option value="${c.id}">${c.name} — ${c.day} ${c.time}</option>`).join('');
  }

  openModal('addBookingModal');
}

async function submitAddBooking() {
  const userId = document.getElementById('addBookingUser')?.value;
  const scheduleId = document.getElementById('addBookingClass')?.value;

  if (!userId || !scheduleId) return showToast('warning', 'Please select a user and class.');

  try {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('schedule_id', scheduleId);

    const res = await fetch(API_BASE + 'admin/add_booking.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      closeModal('addBookingModal');
      showToast('success', 'Booking added!');
      await loadBookings();
      await loadDashboard();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to add booking.');
  }
}

// ---------- Users Management ----------
async function loadUsers() {
  const tbody = document.getElementById('adminUsersBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="7"><div class="page-loading"><div class="spinner spinner--lg"></div></div></td></tr>';

  try {
    const res = await fetch(API_BASE + 'admin/get_all_users.php');
    const data = await res.json();

    if (data.success) {
      adminUsers = data.users;
      renderAdminUsers();
    }
  } catch (err) {
    showToast('error', 'Failed to load users.');
  }
}

function renderAdminUsers(filter = '') {
  const tbody = document.getElementById('adminUsersBody');
  if (!tbody) return;

  let filtered = adminUsers;
  if (filter) {
    const q = filter.toLowerCase();
    filtered = adminUsers.filter(u =>
      u.fullName.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q)
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:40px"><div class="empty-state__icon">👥</div><h3 class="empty-state__title">No users found</h3></div></td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(u => `
    <tr>
      <td>GYM-${String(u.id).padStart(4, '0')}</td>
      <td>
        <div class="user-info-cell">
          <div class="user-avatar">${getInitials(u.fullName)}</div>
          <div>
            <div class="user-info-cell__name">${u.fullName}</div>
            <div class="user-info-cell__email">${u.email}</div>
          </div>
        </div>
      </td>
      <td><span class="status-badge status-badge--${u.role}">${capitalize(u.role)}</span></td>
      <td>${u.totalBookings}</td>
      <td>${u.totalOrders}</td>
      <td><span class="status-badge status-badge--${u.status}">● ${capitalize(u.status)}</span></td>
      <td>
        ${u.role !== 'admin' ? (
          u.status === 'active'
            ? `<button class="btn btn--xs btn--danger" onclick="toggleUserStatus(${u.id}, 'block')">Block</button>`
            : `<button class="btn btn--xs btn--success" onclick="toggleUserStatus(${u.id}, 'unblock')">Unblock</button>`
        ) : '<span style="color:var(--text-muted);font-size:0.78rem">—</span>'}
      </td>
    </tr>`
  ).join('');
}

async function toggleUserStatus(userId, action) {
  if (!confirm(`${capitalize(action)} this user?`)) return;

  try {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);

    const res = await fetch(API_BASE + 'admin/block_user.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', data.message);
      await loadUsers();
      await loadDashboard();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to update user.');
  }
}

// Search handlers
function searchBookings(query) {
  renderAdminBookings(query);
}

function searchUsers(query) {
  renderAdminUsers(query);
}
