/* ============================================================
   CAFETERÍA CBTis No. 171 — Admin Dashboard JS
   ============================================================ */

/* ── AUTH GUARD ── */
if (!sessionStorage.getItem('cbtis_admin_logged')) {
  window.location.href = 'admin-login.html';
}

/* ── DATE ── */
const now = new Date();
const opts = { weekday:'long', year:'numeric', month:'long', day:'numeric' };
document.getElementById('topbarDate').textContent = now.toLocaleDateString('es-MX', opts);
document.getElementById('salesDateLabel').textContent = 'Resumen: ' + now.toLocaleDateString('es-MX', opts);

/* ── PANEL NAVIGATION ── */
function showPanel(id, btn) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + id).classList.add('active');
  if (btn) btn.classList.add('active');
  const titles = {
    dashboard:'Dashboard', orders:'Pedidos en vivo',
    menu:'Gestión de Menú', inventory:'Inventario', sales:'Reporte de Ventas'
  };
  document.getElementById('topbarTitle').textContent = titles[id] || '';
  if (id === 'orders')    loadOrders();
  if (id === 'menu')      loadMenuManage();
  if (id === 'inventory') loadInventory();
  if (id === 'sales')     loadSalesReport();
  if (id === 'dashboard') loadDashboard();
}

/* ── TOAST ── */
function showToast(msg, type = 'ok') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show' + (type === 'error' ? ' error' : '');
  setTimeout(() => t.className = 'toast', 3200);
}

/* ── MODAL ── */
function openModal()  { document.getElementById('menuModal').classList.add('show');    }
function closeModal() { document.getElementById('menuModal').classList.remove('show'); }
document.addEventListener('click', e => {
  if (e.target.id === 'menuModal') closeModal();
});

/* ── BADGE HTML ── */
function badgeHtml(s) {
  const map    = { pagado:'badge-paid', pendiente:'badge-pending', entregado:'badge-delivered' };
  const labels = { pagado:'Pagado',     pendiente:'Pendiente',     entregado:'Entregado' };
  return `<span class="badge ${map[s] || ''}">${labels[s] || s}</span>`;
}

/* ══════════════════════════════════════════
   DASHBOARD
══════════════════════════════════════════ */
async function loadDashboard() {
  try {
    const r = await fetch('php/api_dashboard.php');
    const d = await r.json();
    if (!d.ok) throw new Error();
    document.getElementById('dash-ventas').textContent    = '$' + d.ventas_total;
    document.getElementById('dash-pedidos').textContent   = d.total_pedidos;
    document.getElementById('dash-pendientes').textContent= d.pendientes + ' pendientes';
    document.getElementById('dash-estrella').textContent  = d.platillo_estrella;
    document.getElementById('dash-estrella-qty').textContent = d.estrella_qty + ' vendidos hoy';
    document.getElementById('dash-alumnos').textContent   = d.alumnos_unicos;
    renderDashOrders(d.ultimos_pedidos);
    updatePendingBadge(d.pendientes);
  } catch {
    renderDashOrders(demoOrders.slice(0, 5));
  }
}

function renderDashOrders(list) {
  document.getElementById('dashOrdersBody').innerHTML = (list || []).map(o => `
    <tr>
      <td><div class="student-name">${o.alumno}</div><div class="student-meta">${o.grado} ${o.grupo}</div></td>
      <td><div class="order-items">${o.items}</div></td>
      <td><strong>$${o.total}</strong></td>
      <td>${badgeHtml(o.estatus)}</td>
      <td>${o.estatus !== 'entregado'
        ? `<button class="btn-gold btn-sm" onclick="releaseOrder(${o.id})">Liberar</button>`
        : '—'}</td>
    </tr>`).join('');
}

/* ══════════════════════════════════════════
   ORDERS
══════════════════════════════════════════ */
async function loadOrders() {
  const filter = document.getElementById('orderFilter').value;
  try {
    const r = await fetch(`php/api_pedidos.php?estatus=${filter}`);
    const d = await r.json();
    if (!d.ok) throw new Error();
    renderOrders(d.pedidos);
    updatePendingBadge(d.pendientes_total);
  } catch {
    const filtered = filter === 'all' ? demoOrders : demoOrders.filter(o => o.estatus === filter);
    renderOrders(filtered);
  }
}

