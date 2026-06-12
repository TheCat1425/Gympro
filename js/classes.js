// =============================================
//  CLASSES PAGE — Browse & Book Classes
// =============================================

let gymClasses = [];
let filteredClasses = [];
let myBookings = [];

document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  updateNavbarUser();
  initFilters();
  initModalEvents();
  await fetchClassesData();
});

async function fetchClassesData() {
  const grid = document.getElementById('classesGrid');
  if (grid) grid.innerHTML = '<div class="page-loading"><div class="spinner spinner--lg"></div></div>';

  try {
    const classesRes = await fetch(API_BASE + 'get_classes.php');
    const classesData = await classesRes.json();
    if (classesData.success) {
      gymClasses = classesData.classes;
      filteredClasses = [...gymClasses];
    }

    if (currentUser) {
      const bookingsRes = await fetch(API_BASE + 'get_bookings.php');
      const bookingsData = await bookingsRes.json();
      if (bookingsData.success) myBookings = bookingsData.bookings;
    }

    renderClasses(filteredClasses);
    updateClassStats();
  } catch (err) {
    showToast('error', 'Failed to load classes. Is the server running?');
    if (grid) grid.innerHTML = '<div class="empty-state"><div class="empty-state__icon">⚠️</div><h3 class="empty-state__title">Connection Error</h3><p class="empty-state__text">Could not connect to the server.</p></div>';
  }
}

function initFilters() {
  const searchInput = document.getElementById('searchInput');
  const categoryFilter = document.getElementById('categoryFilter');
  const dayFilter = document.getElementById('dayFilter');
  const levelFilter = document.getElementById('levelFilter');

  const applyFilters = () => {
    const search = (searchInput?.value || '').toLowerCase().trim();
    const category = categoryFilter?.value || '';
    const day = dayFilter?.value || '';
    const level = levelFilter?.value || '';

    filteredClasses = gymClasses.filter(c => {
      const matchSearch = !search ||
        c.name.toLowerCase().includes(search) ||
        c.instructor.toLowerCase().includes(search) ||
        c.category.toLowerCase().includes(search);
      const matchCategory = !category || c.category === category;
      const matchDay = !day || c.day === day;
      const matchLevel = !level || c.level === level;
      return matchSearch && matchCategory && matchDay && matchLevel;
    });

    renderClasses(filteredClasses);
  };

  if (searchInput) searchInput.addEventListener('input', debounce(applyFilters, 200));
  if (categoryFilter) categoryFilter.addEventListener('change', applyFilters);
  if (dayFilter) dayFilter.addEventListener('change', applyFilters);
  if (levelFilter) levelFilter.addEventListener('change', applyFilters);
}

function renderClasses(classes) {
  const grid = document.getElementById('classesGrid');
  if (!grid) return;

  if (classes.length === 0) {
    grid.innerHTML = `
      <div class="empty-state" style="grid-column: 1/-1;">
        <div class="empty-state__icon">🔍</div>
        <h3 class="empty-state__title">No classes found</h3>
        <p class="empty-state__text">Try adjusting your filters or search terms.</p>
      </div>`;
    return;
  }

  const categoryIcons = { Cardio: '🔥', Yoga: '🧘', Strength: '💪', Flexibility: '🤸', Combat: '🥊' };

  grid.innerHTML = classes.map(cls => {
    const spotsLeft = cls.capacity - cls.enrolled;
    let spotsClass = 'available', spotsText = `${spotsLeft} spots left`;
    if (spotsLeft === 0) { spotsClass = 'full'; spotsText = 'Class Full'; }
    else if (spotsLeft <= 3) { spotsClass = 'limited'; spotsText = `Only ${spotsLeft} left!`; }

    const isBooked = myBookings.some(b => b.scheduleId === cls.id);
    const isFull = spotsLeft === 0;

    return `
      <div class="class-card" data-class-id="${cls.id}">
        <div class="class-card__banner"></div>
        <div class="class-card__body">
          <span class="class-card__category">${categoryIcons[cls.category] || '⚡'} ${cls.category}</span>
          <h3 class="class-card__name">${cls.name}</h3>
          <p class="class-card__instructor">👤 ${cls.instructor}</p>
          <div class="class-card__meta">
            <div class="class-card__meta-item"><span class="icon">📅</span> ${cls.day}</div>
            <div class="class-card__meta-item"><span class="icon">⏰</span> ${cls.time}</div>
            <div class="class-card__meta-item"><span class="icon">⏱️</span> ${cls.duration} min</div>
            <div class="class-card__meta-item"><span class="icon">📊</span> ${cls.level}</div>
          </div>
          <div class="class-card__footer">
            <span class="class-card__spots class-card__spots--${spotsClass}">${spotsText}</span>
            ${isBooked
              ? `<button class="btn btn--sm btn--success btn--disabled" disabled>✓ Booked</button>`
              : isFull
                ? `<button class="btn btn--sm btn--secondary btn--disabled" disabled>Full</button>`
                : `<button class="btn btn--sm btn--primary" onclick="openBookingModal(${cls.id})">Book Now</button>`
            }
          </div>
        </div>
      </div>`;
  }).join('');
}

