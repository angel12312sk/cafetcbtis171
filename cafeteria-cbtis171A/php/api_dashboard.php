<?php
/* ============================================================
   api_dashboard.php — Estadísticas en tiempo real para admin
   ============================================================ */
require_once 'config.php';
$db   = getDB();
$hoy  = date('Y-m-d');

// Total ventas del día (solo pagados + entregados)
$q1 = $db->prepare("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE DATE(created_at)=? AND estatus IN ('pagado','entregado')");
$q1->execute([$hoy]); $ventas_total = (int)$q1->fetchColumn();

// Total pedidos
$q2 = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at)=?");
$q2->execute([$hoy]); $total_pedidos = (int)$q2->fetchColumn();

// Pendientes
$q3 = $db->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(created_at)=? AND estatus='pendiente'");
$q3->execute([$hoy]); $pendientes = (int)$q3->fetchColumn();

// Alumnos únicos
$q4 = $db->prepare("SELECT COUNT(DISTINCT alumno_id) FROM pedidos WHERE DATE(created_at)=?");
$q4->execute([$hoy]); $alumnos_unicos = (int)$q4->fetchColumn();

// Platillo estrella
$q5 = $db->prepare("
  SELECT m.nombre, SUM(dp.cantidad) AS total_qty
  FROM detalle_pedido dp
  JOIN pedidos p ON p.id = dp.pedido_id
  JOIN menu m ON m.id = dp.menu_id
  WHERE DATE(p.created_at) = ?
  GROUP BY m.id ORDER BY total_qty DESC LIMIT 1
");
$q5->execute([$hoy]); $estrella = $q5->fetch();

// Últimos 5 pedidos
$q6 = $db->prepare("
  SELECT p.id, p.total, p.estatus,
    a.nombre AS alumno, a.grado, a.grupo,
    COALESCE(
      GROUP_CONCAT(m.nombre, ' x', dp.cantidad SEPARATOR ', '),
      'Sin detalle'
    ) AS items
  FROM pedidos p
  JOIN alumnos a ON a.id = p.alumno_id
  LEFT JOIN detalle_pedido dp ON dp.pedido_id = p.id
  LEFT JOIN menu m ON m.id = dp.menu_id
  WHERE DATE(p.created_at) = ?
  GROUP BY p.id
  ORDER BY p.created_at DESC
  LIMIT 5
");
$q6->execute([$hoy]); $ultimos = $q6->fetchAll();

respond(true, [
  'ventas_total'     => $ventas_total,
  'total_pedidos'    => $total_pedidos,
  'pendientes'       => $pendientes,
  'alumnos_unicos'   => $alumnos_unicos,
  'platillo_estrella'=> $estrella['nombre'] ?? 'N/A',
  'estrella_qty'     => $estrella['total_qty'] ?? 0,
  'ultimos_pedidos'  => $ultimos,
]);
