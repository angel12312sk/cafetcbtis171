<?php
/* ============================================================
   api_pedidos.php — Pedidos (app móvil crea, admin gestiona)
   ============================================================ */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET: listar pedidos (admin) ────────────────────────────
if ($method === 'GET') {
  $estatus = $_GET['estatus'] ?? 'all';
  $fecha   = $_GET['fecha']   ?? date('Y-m-d');

  $where = "DATE(p.created_at) = :fecha";
  $params = [':fecha' => $fecha];

  if ($estatus !== 'all') {
    $where .= " AND p.estatus = :estatus";
    $params[':estatus'] = $estatus;
  }

  $sql = "
    SELECT
      p.id, p.total, p.estatus, p.created_at,
      a.nombre AS alumno, a.correo, a.grado, a.grupo,
      GROUP_CONCAT(m.nombre, ' ×', dp.cantidad ORDER BY m.nombre SEPARATOR ', ') AS items
    FROM pedidos p
    JOIN alumnos a ON a.id = p.alumno_id
    JOIN detalle_pedido dp ON dp.pedido_id = p.id
    JOIN menu m ON m.id = dp.menu_id
    WHERE $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
  ";

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $pedidos = $stmt->fetchAll();

  // Contar pendientes totales del día
  $cnt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE estatus = 'pendiente' AND DATE(created_at) = ?");
  $cnt->execute([$fecha]);
  $pendientes = (int)$cnt->fetchColumn();

  respond(true, ['pedidos' => $pedidos, 'pendientes_total' => $pendientes]);
}

// ── POST: crear pedido (app móvil) o liberar (admin) ──────
if ($method === 'POST') {
  $body   = json_decode(file_get_contents('php://input'), true) ?? [];
  $action = $body['action'] ?? 'create';

  // CREAR PEDIDO (alumno desde app)
  if ($action === 'create') {
    $payload = requireAuth();  // JWT del alumno
    if ($payload['role'] !== 'alumno') respond(false, ['error'=>'Solo alumnos pueden hacer pedidos'], 403);

    $alumno_id = $payload['sub'];
    $items     = $body['items'] ?? [];  // [{menu_id:1, cantidad:2}, ...]

    if (empty($items)) respond(false, ['error' => 'El pedido está vacío'], 400);

    // Calcular total y verificar stock
    $total = 0;
    $detalles = [];
    foreach ($items as $item) {
      $stmt = $db->prepare('SELECT id, precio, stock, nombre FROM menu WHERE id = ? AND activo = 1');
      $stmt->execute([$item['menu_id']]);
      $platillo = $stmt->fetch();
      if (!$platillo) respond(false, ['error' => 'Platillo no encontrado: ' . $item['menu_id']], 404);
      if ($platillo['stock'] < $item['cantidad'])
        respond(false, ['error' => "Sin stock suficiente para: {$platillo['nombre']}"], 409);
      $subtotal = $platillo['precio'] * $item['cantidad'];
      $total   += $subtotal;
      $detalles[] = ['menu_id'=>$item['menu_id'], 'cantidad'=>$item['cantidad'], 'precio_unit'=>$platillo['precio'], 'subtotal'=>$subtotal];
    }

    // Insertar pedido
    $db->beginTransaction();
    try {
      $ins = $db->prepare('INSERT INTO pedidos (alumno_id, total, estatus) VALUES (?,?,?)');
      $ins->execute([$alumno_id, $total, 'pendiente']);
      $pedido_id = $db->lastInsertId();

      foreach ($detalles as $d) {
        $dp = $db->prepare('INSERT INTO detalle_pedido (pedido_id, menu_id, cantidad, precio_unit, subtotal) VALUES (?,?,?,?,?)');
        $dp->execute([$pedido_id, $d['menu_id'], $d['cantidad'], $d['precio_unit'], $d['subtotal']]);

        // Descontar stock
        $upd = $db->prepare('UPDATE menu SET stock = stock - ? WHERE id = ?');
        $upd->execute([$d['cantidad'], $d['menu_id']]);
      }
      $db->commit();
      respond(true, ['pedido_id' => $pedido_id, 'total' => $total]);
    } catch (Exception $e) {
      $db->rollBack();
      respond(false, ['error' => 'Error al crear pedido'], 500);
    }
  }

  // ACTUALIZAR ESTATUS DE PAGO (desde Stripe webhook)
  if ($action === 'mark_paid') {
    $pedido_id = $body['pedido_id'] ?? null;
    if (!$pedido_id) respond(false, ['error' => 'pedido_id requerido'], 400);
    $stmt = $db->prepare("UPDATE pedidos SET estatus = 'pagado' WHERE id = ?");
    $stmt->execute([$pedido_id]);
    respond(true);
  }

  // LIBERAR / ENTREGAR (admin)
  if ($action === 'release') {
    $pedido_id = $body['id'] ?? null;
    if (!$pedido_id) respond(false, ['error' => 'id requerido'], 400);
    $stmt = $db->prepare("UPDATE pedidos SET estatus = 'entregado' WHERE id = ?");
    $stmt->execute([$pedido_id]);
    respond(true);
  }

  // OBTENER MIS PEDIDOS (alumno desde app)
  if ($action === 'mis_pedidos') {
    $payload   = requireAuth();
    $alumno_id = $payload['sub'];
    $stmt = $db->prepare("
      SELECT p.id, p.total, p.estatus, p.created_at,
        GROUP_CONCAT(m.nombre, ' ×', dp.cantidad SEPARATOR ', ') AS items
      FROM pedidos p
      JOIN detalle_pedido dp ON dp.pedido_id = p.id
      JOIN menu m ON m.id = dp.menu_id
      WHERE p.alumno_id = ?
      GROUP BY p.id
      ORDER BY p.created_at DESC
      LIMIT 20
    ");
    $stmt->execute([$alumno_id]);
    respond(true, ['pedidos' => $stmt->fetchAll()]);
  }

  respond(false, ['error' => 'Acción no válida'], 400);
}

respond(false, ['error' => 'Método no permitido'], 405);
