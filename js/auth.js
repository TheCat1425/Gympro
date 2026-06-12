// =============================================
//  AUTH — Login & Register
// =============================================

document.addEventListener('DOMContentLoaded', () => {
  const loginForm = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');

  if (loginForm) initLoginForm(loginForm);
  if (registerForm) initRegisterForm(registerForm);

  // If already logged in, redirect
  checkAuth().then(user => {
    if (user) {
      window.location.href = 'index.html';
    }
  });
});

function initLoginForm(form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);

    const email = form.querySelector('#loginEmail').value.trim();
    const password = form.querySelector('#loginPassword').value;

    // Client validation
    if (!email) return showFieldError('loginEmail', 'Email is required.');
    if (!password) return showFieldError('loginPassword', 'Password is required.');

    const btn = form.querySelector('button[type="submit"]');
    setButtonLoading(btn, true);

    try {
      const formData = new FormData();
      formData.append('email', email);
      formData.append('password', password);

      const res = await fetch('api/auth/login.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        showToast('success', 'Welcome back, ' + data.user.full_name + '! 🎉');
        setTimeout(() => {
          window.location.href = data.user.role === 'admin' ? 'admin.html' : 'index.html';
        }, 1000);
      } else {
        showToast('error', data.message);
      }
    } catch (err) {
      showToast('error', 'Network error. Please try again.');
    } finally {
      setButtonLoading(btn, false);
    }
  });
}

function initRegisterForm(form) {
  // Password strength
  const passwordInput = form.querySelector('#regPassword');
  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      updatePasswordStrength(passwordInput.value);
    });
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);

    const fullName = form.querySelector('#regName').value.trim();
    const email = form.querySelector('#regEmail').value.trim();
    const phone = form.querySelector('#regPhone')?.value.trim() || '';
    const password = form.querySelector('#regPassword').value;
    const confirmPassword = form.querySelector('#regConfirmPassword').value;

    // Client validation
    if (!fullName || fullName.length < 2) return showFieldError('regName', 'Full name is required (min 2 chars).');
    if (!email || !email.includes('@')) return showFieldError('regEmail', 'Valid email is required.');
    if (password.length < 6) return showFieldError('regPassword', 'Password must be at least 6 characters.');
    if (password !== confirmPassword) return showFieldError('regConfirmPassword', 'Passwords do not match.');

    const btn = form.querySelector('button[type="submit"]');
    setButtonLoading(btn, true);

    try {
      const formData = new FormData();
      formData.append('full_name', fullName);
      formData.append('email', email);
      formData.append('phone', phone);
      formData.append('password', password);
      formData.append('confirm_password', confirmPassword);

      const res = await fetch('api/auth/register.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) {
        // Show user ID
        const userIdDisplay = document.getElementById('userIdDisplay');
        if (userIdDisplay) {
          userIdDisplay.style.display = 'block';
          userIdDisplay.querySelector('.user-id-display__value').textContent = 'GYM-' + String(data.user.user_id).padStart(4, '0');
        }
        showToast('success', 'Registration successful! Welcome to GymPro! 🎉');
        showConfetti();
        setTimeout(() => {
          window.location.href = 'index.html';
        }, 3000);
      } else {
        showToast('error', data.message);
      }
    } catch (err) {
      showToast('error', 'Network error. Please try again.');
    } finally {
      setButtonLoading(btn, false);
    }
  });
}

function updatePasswordStrength(password) {
  const bar = document.querySelector('.password-strength__bar');
  if (!bar) return;

  bar.className = 'password-strength__bar';
  if (password.length === 0) {
    bar.style.width = '0';
  } else if (password.length < 6) {
    bar.classList.add('password-strength__bar--weak');
  } else if (password.length < 10 || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
    bar.classList.add('password-strength__bar--medium');
  } else {
    bar.classList.add('password-strength__bar--strong');
  }
}

function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  if (field) {
    const error = document.createElement('div');
    error.className = 'form-error';
    error.textContent = message;
    field.parentElement.appendChild(error);
    field.style.borderColor = 'var(--danger)';
    field.focus();
  }
}

function clearErrors(form) {
  form.querySelectorAll('.form-error').forEach(e => e.remove());
  form.querySelectorAll('.form-input').forEach(i => i.style.borderColor = '');
}

function setButtonLoading(btn, loading) {
  if (!btn) return;
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span> Please wait...';
    btn.disabled = true;
  } else {
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    btn.disabled = false;
  }
}