// ---------- Booking Modal ----------
function initModalEvents() {
  const overlay = document.getElementById('modalOverlay');
  const closeBtn = document.getElementById('modalClose');
  const cancelBtn = document.getElementById('modalCancel');

  if (closeBtn) closeBtn.addEventListener('click', () => closeModal('modalOverlay'));
  if (cancelBtn) cancelBtn.addEventListener('click', () => closeModal('modalOverlay'));
  if (overlay) overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal('modalOverlay'); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal('modalOverlay'); });

  // Success popup close
  const successOverlay = document.getElementById('bookingSuccessOverlay');
  if (successOverlay) {
    successOverlay.addEventListener('click', (e) => { if (e.target === successOverlay) closeModal('bookingSuccessOverlay'); });
  }
}

function openBookingModal(classId) {
  if (!requireLogin()) return;

  const cls = gymClasses.find(c => c.id === classId);
  if (!cls) return;

  const spotsLeft = cls.capacity - cls.enrolled;
  document.getElementById('modalClassName').textContent = cls.name;
  document.getElementById('modalInstructor').textContent = cls.instructor;
  document.getElementById('modalDay').textContent = cls.day;
  document.getElementById('modalTime').textContent = cls.time;
  document.getElementById('modalDuration').textContent = `${cls.duration} min`;
  document.getElementById('modalSpots').textContent = `${spotsLeft} / ${cls.capacity}`;
  document.getElementById('modalLevel').textContent = cls.level;
  document.getElementById('modalCategory').textContent = cls.category;

  const confirmBtn = document.getElementById('modalConfirm');
  confirmBtn.onclick = () => bookClass(classId);
  confirmBtn.innerHTML = '✓ Confirm Booking';
  confirmBtn.disabled = false;

  openModal('modalOverlay');
}

async function bookClass(scheduleId) {
  const confirmBtn = document.getElementById('modalConfirm');
  confirmBtn.innerHTML = '<span class="spinner"></span> Booking...';
  confirmBtn.disabled = true;

  try {
    const formData = new FormData();
    formData.append('schedule_id', scheduleId);

    const res = await fetch(API_BASE + 'book_class.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.success) {
      closeModal('modalOverlay');
      // Show success popup
      openModal('bookingSuccessOverlay');
      showConfetti();
      // Refresh data
      await fetchClassesData();
    } else {
      showToast('error', result.message || 'Booking failed.');
    }
  } catch (err) {
    showToast('error', 'Network error — could not reach the server.');
  } finally {
    confirmBtn.innerHTML = '✓ Confirm Booking';
    confirmBtn.disabled = false;
  }
}

function updateClassStats() {
  animateCounter('statClasses', gymClasses.length);
  animateCounter('statSpots', gymClasses.reduce((sum, c) => sum + (c.capacity - c.enrolled), 0));
  animateCounter('statBookings', myBookings.length);
  animateCounter('statCategories', [...new Set(gymClasses.map(c => c.category))].length);
}
