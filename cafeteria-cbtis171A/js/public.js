/* ============================================================
   CAFETERÍA CBTis No. 171 — Public JS (index.html)
   ============================================================ */

/* ── CAROUSEL ── */
let currentSlide = 0;
const totalSlides = 4;
let autoTimer;

function goSlide(n) {
  document.getElementById('slide-' + currentSlide).classList.remove('active');
  document.querySelectorAll('.dot')[currentSlide].classList.remove('active');
  currentSlide = n;
  document.getElementById('slide-' + currentSlide).classList.add('active');
  document.querySelectorAll('.dot')[currentSlide].classList.add('active');
  resetTimer();
}
function nextSlide() { goSlide((currentSlide + 1) % totalSlides); }
function resetTimer() { clearInterval(autoTimer); autoTimer = setInterval(nextSlide, 5000); }
autoTimer = setInterval(nextSlide, 5000);

/* ── NAVBAR SCROLL ── */
window.addEventListener('scroll', () => {
  document.getElementById('navbar').classList.toggle('scrolled', window.scrollY > 60);
});

/* ── SMOOTH SCROLL ── */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    const t = document.querySelector(a.getAttribute('href'));
    if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

/* ── MENU: fetch desde PHP API ── */
let allItems = [];
let activeFilter = 'all';

async function loadMenu() {
  try {
    const res = await fetch('php/api_menu.php');
    const data = await res.json();
    if (data.ok) {
      allItems = data.items;
      renderMenu();
    }
  } catch (e) {
    // Fallback a datos demo si no hay conexión
    allItems = [
      { id:1, nombre:'Burrito de Frijoles', categoria:'comida', precio:25, descripcion:'Relleno de frijoles refritos, queso y crema.', stock:85, stock_max:100, imagen:'https://images.unsplash.com/photo-1599974579688-8dbdd335c77f?w=600&q=80' },
      { id:2, nombre:'Torta de Milanesa',   categoria:'comida', precio:35, descripcion:'Pan telera con milanesa empanizada.',           stock:32, stock_max:50,  imagen:'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&q=80' },
      { id:3, nombre:'Quesadillas (2 pzas)',categoria:'comida', precio:20, descripcion:'Tortilla de maíz con queso Oaxaca derretido.',  stock:0,  stock_max:60,  imagen:'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=600&q=80' },
      { id:4, nombre:'Enchiladas Verdes',   categoria:'comida', precio:30, descripcion:'3 enchiladas bañadas en salsa verde.',          stock:18, stock_max:40,  imagen:'https://images.unsplash.com/photo-1583835323615-e67e7fa63d14?w=600&q=80' },
      { id:5, nombre:'Licuado Natural',     categoria:'bebida', precio:15, descripcion:'Fresa, mango o plátano con leche entera.',      stock:50, stock_max:80,  imagen:'https://images.unsplash.com/photo-1623428187969-5da2dcea5ebf?w=600&q=80' },
      { id:6, nombre:'Agua Fresca',         categoria:'bebida', precio:10, descripcion:'Horchata, jamaica o limón. 300ml.',             stock:120,stock_max:150, imagen:'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=600&q=80' },
      { id:7, nombre:'Huevos a la Mexicana',categoria:'desayuno',precio:22,descripcion:'Revueltos con verduras y tortillas.',           stock:40, stock_max:50,  imagen:'https://images.unsplash.com/photo-1482049016688-2d3e1b311543?w=600&q=80' },
      { id:8, nombre:'Papas con Chile',     categoria:'snack',  precio:12, descripcion:'Papas fritas con chile piquín y limón.',       stock:200,stock_max:200, imagen:'https://images.unsplash.com/photo-1548340748-6d2b7d7da280?w=600&q=80' },
    ];
    renderMenu();
  }
}

function filterMenu(cat, btn) {
  activeFilter = cat;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderMenu();
}

function stockInfo(item) {
  if (item.stock <= 0)                          return { label:'Agotado', badgeCls:'agotado', stockCls:'out'  };
  if (item.stock <= item.stock_max * 0.25)      return { label:'Últimos', badgeCls:'',        stockCls:'low'  };
  return                                               { label:'Disponible',badgeCls:'',       stockCls:''     };
}

function renderMenu() {
  const grid = document.getElementById('menuGrid');
  const list = activeFilter === 'all' ? allItems : allItems.filter(i => i.categoria === activeFilter);

  if (!list.length) {
    grid.innerHTML = '<p style="color:var(--text-muted);text-align:center;grid-column:1/-1;padding:40px 0;">No hay platillos en esta categoría hoy.</p>';
    return;
  }

  grid.innerHTML = list.map(item => {
    const s = stockInfo(item);
    return `
    <article class="menu-card">
      <div class="menu-card-img-wrap">
        <img class="menu-card-img" src="${item.imagen}" alt="${item.nombre}" loading="lazy">
      </div>
      <div class="menu-card-badge ${s.badgeCls}">${s.label}</div>
      <div class="menu-card-body">
        <div class="menu-card-category">${item.categoria}</div>
        <div class="menu-card-name">${item.nombre}</div>
        <div class="menu-card-desc">${item.descripcion}</div>
        <div class="menu-card-footer">
          <div class="menu-card-price">$${item.precio}<span> MXN</span></div>
          <div class="menu-card-stock ${s.stockCls}">
            ${item.stock <= 0 ? 'Sin existencia' : item.stock + ' disponibles'}
          </div>
        </div>
      </div>
    </article>`;
  }).join('');
}

document.addEventListener('DOMContentLoaded', loadMenu);