function renderOrders(list) {
  document.getElementById('ordersBody').innerHTML = (list || []).map(o => `
    <tr>
      <td><div class="student-name">#${o.id} · ${o.alumno}</div></td>
      <td><div class="student-meta">${o.correo}</div></td>
      <td>${o.grado} ${o.grupo}</td>
      <td><div class="order-items">${o.items}</div></td>
      <td><strong>$${o.total}</strong></td>
      <td>${badgeHtml(o.estatus)}</td>
      <td>${o.estatus !== 'entregado'
        ? `<button class="btn-gold btn-sm" onclick="releaseOrder(${o.id})">Liberar</button>`
        : '<span style="font-size:11px;color:var(--text-sub)">—</span>'}</td>
    </tr>`).join('');
}

async function releaseOrder(id) {
  try {
    const r = await fetch('php/api_pedidos.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'release', id })
    });
    const d = await r.json();
    if (d.ok) { showToast('Pedido #' + id + ' marcado como entregado ✓'); loadOrders(); loadDashboard(); }
  } catch {
    showToast('Pedido #' + id + ' entregado (modo demo) ✓');
  }
}

function updatePendingBadge(n) {
  const el = document.getElementById('pendingCount');
  if (el) el.textContent = n;
}

/* ══════════════════════════════════════════
   MENU MANAGEMENT
══════════════════════════════════════════ */
let editItemId = null;

async function loadMenuManage() {
  try {
    const r = await fetch('php/api_menu.php');
    const d = await r.json();
    if (!d.ok) throw new Error();
    renderMenuManage(d.items);
  } catch {
    renderMenuManage(demoMenuItems);
  }
}

function renderMenuManage(items) {
  document.getElementById('menuManageGrid').innerHTML = (items || []).map(item => `
    <div class="mmc">
      <img class="mmc-img" src="${item.imagen}" alt="${item.nombre}" loading="lazy">
      <div class="mmc-body">
        <div class="mmc-name">${item.nombre}</div>
        <div class="mmc-meta">${item.categoria} · $${item.precio} MXN · Stock: ${item.stock}</div>
        <div class="mmc-actions">
          <button class="btn-outline btn-sm" onclick="editMenuItem(${item.id})">✏️ Editar</button>
          <button class="btn-danger btn-sm" onclick="deleteMenuItem(${item.id})">🗑</button>
        </div>
      </div>
    </div>`).join('');
}

