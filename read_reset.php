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
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Méthode non autorisée.");
}
if (!hash_equals((string)$_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
  http_response_code(403);
  exit("CSRF invalide.");
}

$action = (string)($_POST['action'] ?? '');
$back = (string)($_POST['back'] ?? 'catalog.php');
if ($back === '' || str_starts_with($back, 'http')) $back = 'catalog.php';

if ($action === 'reset_one') {
  $chapterId = (int)($_POST['chapter_id'] ?? 0);
  if ($chapterId > 0) {
    $stmt = $pdo->prepare("DELETE FROM chapter_reads WHERE user_id=? AND chapter_id=?");
    $stmt->execute([$userId, $chapterId]);
  }
  header("Location: $back");
  exit;
}

if ($action === 'reset_all') {
  $mangaId = (int)($_POST['manga_id'] ?? 0);
  if ($mangaId > 0) {
    // supprime tous les "lus" pour ce manga
    $stmt = $pdo->prepare("
      DELETE cr
      FROM chapter_reads cr
      JOIN chapters c ON c.id = cr.chapter_id
      WHERE cr.user_id=? AND c.manga_id=?
    ");
    $stmt->execute([$userId, $mangaId]);
  }
  header("Location: $back");
  exit;
}

http_response_code(400);
echo "Action invalide.";
