// =============================================
//  ADMIN PANEL — Dashboard, Bookings, Users
// =============================================

let adminBookings = [];
let adminUsers = [];
let adminClasses = [];
let adminInstructors = [];

document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  updateNavbarUser();

  if (!currentUser || currentUser.role !== 'admin') {
    showToast('error', 'Admin access required.');
    setTimeout(() => { window.location.href = 'index.html'; }, 1200);
    return;
  }

  const pageName = window.location.pathname.split('/').pop();

  if (pageName === 'admin-bookings.html') {
    await loadBookings();
    return;
  }

  if (pageName === 'admin-users.html') {
    await loadUsers();
    return;
  }

  if (pageName === 'admin-instructors.html') {
    await loadInstructors();
    return;
  }

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
      animateCounter('adminStatInstructors', s.activeInstructors);
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

  if (adminUsers.length === 0) {
    await loadUsers();
  }

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
  const confirmed = await showConfirmModal({
    title: 'Remove booking',
    message: 'This will permanently remove the booking from the system.',
    confirmText: 'Remove Booking',
    variant: 'danger',
    icon: '🗑️'
  });

  if (!confirmed) return;

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
async function showAddBookingModal() {
  const userSelect = document.getElementById('addBookingUser');
  const classSelect = document.getElementById('addBookingClass');

  if (adminUsers.length === 0) {
    await loadUsers();
  }

  // Populate users
  if (userSelect) {
    userSelect.innerHTML = '<option value="">Select User</option>' +
      adminUsers.filter(u => u.status === 'active').map(u => `<option value="${u.id}">${u.fullName} (${u.email})</option>`).join('');
  }

  // Populate classes
  if (classSelect) {
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
  const confirmed = await showConfirmModal({
    title: action === 'block' ? 'Block user' : 'Unblock user',
    message: action === 'block'
      ? 'Blocked users will lose access until they are unblocked.'
      : 'This will restore the user account access.',
    confirmText: action === 'block' ? 'Block User' : 'Unblock User',
    variant: action === 'block' ? 'danger' : 'success',
    icon: action === 'block' ? '🚫' : '✅'
  });

  if (!confirmed) return;

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

// ---------- Instructor Management ----------
async function loadInstructors() {
  const tbody = document.getElementById('adminInstructorsBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="7"><div class="page-loading"><div class="spinner spinner--lg"></div></div></td></tr>';

  try {
    const res = await fetch(API_BASE + 'admin/get_all_instructors.php');
    const data = await res.json();

    if (data.success) {
      adminInstructors = data.instructors;
      renderAdminInstructors();
    }
  } catch (err) {
    showToast('error', 'Failed to load instructors.');
  }
}

function renderAdminInstructors(filter = '') {
  const tbody = document.getElementById('adminInstructorsBody');
  if (!tbody) return;

  let filtered = adminInstructors;
  if (filter) {
    const q = filter.toLowerCase();
    filtered = adminInstructors.filter(i =>
      i.fullName.toLowerCase().includes(q) ||
      (i.specialty || '').toLowerCase().includes(q) ||
      (i.email || '').toLowerCase().includes(q)
    );
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state" style="padding:40px"><div class="empty-state__icon">🏋️</div><h3 class="empty-state__title">No instructors found</h3></div></td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(i => `
    <tr>
      <td>#INS-${String(i.id).padStart(4, '0')}</td>
      <td>
        <div class="user-info-cell">
          <div class="user-avatar">${getInitials(i.fullName)}</div>
          <div>
            <div class="user-info-cell__name">${i.fullName}</div>
            <div class="user-info-cell__email">${i.email || 'No email'}</div>
          </div>
        </div>
      </td>
      <td>${i.specialty || 'General Fitness'}</td>
      <td>${i.totalClasses}</td>
      <td>${i.totalBookings}</td>
      <td><span class="status-badge status-badge--${i.status}">● ${capitalize(i.status)}</span></td>
      <td>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
          <button class="btn btn--xs btn--secondary" onclick="editInstructor(${i.id})">Edit</button>
          ${i.status === 'active'
            ? `<button class="btn btn--xs btn--warning" onclick="toggleInstructorStatus(${i.id}, 'block')">Block</button>`
            : i.status === 'blocked'
              ? `<button class="btn btn--xs btn--success" onclick="toggleInstructorStatus(${i.id}, 'unblock')">Unblock</button>`
              : ''
          }
          ${i.status !== 'removed' ? `<button class="btn btn--xs btn--danger" onclick="removeInstructor(${i.id})">Remove</button>` : ''}
        </div>
      </td>
    </tr>`
  ).join('');
}

function showAddInstructorModal() {
  const form = document.getElementById('instructorForm');
  if (form) form.reset();
  const formId = document.getElementById('instructorFormId');
  if (formId) formId.value = '';
  const title = document.getElementById('instructorModalTitle');
  if (title) title.textContent = 'Add Instructor';
  openModal('instructorModal');
}

function editInstructor(instructorId) {
  const instructor = adminInstructors.find(i => i.id === instructorId);
  if (!instructor) return;

  document.getElementById('instructorFormId').value = instructor.id;
  document.getElementById('instructorFullName').value = instructor.fullName;
  document.getElementById('instructorSpecialty').value = instructor.specialty || '';
  document.getElementById('instructorEmail').value = instructor.email || '';
  document.getElementById('instructorPhone').value = instructor.phone || '';
  document.getElementById('instructorBio').value = instructor.bio || '';
  document.getElementById('instructorDay').value = '';
  document.getElementById('instructorTime').value = '';

  const title = document.getElementById('instructorModalTitle');
  if (title) title.textContent = 'Edit Instructor';
  openModal('instructorModal');
}

async function submitInstructorForm() {
  const instructorId = document.getElementById('instructorFormId')?.value;
  const fullName = document.getElementById('instructorFullName')?.value.trim();
  const specialty = document.getElementById('instructorSpecialty')?.value.trim();
  const email = document.getElementById('instructorEmail')?.value.trim();
  const phone = document.getElementById('instructorPhone')?.value.trim();
  const bio = document.getElementById('instructorBio')?.value.trim();
  const day = document.getElementById('instructorDay')?.value.trim();
  const time = document.getElementById('instructorTime')?.value.trim();

  if (!fullName || !specialty || !day || !time) {
    return showToast('warning', 'Please fill in all required fields.');
  }

  const formData = new FormData();
  formData.append('full_name', fullName);
  formData.append('specialty', specialty);
  formData.append('email', email);
  formData.append('phone', phone);
  formData.append('bio', bio);
  formData.append('day_of_week', day);
  formData.append('start_time', time);

  const url = instructorId
    ? API_BASE + 'admin/update_instructor.php'
    : API_BASE + 'admin/add_instructor.php';

  if (instructorId) formData.append('instructor_id', instructorId);

  try {
    const res = await fetch(url, { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      closeModal('instructorModal');
      showToast('success', data.message);
      await loadInstructors();
      await loadDashboard();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to save instructor.');
  }
}

async function toggleInstructorStatus(instructorId, action) {
  const confirmed = await showConfirmModal({
    title: action === 'block' ? 'Block instructor' : 'Unblock instructor',
    message: action === 'block'
      ? 'Blocked instructors remain on file but are hidden from active scheduling.'
      : 'This will restore the instructor to active status.',
    confirmText: action === 'block' ? 'Block Instructor' : 'Unblock Instructor',
    variant: action === 'block' ? 'danger' : 'success',
    icon: action === 'block' ? '🚫' : '✅'
  });

  if (!confirmed) return;

  try {
    const formData = new FormData();
    formData.append('instructor_id', instructorId);
    formData.append('action', action);

    const res = await fetch(API_BASE + 'admin/block_instructor.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', data.message);
      await loadInstructors();
      await loadDashboard();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to update instructor.');
  }
}

async function removeInstructor(instructorId) {
  const confirmed = await showConfirmModal({
    title: 'Remove instructor',
    message: 'This will archive the instructor and keep existing class records intact.',
    confirmText: 'Remove Instructor',
    variant: 'danger',
    icon: '🗑️'
  });

  if (!confirmed) return;

  try {
    const formData = new FormData();
    formData.append('instructor_id', instructorId);

    const res = await fetch(API_BASE + 'admin/remove_instructor.php', { method: 'POST', body: formData });
    const data = await res.json();

    if (data.success) {
      showToast('success', data.message);
      await loadInstructors();
      await loadDashboard();
    } else {
      showToast('error', data.message);
    }
  } catch (err) {
    showToast('error', 'Failed to remove instructor.');
  }
}

// Search handlers
function searchBookings(query) {
  renderAdminBookings(query);
}

function searchUsers(query) {
  renderAdminUsers(query);
}

function searchInstructors(query) {
  renderAdminInstructors(query);
}
