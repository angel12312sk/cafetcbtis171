<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

$input = file_get_contents('php://input');
$body = json_decode($input, true);
if (empty($body)) {
    parse_str($input, $body);
}
if (empty($body)) {
    $body = array_merge($_GET, $_POST);
}
$action = $body['action'] ?? $_GET['action'] ?? '';

// ── CREAR SESIÓN DE PAGO ─────────────────────────────────────
if ($action === 'create_session') {
  $pedido_id = $body['pedido_id'] ?? null;
  $alumno_id = $body['alumno_id'] ?? null;

  if (!$pedido_id)
    respond(false, ['error' => 'pedido_id requerido'], 400);


  // ── MODO REAL ──
 require_once __DIR__ . '/../vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$session = \Stripe\Checkout\Session::create([
  'payment_method_types' => ['card'],
  'line_items' => [[
    'price_data' => [
      'currency' => 'mxn',
      'product_data' => ['name' => 'Pedido Cafetería CBTis #' . $pedido_id],
      'unit_amount' => (int)($total * 100),
    ],
    'quantity' => 1,
  ]],
  'mode' => 'payment',
  'success_url' => 'https://cafetcbtis171.onrender.com/pago_exitoso.html?pedido=' . $pedido_id,
  'cancel_url'  => 'https://cafetcbtis171.onrender.com/pago_cancelado.html',
  'metadata'    => ['pedido_id' => $pedido_id, 'alumno_id' => $alumno_id],
]);

respond(true, ['checkout_url' => $session->url, 'session_id' => $session->id]);
}

// ── WEBHOOK ─────────────────────────────────────────────────
if ($action === 'webhook') {
  $event = json_decode(file_get_contents('php://input'), true);
  if (($event['type'] ?? '') === 'checkout.session.completed') {
    $pedido_id = $event['data']['object']['metadata']['pedido_id'] ?? null;
    if ($pedido_id) {
      $db = getDB();
      $stmt = $db->prepare("UPDATE pedidos SET estatus = 'pagado' WHERE id = ?");
      $stmt->execute([$pedido_id]);
    }
  }
  http_response_code(200);
  echo json_encode(['received' => true]);
  exit();
}

respond(false, ['error' => 'Acción no válida'], 400);
