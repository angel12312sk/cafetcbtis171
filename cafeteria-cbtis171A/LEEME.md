# 🍽️ Cafetería CBTis No. 171 — Guía Completa de Instalación y Configuración

---

## 📁 Estructura del Proyecto

```
cafeteria-cbtis171/
├── index.html               ← Sitio público (menú + info)
├── admin-login.html         ← Login del administrador
├── admin-dashboard.html     ← Panel de control completo
├── css/
│   ├── global.css           ← Estilos globales compartidos
│   ├── public.css           ← Estilos del sitio público
│   └── admin.css            ← Estilos del panel admin
├── js/
│   ├── public.js            ← Lógica del sitio público (carousel, menú)
│   ├── admin.js             ← Lógica del panel de administrador
│   └── login.js             ← Lógica del login
├── php/
│   ├── config.php           ← ⚙️ Configuración de BD y claves (EDITAR)
│   ├── api_auth.php         ← Login admin + registro/login alumnos (JWT)
│   ├── api_menu.php         ← CRUD del menú (público + admin)
│   ├── api_pedidos.php      ← Crear, listar y liberar pedidos
│   ├── api_inventario.php   ← Control de stock del día
│   ├── api_dashboard.php    ← Estadísticas en tiempo real
│   ├── api_reportes.php     ← Reporte de ventas diario
│   └── api_pago.php         ← Integración Stripe (pagos reales)
└── sql/
    └── cafeteria_cbtis171.sql  ← Base de datos completa con datos de ejemplo
```

---

## 🗄️ PASO 1 — Crear la Base de Datos

### Opción A: XAMPP (desarrollo local en tu computadora)

1. Descarga e instala **XAMPP**: https://www.apachefriends.org
2. Inicia los módulos **Apache** y **MySQL** desde el panel de XAMPP
3. Abre tu navegador y entra a: `http://localhost/phpmyadmin`
4. En el panel izquierdo, haz clic en **"Nueva"**
5. Escribe el nombre: `cafeteria_cbtis` → clic en **Crear**
6. Con la BD seleccionada, ve a la pestaña **Importar**
7. Haz clic en **"Elegir archivo"** → selecciona `sql/cafeteria_cbtis171.sql`
8. Scroll al final → clic en **Continuar**
9. ✅ Listo. Ya tienes todas las tablas y datos de ejemplo.

### Opción B: InfinityFree (hosting gratuito en línea — recomendado para producción)

1. Crea cuenta en: https://infinityfree.net
2. Crea un hosting → anota tu **dominio** (ej: `tucafteria.rf.gd`)
3. En el panel de control, busca **"MySQL Databases"**
4. Crea una nueva base de datos:
   - Nombre: `cafeteria_cbtis` (se añadirá un prefijo automático, ej: `epiz_12345_cafeteria_cbtis`)
   - Usuario y contraseña: anótalos
5. Abre **phpMyAdmin** desde el panel
6. Selecciona tu BD → **Importar** → sube `cafeteria_cbtis171.sql` → Continuar

### Opción C: 000webhost (alternativa gratuita)

1. Crea cuenta en: https://www.000webhost.com
2. Ve a **Manage Website** → **Database Manager**
3. Crea una BD y anota: host, nombre, usuario y contraseña
4. Abre phpMyAdmin → Importa `cafeteria_cbtis171.sql`

---

## ⚙️ PASO 2 — Configurar el archivo `php/config.php`

Abre `php/config.php` y edita estas líneas con tus datos reales:

```php
// Para XAMPP local:
define('DB_HOST', 'localhost');
define('DB_NAME', 'cafeteria_cbtis');
define('DB_USER', 'root');
define('DB_PASS', '');           // Vacío en XAMPP

// Para InfinityFree / 000webhost:
define('DB_HOST', 'sql200.infinityfree.com');   // El host que te dan
define('DB_NAME', 'epiz_12345_cafeteria_cbtis'); // Con el prefijo
define('DB_USER', 'epiz_12345_usuario');
define('DB_PASS', 'TU_CONTRASEÑA');
```

---

## 🌐 PASO 3 — Subir el Proyecto al Hosting

### Con XAMPP (local):
1. Copia la carpeta `cafeteria-cbtis171` a:
   - Windows: `C:\xampp\htdocs\cafeteria-cbtis171\`
   - Mac/Linux: `/opt/lampp/htdocs/cafeteria-cbtis171/`
2. Accede desde: `http://localhost/cafeteria-cbtis171/`

