<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/config/db.php';
$pdo = db();

$userId = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Méthode non autorisée.");
}

if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
  http_response_code(403);
  exit("CSRF invalide.");
}

$mangaId = (int)($_POST['manga_id'] ?? 0);
$back = (string)($_POST['back'] ?? 'catalog.php');

if ($mangaId <= 0) {
  http_response_code(400);
  exit("Paramètres invalides.");
}

// Vérifie que le manga existe
$stmt = $pdo->prepare("SELECT id FROM mangas WHERE id=?");
$stmt->execute([$mangaId]);
if (!$stmt->fetch()) {
  http_response_code(404);
  exit("Manga introuvable.");
}

// Toggle
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id=? AND manga_id=?");
$stmt->execute([$userId, $mangaId]);
$exists = $stmt->fetch();

if ($exists) {
  $del = $pdo->prepare("DELETE FROM favorites WHERE user_id=? AND manga_id=?");
  $del->execute([$userId, $mangaId]);
} else {
  $ins = $pdo->prepare("INSERT INTO favorites (user_id, manga_id) VALUES (?, ?)");
  $ins->execute([$userId, $mangaId]);
}

if ($back === '' || str_starts_with($back, 'http')) $back = 'catalog.php';
header("Location: $back");
exit;
