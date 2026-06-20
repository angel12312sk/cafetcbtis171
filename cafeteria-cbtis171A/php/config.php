<?php
/* ============================================================
   CAFETERÍA CBTis No. 171 — Configuración de Base de Datos
   Archivo: php/config.php
   ============================================================
   INSTRUCCIONES:
   1. Crea la base de datos en phpMyAdmin (ver cafeteria_cbtis171.sql)
   2. Cambia DB_USER y DB_PASS con tus credenciales reales
   3. Para InfinityFree/000webhost: usa los datos del panel de control
   ============================================================ */

define('DB_HOST', 'bxbltrcg8lqe1pw8xyqj-mysql.services.clever-cloud.com');        // Casi siempre "localhost"
define('DB_NAME', 'bxbltrcg8lqe1pw8xyqj');  // Nombre exacto de tu BD
define('DB_USER', 'utpablneh4lfsh9p');             // Tu usuario MySQL
define('DB_PASS', 'sKWYWyqGKHIK4Wx4KXxP');                 // Tu contraseña MySQL (vacía en XAMPP local)
define('DB_CHARSET', 'utf8mb4');

/* ── JWT Secret (para tokens de app móvil) ── */
define('JWT_SECRET', 'cbtis171_secret_key_2025_cafeteria');

/* ── Stripe (para pagos reales) ── */
define('STRIPE_SECRET_KEY', 'sk_test_XXXXXXXXXXXXXXXXXXXXXXXXX'); // Cambia por tu key real
define('STRIPE_PUBLIC_KEY', 'pk_test_XXXXXXXXXXXXXXXXXXXXXXXXX');

/* ── CORS para app móvil Thunkable ── */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

/* ── Conexión PDO ── */
function getDB(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $opts = [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
      $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    } catch (PDOException $e) {
      http_response_code(500);
      echo json_encode(['ok' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
      exit();
    }
  }
  return $pdo;
}

/* ── Respuesta JSON ── */
function respond(bool $ok, array $data = [], int $code = 200): void {
  http_response_code($code);
  echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE);
  exit();
}

/* ── JWT helpers (liviano, sin librería) ── */
function jwtCreate(array $payload): string {
  $header  = base64url_encode(json_encode(['alg'=>'HS256','typ'=>'JWT']));
  $payload = base64url_encode(json_encode($payload));
  $sig     = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
  return "$header.$payload.$sig";
}
function jwtVerify(string $token): ?array {
  $parts = explode('.', $token);
  if (count($parts) !== 3) return null;
  [$h, $p, $s] = $parts;
  $expected = base64url_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
  if (!hash_equals($expected, $s)) return null;
  $payload = json_decode(base64url_decode($p), true);
  if (isset($payload['exp']) && $payload['exp'] < time()) return null;
  return $payload;
}
function base64url_encode(string $data): string { return rtrim(strtr(base64_encode($data),'+/','-_'),'='); }
function base64url_decode(string $data): string { return base64_decode(strtr($data,'-_','+/').'==='); }

/* ── Autenticar token desde header ── */
function requireAuth(): array {
  $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
  if (!str_starts_with($auth, 'Bearer ')) respond(false, ['error'=>'No autorizado'], 401);
  $token = substr($auth, 7);
  $payload = jwtVerify($token);
  if (!$payload) respond(false, ['error'=>'Token inválido o expirado'], 401);
  return $payload;
}