### Con InfinityFree / 000webhost:
1. En el panel de control → **File Manager** o conéctate por FTP
2. Sube todos los archivos a la carpeta `htdocs/` o `public_html/`
3. La URL del sitio quedará como: `https://tudominio.rf.gd/`

**FTP con FileZilla:**
- Host: `ftp.tudominio.rf.gd`
- Usuario y contraseña: los del hosting
- Puerto: 21

---

## 🔐 PASO 4 — Cambiar la Contraseña del Admin

La contraseña por defecto es `admin2025`. Para cambiarla:

1. En phpMyAdmin, ejecuta esta consulta en la pestaña **SQL**:
```sql
UPDATE admins
SET password_hash = '$2y$10$NUEVO_HASH_AQUI'
WHERE email = 'admin@cbtis171.edu.mx';
```

2. Para generar el hash de tu nueva contraseña, crea un archivo temporal `generar_hash.php`:
```php
<?php
echo password_hash('TU_NUEVA_CONTRASEÑA', PASSWORD_DEFAULT);
```
Ábrelo en el navegador, copia el hash y úsalo en la consulta SQL. Borra el archivo después.

---

## 💳 PASO 5 — Configurar Stripe (Pagos Reales)

1. Crea cuenta en: https://stripe.com/mx
2. Ve a **Developers > API Keys**
3. Copia **Publishable key** y **Secret key**
4. En `php/config.php` reemplaza:
```php
define('STRIPE_SECRET_KEY', 'sk_live_TU_CLAVE_SECRETA');
define('STRIPE_PUBLIC_KEY',  'pk_live_TU_CLAVE_PUBLICA');
```
5. Instala la librería de Stripe. Dos opciones:

**Opción A — Composer (recomendado):**
```bash
composer require stripe/stripe-php
```
Luego descomenta en `api_pago.php`:
```php
require_once '../vendor/autoload.php';
```

**Opción B — Sin Composer:**
- Descarga: https://github.com/stripe/stripe-php/releases
- Extrae la carpeta `stripe-php` en la raíz del proyecto
- Descomenta en `api_pago.php`:
```php
require_once '../stripe-php/init.php';
```

6. En Stripe, configura el **Webhook**:
   - URL: `https://tudominio.com/php/api_pago.php?action=webhook`
   - Evento: `checkout.session.completed`
   - Copia el **Webhook Secret** y agrégalo en `config.php`

---

## 📱 PASO 6 — Crear la App Móvil en Thunkable X

### 6.1 Crear la cuenta y el proyecto

1. Ve a: https://x.thunkable.com
2. Crea tu cuenta con Gmail
3. Haz clic en **"+ New Project"** → elige **"Blank Project"**
4. Nombre del proyecto: `Cafeteria CBTis 171`
5. Plataforma: **Both (iOS & Android)**

---

### 6.2 Pantallas que debes crear

En el panel izquierdo, crea estas pantallas (clic en "+" junto a Screens):

| Pantalla | Descripción |
|---|---|
| `Screen_Login` | Registro/Login del alumno |
| `Screen_Menu` | Ver el menú del día |
| `Screen_Carrito` | Resumen del pedido antes de pagar |
| `Screen_Pago` | Redirige a Stripe Checkout |
| `Screen_Ticket` | Muestra el ticket y estatus |
| `Screen_Historial` | Pedidos anteriores del alumno |

---

### 6.3 Configurar cada pantalla

#### 🔑 Screen_Login
**Componentes a agregar:**
- `TextInput` → ID: `input_nombre`, placeholder: "Tu nombre completo"
- `TextInput` → ID: `input_correo`, placeholder: "correo@cbtis171.edu.mx"
- `TextInput` → ID: `input_password`, placeholder: "Contraseña", isPassword: true
- `Dropdown/Picker` → ID: `picker_grado`, opciones: 1°, 2°, 3°
- `Dropdown/Picker` → ID: `picker_grupo`, opciones: A, B, C, D, E
- `Button` → texto: "Registrarse"
- `Button` → texto: "Ya tengo cuenta → Iniciar sesión"
- `Label` → ID: `label_error`, color rojo, visible: false

**Bloques de código (en la pestaña Blocks):**

Para el botón **Registrarse**:
```
when Button_Registrar.Click
  set url = "https://TUDOMINIO.com/php/api_auth.php"
  set body = {
    "action":   "register_alumno",
    "nombre":   input_nombre.Text,
    "correo":   input_correo.Text,
    "password": input_password.Text,
    "grado":    picker_grado.Selection,
    "grupo":    picker_grupo.Selection
  }
  Web API → POST → url, body (JSON)
    if response.ok = true
      App Variables.token   = response.token
      App Variables.alumno  = response.alumno
      Navigate to Screen_Menu
    else
      label_error.Text    = response.error
      label_error.Visible = true
```

