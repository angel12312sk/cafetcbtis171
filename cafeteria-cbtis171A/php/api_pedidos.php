<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET: listar pedidos (admin) ────────────────────────────
if ($method === 'GET' && !isset($_GET['action'])) {
  $estatus = $_GET['estatus'] ?? 'all';
  $fecha   = $_GET['fecha']   ?? date('Y-m-d');

  $where = "DATE(p.created_at) = :fecha";
  $params = [':fecha' => $fecha];

  if ($estatus !== 'all') {
    $where .= " AND p.estatus = :estatus";
    $params[':estatus'] = $estatus;
  }

 $sql = "
    SELECT p.id, p.total, p.estatus, p.created_at,
      a.nombre AS alumno, a.correo, a.grado, a.grupo,
      COALESCE(
        GROUP_CONCAT(m.nombre, ' x', dp.cantidad ORDER BY m.nombre SEPARATOR ', '),
        'Sin detalle'
      ) AS items
    FROM pedidos p
    JOIN alumnos a ON a.id = p.alumno_id
    LEFT JOIN detalle_pedido dp ON dp.pedido_id = p.id
    LEFT JOIN menu m ON m.id = dp.menu_id
    WHERE $where
    GROUP BY p.id
    ORDER BY p.created_at DESC
  ";

  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  $pedidos = $stmt->fetchAll();

  $cnt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE estatus = 'pendiente' AND DATE(created_at) = ?");
  $cnt->execute([$fecha]);
  $pendientes = (int)$cnt->fetchColumn();

  respond(true, ['pedidos' => $pedidos, 'pendientes_total' => $pendientes]);
}

// ── POST/GET con action: crear pedido o liberar ──────
if ($method === 'POST' || isset($_GET['action'])) {
  $input = file_get_contents('php://input');
  $body = json_decode($input, true);
  if (empty($body)) { parse_str($input, $body); }
  if (empty($body)) { $body = array_merge($_GET, $_POST); }
  $action = $body['action'] ?? $_GET['action'] ?? 'create';

  // CREAR PEDIDO
  if ($action === 'create') {
    $alumno_id = $body['alumno_id'] ?? $_GET['alumno_id'] ?? null;
    $total     = $body['total']     ?? $_GET['total']     ?? 0;

    if (!$alumno_id) respond(false, ['error' => 'alumno_id requerido'], 400);

    $db->beginTransaction();
    try {
      $ins = $db->prepare('INSERT INTO pedidos (alumno_id, total, estatus) VALUES (?,?,?)');
      $ins->execute([$alumno_id, $total, 'pendiente']);
      $pedido_id = $db->lastInsertId();
      $db->commit();
      respond(true, ['pedido_id' => $pedido_id, 'total' => $total]);
    } catch (Exception $e) {
      $db->rollBack();
      respond(false, ['error' => 'Error al crear pedido'], 500);
    }
  }

  // MARCAR PAGADO
  if ($action === 'mark_paid') {
    $pedido_id = $body['pedido_id'] ?? null;
    if (!$pedido_id) respond(false, ['error' => 'pedido_id requerido'], 400);
    $stmt = $db->prepare("UPDATE pedidos SET estatus = 'pagado' WHERE id = ?");
    $stmt->execute([$pedido_id]);
    respond(true);
  }

  // LIBERAR / ENTREGAR
  if ($action === 'release') {
    $pedido_id = $body['id'] ?? null;
    if (!$pedido_id) respond(false, ['error' => 'id requerido'], 400);
    $stmt = $db->prepare("UPDATE pedidos SET estatus = 'entregado' WHERE id = ?");
    $stmt->execute([$pedido_id]);
    respond(true);
  }

  // MIS PEDIDOS
  if ($action === 'mis_pedidos') {
    $alumno_id = $body['alumno_id'] ?? $_GET['alumno_id'] ?? null;
    if (!$alumno_id) respond(false, ['error' => 'alumno_id requerido'], 400);
    $stmt = $db->prepare("
      SELECT p.id, p.total, p.estatus, p.created_at,
        GROUP_CONCAT(m.nombre, ' x', dp.cantidad SEPARATOR ', ') AS items
      FROM pedidos p
      LEFT JOIN detalle_pedido dp ON dp.pedido_id = p.id
      LEFT JOIN menu m ON m.id = dp.menu_id
      WHERE p.alumno_id = ?
      GROUP BY p.id
      ORDER BY p.created_at DESC
      LIMIT 20
    ");
    $stmt->execute([$alumno_id]);
    respond(true, ['pedidos' => $stmt->fetchAll()]);
  }

  respond(false, ['error' => 'Accion no valida'], 400);
}

respond(false, ['error' => 'Metodo no permitido'], 405);
