<?php
/* ============================================================
   api_reportes.php — Reporte de ventas por día
   ============================================================ */
require_once 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'GET') {
  $fecha = $_GET['fecha'] ?? date('Y-m-d');

  // Detalle por platillo
  $stmt = $db->prepare("
    SELECT
      m.nombre, m.categoria,
      SUM(dp.cantidad)   AS cantidad,
      dp.precio_unit,
      SUM(dp.subtotal)   AS subtotal
    FROM detalle_pedido dp
    JOIN pedidos p ON p.id = dp.pedido_id
    JOIN menu m    ON m.id = dp.menu_id
    WHERE DATE(p.created_at) = ? AND p.estatus IN ('pagado','entregado')
    GROUP BY m.id, dp.precio_unit
    ORDER BY subtotal DESC
  ");
  $stmt->execute([$fecha]);
  $detalle = $stmt->fetchAll();

  // Totales
  $tot = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE DATE(created_at)=? AND estatus IN ('pagado','entregado')");
  $tot->execute([$fecha]); $total = (int)$tot->fetchColumn();

  $comp = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at)=? AND estatus='entregado'");
  $comp->execute([$fecha]); $completados = (int)$comp->fetchColumn();

  $pend_m = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE DATE(created_at)=? AND estatus='pendiente'");
  $pend_m->execute([$fecha]); $pendientes_monto = (int)$pend_m->fetchColumn();

  $est = $db->prepare("
    SELECT m.nombre FROM detalle_pedido dp
    JOIN pedidos p ON p.id=dp.pedido_id JOIN menu m ON m.id=dp.menu_id
    WHERE DATE(p.created_at)=?
    GROUP BY m.id ORDER BY SUM(dp.cantidad) DESC LIMIT 1
  ");
  $est->execute([$fecha]); $estrella_row = $est->fetch();

  respond(true, [
    'fecha'            => $fecha,
    'total'            => $total,
    'completados'      => $completados,
    'pendientes_monto' => $pendientes_monto,
    'estrella'         => $estrella_row['nombre'] ?? 'N/A',
    'detalle'          => $detalle,
  ]);
}

if ($method === 'POST') {
  $body  = json_decode(file_get_contents('php://input'), true) ?? [];
  $fecha = $body['fecha'] ?? date('Y-m-d');

  // Guardar snapshot del reporte en tabla reportes_diarios
  $check = $db->prepare('SELECT id FROM reportes_diarios WHERE fecha = ?');
  $check->execute([$fecha]);
  if ($check->fetch()) {
    respond(false, ['error' => 'Ya existe un reporte para esta fecha']);
  }

  $tot = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE DATE(created_at)=? AND estatus IN ('pagado','entregado')");
  $tot->execute([$fecha]); $total = (int)$tot->fetchColumn();

  $cnt = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at)=?");
  $cnt->execute([$fecha]); $num_pedidos = (int)$cnt->fetchColumn();

  $ins = $db->prepare('INSERT INTO reportes_diarios (fecha, total_ventas, num_pedidos) VALUES (?,?,?)');
  $ins->execute([$fecha, $total, $num_pedidos]);
  respond(true, ['message' => 'Reporte guardado', 'id' => $db->lastInsertId()]);
}

respond(false, ['error' => 'Método no permitido'], 405);