Para el botón **Iniciar sesión**:
```
when Button_Login.Click
  set body = {
    "action":   "login_alumno",
    "correo":   input_correo.Text,
    "password": input_password.Text
  }
  Web API → POST → url, body
    if response.ok = true
      App Variables.token  = response.token
      App Variables.alumno = response.alumno
      Navigate to Screen_Menu
```

---

#### 🍽️ Screen_Menu
**Componentes:**
- `Label` → texto: "Menú del día" (título)
- `Label` → ID: `label_bienvenida` (mostrará "Hola, [nombre]")
- `List Viewer` o `Data Viewer List` → ID: `lista_menu`
- `Button` → texto: "Ver mi carrito 🛒"

**Bloques:**
```
when Screen_Menu.Opens
  label_bienvenida.Text = "Hola, " + App Variables.alumno.nombre

  Web API → GET → "https://TUDOMINIO.com/php/api_menu.php"
    for each item in response.items
      agregar a lista_menu:
        título:    item.nombre
        subtítulo: "$" + item.precio + " · Stock: " + item.stock
        imagen:    item.imagen
        habilitado: (item.stock > 0)

when lista_menu.ItemClick
  if item.stock > 0
    App Variables.carrito.add({
      menu_id:  item.id,
      nombre:   item.nombre,
      precio:   item.precio,
      cantidad: 1
    })
    mostrar Alert: item.nombre + " agregado al carrito ✓"
  else
    mostrar Alert: "⚠️ Lo sentimos, " + item.nombre + " está agotado por hoy."
```

---

#### 🛒 Screen_Carrito
**Componentes:**
- `Data Viewer List` → ID: `lista_carrito`
- `Label` → ID: `label_total` (Total: $XX)
- `Button` → texto: "Pagar ahora →"
- `Button` → texto: "← Regresar al menú"

**Bloques:**
```
when Screen_Carrito.Opens
  mostrar App Variables.carrito en lista_carrito
  calcular total = suma de (item.precio × item.cantidad)
  label_total.Text = "Total: $" + total + " MXN"

when Button_Pagar.Click
  set headers = { "Authorization": "Bearer " + App Variables.token }
  set body = {
    "action": "create",
    "items":  App Variables.carrito
  }
  Web API → POST → "https://TUDOMINIO.com/php/api_pedidos.php"
    headers: headers, body: body
    if response.ok = true
      App Variables.pedido_id = response.pedido_id
      App Variables.total     = response.total
      Navigate to Screen_Pago
    else
      mostrar Alert: response.error
```

---

#### 💳 Screen_Pago
**Componentes:**
- `Label` → "Procesando tu pago..."
- `WebViewer` → ID: `web_stripe` (para abrir Stripe Checkout)
- `Button` → "Ya pagué, ver mi ticket"

**Bloques:**
```
when Screen_Pago.Opens
  set headers = { "Authorization": "Bearer " + App Variables.token }
  set body = {
    "action":     "create_session",
    "pedido_id":  App Variables.pedido_id,
    "items":      App Variables.carrito
  }
  Web API → POST → "https://TUDOMINIO.com/php/api_pago.php"
    if response.ok = true
      web_stripe.URL = response.checkout_url
      web_stripe.Visible = true

when Button_VerTicket.Click
  Navigate to Screen_Ticket
```

---