function openAddMenu() {
  editItemId = null;
  document.getElementById('modalTitle').textContent = 'Agregar platillo';
  ['mNombre','mDesc','mPrecio','mStock','mImagen'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('mCategoria').value = 'comida';
  openModal();
}

async function editMenuItem(id) {
  editItemId = id;
  try {
    const r = await fetch('php/api_menu.php?id=' + id);
    const d = await r.json();
    const item = d.item || demoMenuItems.find(i => i.id === id);
    if (!item) return;
    document.getElementById('modalTitle').textContent    = 'Editar platillo';
    document.getElementById('mNombre').value             = item.nombre;
    document.getElementById('mCategoria').value          = item.categoria;
    document.getElementById('mDesc').value               = item.descripcion;
    document.getElementById('mPrecio').value             = item.precio;
    document.getElementById('mStock').value              = item.stock;
    document.getElementById('mImagen').value             = item.imagen;
    openModal();
  } catch { openModal(); }
}

async function saveMenuItem() {
  const nombre = document.getElementById('mNombre').value.trim();
  if (!nombre) { showToast('El nombre es obligatorio', 'error'); return; }
  const payload = {
    action   : editItemId ? 'update' : 'create',
    id       : editItemId,
    nombre,
    categoria: document.getElementById('mCategoria').value,
    descripcion: document.getElementById('mDesc').value,
    precio   : parseFloat(document.getElementById('mPrecio').value) || 0,
    stock    : parseInt(document.getElementById('mStock').value) || 0,
    imagen   : document.getElementById('mImagen').value || 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=400&q=80'
  };
  try {
    const r = await fetch('php/api_menu.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const d = await r.json();
    if (d.ok) {
      showToast(editItemId ? 'Platillo actualizado ✓' : 'Platillo agregado ✓');
      closeModal(); loadMenuManage();
    }
  } catch {
    showToast(editItemId ? 'Actualizado (demo) ✓' : 'Agregado (demo) ✓');
    closeModal();
  }
}

async function deleteMenuItem(id) {
  if (!confirm('¿Eliminar este platillo del menú?')) return;
  try {
    const r = await fetch('php/api_menu.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'delete', id })
    });
    const d = await r.json();
    if (d.ok) { showToast('Platillo eliminado ✓'); loadMenuManage(); }
  } catch { showToast('Eliminado (demo) ✓'); }
}

/* ══════════════════════════════════════════
   INVENTORY
══════════════════════════════════════════ */
async function loadInventory() {
  try {
    const r = await fetch('php/api_inventario.php');
    const d = await r.json();
    if (!d.ok) throw new Error();
    renderInventory(d.items);
  } catch {
    renderInventory(demoMenuItems);
  }
}

function renderInventory(items) {
  document.getElementById('invList').innerHTML = (items || []).map(item => {
    const pct = item.stock_max > 0 ? Math.round((item.stock / item.stock_max) * 100) : 0;
    const cls = pct > 50 ? 'good' : pct > 20 ? 'mid' : 'low';
    return `
    <div class="inv-item">
      <div class="inv-header">
        <div>
          <div class="inv-name">${item.nombre}</div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">${item.categoria} · Máx. del día: ${item.stock_max}</div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;">
          <input class="inv-stock-input" type="number" value="${item.stock}" min="0" max="${item.stock_max}"
            onchange="updateStock(${item.id}, this.value)">
          <div class="inv-count">${item.stock}</div>
        </div>
      </div>
      <div class="inv-bar-bg"><div class="inv-bar ${cls}" style="width:${pct}%"></div></div>
      <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text-muted);">
        <span>${pct}% disponible</span>
        <span>${item.stock <= 0 ? '⚠️ Agotado' : item.stock + ' porciones restantes'}</span>
      </div>
    </div>`;
  }).join('');
}

async function updateStock(id, val) {
  try {
    await fetch('php/api_inventario.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'update_stock', id, stock: parseInt(val) || 0 })
    });
  } catch {}
  loadInventory();
}

async function saveInventory() {
  showToast('Inventario guardado ✓');
  loadInventory();
}

/* ══════════════════════════════════════════
   SALES REPORT
══════════════════════════════════════════ */
async function loadSalesReport() {
  try {
    const r = await fetch('php/api_reportes.php?fecha=' + now.toISOString().split('T')[0]);
    const d = await r.json();
    if (!d.ok) throw new Error();
    renderSales(d);
  } catch {
    renderSales(demoSales);
  }
}

function renderSales(d) {
  document.getElementById('salesBody').innerHTML = (d.detalle || []).map(s => `
    <tr>
      <td><strong>${s.nombre}</strong></td>
      <td>${s.categoria}</td>
      <td style="text-align:center;font-family:'Playfair Display',serif;font-size:18px;color:var(--gold);">${s.cantidad}</td>
      <td>$${s.precio_unit}</td>
      <td><strong>$${s.subtotal}</strong></td>
    </tr>`).join('');
  document.getElementById('salesTotalValue').textContent = '$' + (d.total || 0) + ' MXN';
  // Stat cards
  if (d.total)           document.getElementById('sr-total').textContent     = '$' + d.total;
  if (d.completados)     document.getElementById('sr-completados').textContent= d.completados;
  if (d.pendientes_monto)document.getElementById('sr-pendientes').textContent= '$' + d.pendientes_monto;
  if (d.estrella)        document.getElementById('sr-estrella').textContent  = d.estrella;
}

async function saveReport() {
  try {
    const r = await fetch('php/api_reportes.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'save', fecha: now.toISOString().split('T')[0] })
    });
    const d = await r.json();
    if (d.ok) showToast('Reporte guardado en base de datos ✓');
  } catch { showToast('Reporte guardado (demo) ✓'); }
}

function printReport() { window.print(); }

/* ── LOGOUT ── */
function logout() {
  sessionStorage.removeItem('cbtis_admin_logged');
  window.location.href = 'admin-login.html';
}

