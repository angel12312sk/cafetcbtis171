<?php
/* ============================================================
   api_pago.php — Integración Stripe (crear sesión de pago)
   ============================================================
   CONFIGURACIÓN:
   1. Crea cuenta en https://stripe.com
   2. Ve a Developers > API Keys y copia tus llaves
   3. Pega STRIPE_SECRET_KEY y STRIPE_PUBLIC_KEY en config.php
   4. Instala la librería: composer require stripe/stripe-php
      O descarga el archivo único de https://github.com/stripe/stripe-php/releases
   ============================================================ */
require_once 'config.php';
// require_once '../vendor/autoload.php'; // Descomentar con Composer

// Si no tienes Composer, puedes usar la versión "sin dependencias" de Stripe:
// require_once '../stripe-php/init.php';

$method = $_SERVER['REQUEST_METHOD'];
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method !== 'POST') respond(false, ['error' => 'Método no permitido'], 405);

$action = $body['action'] ?? '';

// ── CREAR SESIÓN DE PAGO ─────────────────────────────────────
if ($action === 'create_session') {
  $payload   = requireAuth();  // JWT del alumno
  $pedido_id = $body['pedido_id'] ?? null;
  $items     = $body['items']     ?? [];  // [{nombre, precio, cantidad}]

  if (!$pedido_id || empty($items))
    respond(false, ['error' => 'Datos incompletos'], 400);

  // Construir line_items para Stripe
  $line_items = array_map(fn($i) => [
    'price_data' => [
      'currency'     => 'mxn',
      'product_data' => ['name' => $i['nombre']],
      'unit_amount'  => (int)($i['precio'] * 100),  // centavos
    ],
    'quantity' => (int)$i['cantidad'],
  ], $items);

  // ── MODO REAL (descomentar cuando tengas Stripe instalado) ──
  /*
  \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
  $session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items'           => $line_items,
    'mode'                 => 'payment',
    'success_url'          => 'https://TU_DOMINIO/pago_exitoso.html?pedido=' . $pedido_id,
    'cancel_url'           => 'https://TU_DOMINIO/pago_cancelado.html',
    'metadata'             => ['pedido_id' => $pedido_id, 'alumno_id' => $payload['sub']],
  ]);
  respond(true, ['checkout_url' => $session->url, 'session_id' => $session->id]);
  */

  // ── MODO DEMO (sin Stripe instalado) ──
  respond(true, [
    'checkout_url' => 'https://checkout.stripe.com/demo',
    'session_id'   => 'demo_' . $pedido_id,
    'message'      => 'Modo demo: instala stripe/stripe-php y configura tus llaves para pagos reales'
  ]);
}

// ── WEBHOOK DE STRIPE (pago confirmado) ─────────────────────
if ($action === 'webhook') {
  $payload_raw = file_get_contents('php://input');
  $sig_header  = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

  // \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
  // Verificar firma del webhook:
  // $event = \Stripe\Webhook::constructEvent($payload_raw, $sig_header, 'WEBHOOK_SECRET');

  // Por ahora, procesar el JSON directo
  $event = json_decode($payload_raw, true);

  if (($event['type'] ?? '') === 'checkout.session.completed') {
    $pedido_id = $event['data']['object']['metadata']['pedido_id'] ?? null;
    if ($pedido_id) {
      $db   = getDB();
      $stmt = $db->prepare("UPDATE pedidos SET estatus = 'pagado' WHERE id = ?");
      $stmt->execute([$pedido_id]);
    }
  }
  http_response_code(200);
  echo json_encode(['received' => true]);
  exit();
}

respond(false, ['error' => 'Acción no válida'], 400);