#### 🎫 Screen_Ticket
**Componentes:**
- `Label` → ID: `label_folio` (Folio #XXX)
- `Label` → ID: `label_items`
- `Label` → ID: `label_total_ticket`
- `Label` → ID: `label_estatus` (Pendiente / Pagado / Entregado)
- `Button` → "Actualizar estatus"
- `Button` → "Ver historial"

**Bloques:**
```
when Screen_Ticket.Opens
  label_folio.Text  = "Folio #" + App Variables.pedido_id
  
  Web API → POST → "https://TUDOMINIO.com/php/api_pedidos.php"
    body: { "action": "mis_pedidos" }, headers: (con token)
    mostrar el pedido más reciente
    label_estatus.Text  = pedido.estatus
    label_items.Text    = pedido.items
    label_total_ticket.Text = "$" + pedido.total + " MXN"
    
    if estatus = "pendiente" → color rojo
    if estatus = "pagado"    → color amarillo
    if estatus = "entregado" → color verde
```

---

#### 📋 Screen_Historial
**Componentes:**
- `Data Viewer List` → ID: `lista_historial`

**Bloques:**
```
when Screen_Historial.Opens
  Web API → POST → "https://TUDOMINIO.com/php/api_pedidos.php"
    body: { "action": "mis_pedidos" }, headers: (con token)
    mostrar todos los pedidos en lista_historial
    título:    "#" + pedido.id + " · " + pedido.estatus
    subtítulo: pedido.items + " · $" + pedido.total
```

---

### 6.4 Variables de la App (App Variables)

En Thunkable, ve a **App Variables** y crea estas variables:

| Variable | Tipo | Valor inicial |
|---|---|---|
| `token` | Text | "" |
| `alumno` | Object | {} |
| `carrito` | List | [] |
| `pedido_id` | Number | 0 |
| `total` | Number | 0 |

---

### 6.5 Configurar el Theme (colores)

En Thunkable → **Theme**:
- Primary Color: `#F5A623` (dorado)
- Background: `#111111` (negro)
- Text: `#FFFFFF`
- Accent: `#D4891A`

---

### 6.6 Publicar la App

**Para Android (.apk):**
1. En Thunkable → clic en el ícono de descarga ⬇️
2. Elige **"Download Android App (.apk)"**
3. Espera la compilación (5-10 min)
4. Descarga el .apk y compártelo con los alumnos por WhatsApp/Drive
5. Los alumnos deben habilitar "Fuentes desconocidas" en Android para instalarlo

**Para Google Play Store / App Store:**
- Requiere cuenta de desarrollador ($25 USD Google Play, $99/año Apple)
- En Thunkable → Publish → Google Play / App Store

---

## 🔗 URLs de la API (para configurar en Thunkable)

Reemplaza `TUDOMINIO.com` con tu dominio real:

```
Base URL:        https://TUDOMINIO.com/php/
Login/Registro:  https://TUDOMINIO.com/php/api_auth.php
Menú:            https://TUDOMINIO.com/php/api_menu.php
Pedidos:         https://TUDOMINIO.com/php/api_pedidos.php
Pago Stripe:     https://TUDOMINIO.com/php/api_pago.php
```

---

## 🔄 Flujo completo del sistema

```
ALUMNO (app Thunkable)          SERVIDOR PHP + MySQL         ADMIN (panel web)
─────────────────────           ────────────────────         ─────────────────
1. Se registra/loguea     →     api_auth.php → JWT token
2. Ve el menú del día     ←     api_menu.php (GET)
3. Agrega al carrito
4. Crea pedido            →     api_pedidos.php → stock--, pedido INSERT
5. Paga con Stripe        →     api_pago.php → Stripe Session
6. Stripe confirma pago   →     Webhook → estatus = 'pagado'
7. Ve su ticket y estatus ←     api_pedidos.php (mis_pedidos)

                                                             8. Ve pedidos en vivo
                                                             9. Libera el pedido → 'entregado'
                                                            10. Genera reporte del día → guarda en BD
```

---

## ✅ Checklist de configuración

- [ ] XAMPP / Hosting configurado y funcionando
- [ ] Base de datos importada desde `sql/cafeteria_cbtis171.sql`
- [ ] `php/config.php` editado con credenciales reales
- [ ] Proyecto subido al servidor
- [ ] Login admin funciona: `admin@cbtis171.edu.mx` / `admin2025`
- [ ] Contraseña del admin cambiada
- [ ] Cuenta de Stripe creada y llaves configuradas
- [ ] Librería Stripe instalada (`vendor/` o `stripe-php/`)
- [ ] App Thunkable creada con las 6 pantallas
- [ ] URL de la API configurada en Thunkable
- [ ] App variables creadas en Thunkable
- [ ] APK generado y probado en un dispositivo Android

---

## 🆘 Solución de problemas comunes

| Problema | Solución |
|---|---|
| "Error de conexión a la base de datos" | Verifica DB_HOST, DB_NAME, DB_USER, DB_PASS en config.php |
| CORS error en Thunkable | El header `Access-Control-Allow-Origin: *` ya está en config.php. Verifica que el servidor lo incluya |
| Stripe no procesa pagos | Verifica que usas llaves LIVE (no TEST) para producción |
| La app no carga el menú | Verifica que la URL de la API en Thunkable termina en `/php/api_menu.php` |
| Stock no se descuenta | Verifica que el JWT se envía en el header `Authorization: Bearer TOKEN` |
| phpMyAdmin no carga | En XAMPP verifica que MySQL esté activo (luz verde) |
