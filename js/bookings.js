// =============================================
//  BOOKINGS PAGE — My Bookings
// =============================================

let myPageBookings = [];

document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  updateNavbarUser();

  if (!currentUser) {
    showToast('warning', 'Please login to view your bookings.');
    setTimeout(() => { window.location.href = 'login.html'; }, 1200);
    return;
  }

  await fetchMyBookings();
});

async function fetchMyBookings() {
  const tbody = document.getElementById('bookingsBody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="6"><div class="page-loading"><div class="spinner spinner--lg"></div></div></td></tr>';

  try {
    const res = await fetch(API_BASE + 'get_bookings.php');
    const data = await res.json();

    if (data.success) {
      myPageBookings = data.bookings;
      renderMyBookings();
      const countEl = document.getElementById('bookingCount');
      if (countEl) countEl.textContent = myPageBookings.length;
    } else {
      showToast('error', data.message || 'Failed to load bookings.');
    }
  } catch (err) {
    showToast('error', 'Failed to connect to the server.');
  }
}

function renderMyBookings() {
  const tbody = document.getElementById('bookingsBody');
  if (!tbody) return;

  if (myPageBookings.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="6">
          <div class="empty-state">
            <div class="empty-state__icon">📋</div>
            <h3 class="empty-state__title">No bookings yet</h3>
            <p class="empty-state__text">Browse our classes and book your first session!</p>
            <a href="classes.html" class="btn btn--primary btn--sm">Browse Classes</a>
          </div>
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = myPageBookings.map(b => `
    <tr>
      <td style="font-weight:600; color:var(--text-primary)">${b.className}</td>
      <td>👤 ${b.instructor}</td>
      <td>📅 ${b.day}</td>
      <td>⏰ ${b.time}</td>
      <td><span class="status-badge status-badge--${b.status}">● ${capitalize(b.status)}</span></td>
      <td>
        <button class="btn btn--sm btn--danger" onclick="cancelBooking(${b.id}, '${b.className.replace(/'/g, "\\'")}')">Cancel</button>
      </td>
    </tr>`
  ).join('');
}

async function cancelBooking(bookingId, className) {
  if (!confirm(`Are you sure you want to cancel your booking for ${className}?`)) return;

  try {
    const formData = new FormData();
    formData.append('booking_id', bookingId);

    const res = await fetch(API_BASE + 'cancel_booking.php', { method: 'POST', body: formData });
    const result = await res.json();

    if (result.success) {
      showToast('info', 'Booking cancelled successfully.');
      await fetchMyBookings();
    } else {
      showToast('error', result.message || 'Failed to cancel booking.');
    }
  } catch (err) {
    showToast('error', 'Network error while cancelling.');
  }
}