/* ══════════════════════════════════════════
   DEMO DATA (fallback sin BD)
══════════════════════════════════════════ */
const demoOrders = [
  { id:1, alumno:'Carlos Mendoza',  correo:'carlos.m@cbtis171.edu.mx', grado:'3°', grupo:'A', items:'2× Burrito, 1× Agua Fresca', total:60, estatus:'pagado'    },
  { id:2, alumno:'Laura Pérez',     correo:'laura.p@cbtis171.edu.mx',  grado:'1°', grupo:'C', items:'1× Torta Milanesa',           total:35, estatus:'pendiente' },
  { id:3, alumno:'Diego Ramírez',   correo:'diego.r@cbtis171.edu.mx',  grado:'2°', grupo:'B', items:'3× Quesadillas',              total:60, estatus:'pagado'    },
  { id:4, alumno:'Sofía García',    correo:'sofia.g@cbtis171.edu.mx',  grado:'3°', grupo:'D', items:'1× Enchiladas, 1× Agua',      total:40, estatus:'entregado' },
  { id:5, alumno:'Miguel Torres',   correo:'miguel.t@cbtis171.edu.mx', grado:'1°', grupo:'A', items:'2× Tacos',                    total:36, estatus:'pendiente' },
];

const demoMenuItems = [
  { id:1, nombre:'Burrito de Frijoles', categoria:'comida',   precio:25, descripcion:'Frijoles, queso y crema.',          stock:78, stock_max:100, imagen:'https://images.unsplash.com/photo-1599974579688-8dbdd335c77f?w=400&q=80' },
  { id:2, nombre:'Torta de Milanesa',   categoria:'comida',   precio:35, descripcion:'Pan telera con milanesa.',          stock:32, stock_max:50,  imagen:'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&q=80' },
  { id:3, nombre:'Quesadillas',         categoria:'comida',   precio:20, descripcion:'2 piezas con queso Oaxaca.',        stock:0,  stock_max:60,  imagen:'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=400&q=80' },
  { id:4, nombre:'Enchiladas Verdes',   categoria:'comida',   precio:30, descripcion:'3 enchiladas con salsa verde.',     stock:18, stock_max:40,  imagen:'https://images.unsplash.com/photo-1583835323615-e67e7fa63d14?w=400&q=80' },
  { id:5, nombre:'Licuado Natural',     categoria:'bebida',   precio:15, descripcion:'Fresa, mango o plátano.',           stock:50, stock_max:80,  imagen:'https://images.unsplash.com/photo-1623428187969-5da2dcea5ebf?w=400&q=80' },
  { id:6, nombre:'Agua Fresca',         categoria:'bebida',   precio:10, descripcion:'Horchata, jamaica o limón.',        stock:120,stock_max:150, imagen:'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=400&q=80' },
  { id:7, nombre:'Huevos Mexicana',     categoria:'desayuno', precio:22, descripcion:'Revueltos con verduras.',           stock:40, stock_max:50,  imagen:'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?w=400&q=80' },
  { id:8, nombre:'Papas con Chile',     categoria:'snack',    precio:12, descripcion:'Chile piquín y limón.',             stock:200,stock_max:200, imagen:'https://images.unsplash.com/photo-1548340748-6d2b7d7da280?w=400&q=80' },
];

const demoSales = {
  total:847, completados:26, pendientes_monto:220, estrella:'Burrito',
  detalle:[
    { nombre:'Burrito de Frijoles', categoria:'Comida',   cantidad:22, precio_unit:25, subtotal:550 },
    { nombre:'Torta de Milanesa',   categoria:'Comida',   cantidad:8,  precio_unit:35, subtotal:280 },
    { nombre:'Enchiladas Verdes',   categoria:'Comida',   cantidad:6,  precio_unit:30, subtotal:180 },
    { nombre:'Agua Fresca',         categoria:'Bebida',   cantidad:18, precio_unit:10, subtotal:180 },
    { nombre:'Licuado Natural',     categoria:'Bebida',   cantidad:12, precio_unit:15, subtotal:180 },
    { nombre:'Huevos Mexicana',     categoria:'Desayuno', cantidad:5,  precio_unit:22, subtotal:110 },
    { nombre:'Papas con Chile',     categoria:'Snack',    cantidad:15, precio_unit:12, subtotal:180 },
  ]
};

/* ── INIT ── */
document.addEventListener('DOMContentLoaded', () => { loadDashboard(); });
