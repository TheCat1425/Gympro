// =============================================
//  GYM PRO — Shared Utilities (app.js)
//  Loaded on every page
// =============================================

const API_BASE = 'api/';

// ---------- Current User State ----------
let currentUser = null;

// ---------- DOM Ready ----------
document.addEventListener('DOMContentLoaded', async () => {
  await checkAuth();
  initNavbar();
  updateNavbarUser();
  animateOnScroll();
});

// =============================================
//  AUTH CHECK
// =============================================
async function checkAuth() {
  try {
    const res = await fetch(API_BASE + 'auth/me.php');
    const data = await res.json();
    if (data.success && data.logged_in) {
      currentUser = data.user;
    } else {
      currentUser = null;
    }
  } catch (e) {
    currentUser = null;
  }
  return currentUser;
}

function requireLogin() {
  if (!currentUser) {
    showToast('warning', 'Please login to continue.');
    setTimeout(() => { window.location.href = 'login.html'; }, 1000);
    return false;
  }
  return true;
}

function isAdmin() {
  return currentUser && currentUser.role === 'admin';
}

// =============================================
//  NAVBAR
// =============================================
function initNavbar() {
  const navbar = document.getElementById('navbar');
  const hamburger = document.getElementById('hamburger');

  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 40);
    });
    // Set initial state
    navbar.classList.toggle('scrolled', window.scrollY > 40);
  }

  // Smooth scroll links
  document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href');
      if (href === '#') return;
      const target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  // Hamburger
  if (hamburger) {
    hamburger.addEventListener('click', () => {
      const links = document.getElementById('navLinks');
      if (links) links.classList.toggle('navbar__links--open');
    });
  }
}

function updateNavbarUser() {
  const userIndicator = document.getElementById('userIndicator');
  const authBtns = document.getElementById('authButtons');
  const adminLink = document.getElementById('adminNavLink');

  if (currentUser) {
    if (userIndicator) {
      userIndicator.style.display = 'flex';
      userIndicator.innerHTML = `<span class="dot"></span><span>${currentUser.full_name}</span>`;
    }
    if (authBtns) {
      authBtns.innerHTML = `<button class="btn btn--ghost btn--sm" onclick="logout()" id="logoutBtn">Logout</button>`;
    }
    if (adminLink && currentUser.role === 'admin') {
      adminLink.style.display = 'block';
    }
  } else {
    if (userIndicator) userIndicator.style.display = 'none';
    if (authBtns) {
      authBtns.innerHTML = `
        <a href="login.html" class="btn btn--ghost btn--sm">Login</a>
        <a href="register.html" class="btn btn--primary btn--sm">Sign Up</a>`;
    }
    if (adminLink) adminLink.style.display = 'none';
  }
}

async function logout() {
  try {
    await fetch(API_BASE + 'auth/logout.php', { method: 'POST' });
    currentUser = null;
    showToast('info', 'Logged out successfully.');
    setTimeout(() => { window.location.href = 'index.html'; }, 800);
  } catch (e) {
    showToast('error', 'Logout failed.');
  }
}

// =============================================
//  TOAST NOTIFICATIONS
// =============================================
function showToast(type, message) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    container.id = 'toastContainer';
    document.body.appendChild(container);
  }

  const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };

  const toast = document.createElement('div');
  toast.className = `toast toast--${type}`;
  toast.innerHTML = `
    <span class="toast__icon">${icons[type] || 'ℹ️'}</span>
    <span class="toast__message">${message}</span>
    <button class="toast__close" onclick="this.parentElement.classList.add('removing'); setTimeout(() => this.parentElement.remove(), 300)">✕</button>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    if (toast.parentElement) {
      toast.classList.add('removing');
      setTimeout(() => toast.remove(), 300);
    }
  }, 4000);
}

// =============================================
//  SCROLL ANIMATIONS
// =============================================
function animateOnScroll() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-in');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.section, .stat-card, .admin-stat-card').forEach(el => {
    observer.observe(el);
  });
}

// =============================================
//  COUNTER ANIMATION
// =============================================
function animateCounter(elementId, target) {
  const el = document.getElementById(elementId);
  if (!el) return;

  const start = parseInt(el.textContent) || 0;
  const duration = 600;
  const startTime = performance.now();

  function step(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.round(start + (target - start) * eased);
    el.textContent = current;

    if (progress < 1) {
      requestAnimationFrame(step);
    }
  }

  requestAnimationFrame(step);
}

// =============================================
//  CONFETTI EFFECT
// =============================================
function showConfetti() {
  const container = document.createElement('div');
  container.className = 'confetti-container';
  document.body.appendChild(container);

  const colors = ['#6c5ce7', '#00cec9', '#ff6b6b', '#fdcb6e', '#00b894', '#a29bfe'];

  for (let i = 0; i < 50; i++) {
    const piece = document.createElement('div');
    piece.className = 'confetti-piece';
    piece.style.left = Math.random() * 100 + '%';
    piece.style.background = colors[Math.floor(Math.random() * colors.length)];
    piece.style.width = (Math.random() * 8 + 5) + 'px';
    piece.style.height = (Math.random() * 8 + 5) + 'px';
    piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
    piece.style.animationDelay = Math.random() * 1 + 's';
    piece.style.animationDuration = (Math.random() * 2 + 2) + 's';
    container.appendChild(piece);
    setTimeout(() => piece.classList.add('active'), 10);
  }

  setTimeout(() => container.remove(), 5000);
}

// =============================================
//  MODAL HELPERS
// =============================================
function openModal(overlayId) {
  const overlay = document.getElementById(overlayId);
  if (overlay) {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(overlayId) {
  const overlay = document.getElementById(overlayId);
  if (overlay) {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// =============================================
//  UTILITIES
// =============================================
function capitalize(str) {
  return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

function formatCurrency(amount) {
  return '$' + parseFloat(amount).toFixed(2);
}

function getInitials(name) {
  return name ? name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) : '??';
}

function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}
