<?php
/* ============================================================
   api_inventario.php — Gestión de stock por día
   ============================================================ */
require_once 'config.php';
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

if ($method === 'GET') {
  $stmt = $db->prepare('SELECT id, nombre, categoria, stock, stock_max, imagen FROM menu WHERE activo = 1 ORDER BY categoria, nombre');
  $stmt->execute();
  respond(true, ['items' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
  $body   = json_decode(file_get_contents('php://input'), true) ?? [];
  $action = $body['action'] ?? '';

  if ($action === 'update_stock') {
    $stmt = $db->prepare('UPDATE menu SET stock = ? WHERE id = ?');
    $stmt->execute([(int)$body['stock'], $body['id']]);
    respond(true);
  }

  if ($action === 'reset_day') {
    // Reiniciar stocks al máximo al inicio de la jornada
    $db->exec('UPDATE menu SET stock = stock_max WHERE activo = 1');
    respond(true, ['message' => 'Inventario reiniciado para el día']);
  }

  respond(false, ['error' => 'Acción no válida'], 400);
}
respond(false, ['error' => 'Método no permitido'], 405);
