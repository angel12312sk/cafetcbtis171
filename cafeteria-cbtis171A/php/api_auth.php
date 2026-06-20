<?php
/* ============================================================
   api_auth.php — Login para admin (web) y alumnos (app móvil)
   ============================================================ */
require_once 'config.php';

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $body['action'] ?? 'login_admin';

// ── LOGIN ADMINISTRADOR (desde web) ──────────────────────────
if ($action === 'login_admin' || !isset($body['action'])) {
  $email = trim($body['email'] ?? '');
  $pass  = $body['password'] ?? '';

  if (!$email || !$pass) respond(false, ['error' => 'Campos requeridos'], 400);

  $db   = getDB();
  $stmt = $db->prepare('SELECT id, nombre, password_hash FROM admins WHERE email = ? LIMIT 1');
  $stmt->execute([$email]);
  $admin = $stmt->fetch();

  if (!$admin || !password_verify($pass, $admin['password_hash'])) {
    respond(false, ['error' => 'Credenciales incorrectas'], 401);
  }

  $token = jwtCreate([
    'sub'  => $admin['id'],
    'role' => 'admin',
    'exp'  => time() + 28800  // 8 horas
  ]);

  respond(true, ['nombre' => $admin['nombre'], 'token' => $token]);
}

// ── REGISTRO ALUMNO (desde app móvil) ────────────────────────
if ($action === 'register_alumno') {
  $nombre = trim($body['nombre'] ?? '');
  $correo = trim($body['correo'] ?? '');
  $pass   = $body['password']  ?? '';
  $grado  = trim($body['grado']  ?? '');
  $grupo  = trim($body['grupo']  ?? '');

  if (!$nombre || !$correo || !$pass || !$grado || !$grupo)
    respond(false, ['error' => 'Todos los campos son requeridos'], 400);

  $db   = getDB();
  $chk  = $db->prepare('SELECT id FROM alumnos WHERE correo = ? LIMIT 1');
  $chk->execute([$correo]);
  if ($chk->fetch()) respond(false, ['error' => 'El correo ya está registrado'], 409);

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $ins  = $db->prepare('INSERT INTO alumnos (nombre, correo, password_hash, grado, grupo) VALUES (?,?,?,?,?)');
  $ins->execute([$nombre, $correo, $hash, $grado, $grupo]);
  $id = $db->lastInsertId();

  $token = jwtCreate(['sub'=>$id, 'role'=>'alumno', 'exp'=> time()+2592000]);
  respond(true, ['alumno_id'=>$id, 'token'=>$token]);
}

// ── LOGIN ALUMNO (desde app móvil) ───────────────────────────
if ($action === 'login_alumno') {
  $correo = trim($body['correo'] ?? '');
  $pass   = $body['password']   ?? '';

  if (!$correo || !$pass) respond(false, ['error' => 'Campos requeridos'], 400);

  $db   = getDB();
  $stmt = $db->prepare('SELECT id, nombre, grado, grupo, password_hash FROM alumnos WHERE correo = ? LIMIT 1');
  $stmt->execute([$correo]);
  $alumno = $stmt->fetch();

  if (!$alumno || !password_verify($pass, $alumno['password_hash']))
    respond(false, ['error' => 'Credenciales incorrectas'], 401);

  $token = jwtCreate([
    'sub'   => $alumno['id'],
    'role'  => 'alumno',
    'nombre'=> $alumno['nombre'],
    'grado' => $alumno['grado'],
    'grupo' => $alumno['grupo'],
    'exp'   => time() + 2592000  // 30 días
  ]);

  respond(true, [
    'token'  => $token,
    'alumno' => [
      'id'     => $alumno['id'],
      'nombre' => $alumno['nombre'],
      'grado'  => $alumno['grado'],
      'grupo'  => $alumno['grupo'],
      'correo' => $correo
    ]
  ]);
}

respond(false, ['error' => 'Acción no válida'], 400);
