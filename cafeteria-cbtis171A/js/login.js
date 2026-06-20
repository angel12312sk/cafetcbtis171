/* ============================================================
   CAFETERÍA CBTis No. 171 — Admin Login JS
   ============================================================ */

async function handleLogin() {
  const email = document.getElementById('email').value.trim();
  const pass  = document.getElementById('password').value;
  const err   = document.getElementById('errorMsg');
  const btn   = document.getElementById('loginBtn');

  if (!email || !pass) {
    err.textContent = 'Por favor completa todos los campos.';
    err.classList.add('show');
    return;
  }

  btn.textContent = 'Verificando...';
  btn.disabled = true;

  try {
    const r = await fetch('php/api_auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password: pass })
    });
    const d = await r.json();

    if (d.ok) {
      sessionStorage.setItem('cbtis_admin_logged', '1');
      sessionStorage.setItem('cbtis_admin_nombre', d.nombre || 'Administrador');
      window.location.href = 'admin-dashboard.html';
    } else {
      err.textContent = 'Credenciales incorrectas. Intenta de nuevo.';
      err.classList.add('show');
      setTimeout(() => err.classList.remove('show'), 3500);
    }
  } catch {
    // Fallback demo (sin BD)
    if (email === 'admin@cbtis171.edu.mx' && pass === 'admin2025') {
      sessionStorage.setItem('cbtis_admin_logged', '1');
      window.location.href = 'admin-dashboard.html';
    } else {
      err.textContent = 'Credenciales incorrectas.';
      err.classList.add('show');
      setTimeout(() => err.classList.remove('show'), 3500);
    }
  }

  btn.textContent = 'Entrar al panel';
  btn.disabled = false;
}

document.addEventListener('keydown', e => { if (e.key === 'Enter') handleLogin(); });
