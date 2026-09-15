// register.js - Role selection + dynamic form logic

const roleNames = {
  student: 'Student',
  industry: 'Industry',
  academician: 'Academician',
  institution: 'Institution'
};

let selectedRole = '';

function selectRole(el) {
  // highlight card
  document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');

  selectedRole = el.dataset.role;
  document.getElementById('selectedRole').value = selectedRole;
  document.getElementById('roleLabel').textContent = roleNames[selectedRole];

  // show form, hide role step
  document.getElementById('roleStep').classList.add('d-none');
  document.getElementById('registerForm').classList.remove('d-none');

  // show the matching role fields
  showRoleFields(selectedRole);
}

function showRoleFields(role) {
  document.querySelectorAll('.role-fields').forEach(f => f.classList.add('d-none'));
  const target = document.getElementById('fields-' + role);
  if (target) target.classList.remove('d-none');
}

function backToRole() {
  document.getElementById('registerForm').classList.add('d-none');
  document.getElementById('roleStep').classList.remove('d-none');
  document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
}

// ----- Client-side validation -----
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('registerForm');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const pw = document.getElementById('password').value;
    const pw2 = document.getElementById('confirm_password').value;

    if (name.length < 3) {
      e.preventDefault();
      alert('Please enter your full name (min 3 characters).');
      document.getElementById('name').focus();
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      e.preventDefault();
      alert('Please enter a valid email address.');
      document.getElementById('email').focus();
      return;
    }
    if (pw.length < 6) {
      e.preventDefault();
      alert('Password must be at least 6 characters.');
      document.getElementById('password').focus();
      return;
    }
    if (pw !== pw2) {
      e.preventDefault();
      alert('Passwords do not match.');
      document.getElementById('confirm_password').focus();
      return;
    }

    // Validate role-specific required fields (client side)
    if (selectedRole === 'industry') {
      const comp = form.querySelector('[name="company_name"]');
      if (!comp.value.trim()) {
        e.preventDefault();
        alert('Please enter your company name.');
        comp.focus();
        return;
      }
    }
  });
});
