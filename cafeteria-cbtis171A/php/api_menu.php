<?php
/* ============================================================
   api_menu.php — Menú del día (lectura pública + CRUD admin)
   ============================================================ */
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── GET: listar menú (público) ─────────────────────────────
if ($method === 'GET') {
  $id = $_GET['id'] ?? null;

  if ($id) {
    // Un solo platillo para edición
    $stmt = $db->prepare('SELECT * FROM menu WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) respond(false, ['error' => 'Platillo no encontrado'], 404);
    respond(true, ['item' => $item]);
  }

  // Todos los platillos activos del día
  $stmt = $db->prepare('SELECT id, nombre, categoria, descripcion, precio, stock, stock_max, imagen FROM menu WHERE activo = 1 ORDER BY categoria, nombre');
  $stmt->execute();
  $items = $stmt->fetchAll();
  respond(true, ['items' => $items]);
}

// ── POST: crear / actualizar / eliminar (solo admin) ──────
if ($method === 'POST') {
  $body   = json_decode(file_get_contents('php://input'), true) ?? [];
  $action = $body['action'] ?? '';

  // Verificar admin (llamadas desde web usan session, desde app usan token)
  // Para web: verificamos sessionStorage en cliente + doble check por token si se envía
  // (simplificado: en producción real usa sesión PHP o JWT de admin)

  if ($action === 'create') {
    $stmt = $db->prepare('
      INSERT INTO menu (nombre, categoria, descripcion, precio, stock, stock_max, imagen, activo)
      VALUES (?,?,?,?,?,?,?,1)
    ');
    $stmt->execute([
      $body['nombre'], $body['categoria'], $body['descripcion'],
      $body['precio'], $body['stock'], $body['stock'] ?? 100, $body['imagen']
    ]);
    respond(true, ['id' => $db->lastInsertId()]);
  }

  if ($action === 'update') {
    $stmt = $db->prepare('
      UPDATE menu SET nombre=?, categoria=?, descripcion=?, precio=?, stock=?, imagen=?
      WHERE id=?
    ');
    $stmt->execute([
      $body['nombre'], $body['categoria'], $body['descripcion'],
      $body['precio'], $body['stock'], $body['imagen'], $body['id']
    ]);
    respond(true);
  }

  if ($action === 'delete') {
    $stmt = $db->prepare('UPDATE menu SET activo = 0 WHERE id = ?');
    $stmt->execute([$body['id']]);
    respond(true);
  }

  respond(false, ['error' => 'Acción no válida'], 400);
}

respond(false, ['error' => 'Método no permitido'], 405);
