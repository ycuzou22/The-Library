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

// CSRF token (généré si absent)
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = (string)$_SESSION['csrf'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Méthode non autorisée.");
}

$postCsrf = (string)($_POST['csrf'] ?? '');
if (!hash_equals($csrf, $postCsrf)) {
    http_response_code(403);
    exit("CSRF invalide.");
}

$histId = (int)($_POST['hist_id'] ?? 0);
if ($histId <= 0) {
    http_response_code(400);
    exit("Paramètres invalides.");
}

// On récupère l'entrée d'historique (et on vérifie que ça appartient à l'utilisateur)
$stmt = $pdo->prepare("SELECT id, type, url FROM user_media_history WHERE id=? AND user_id=?");
$stmt->execute([$histId, $userId]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit("Historique introuvable.");
}

$type = (string)$row['type']; // avatar|banner
$url  = (string)$row['url'];

if (!in_array($type, ['avatar','banner'], true) || trim($url) === '') {
    http_response_code(400);
    exit("Entrée historique invalide.");
}

// Historise l'actuel avant switch (recommandé)
if ($type === 'avatar') {
    $cur = $pdo->prepare("SELECT avatar_url FROM users WHERE id=?");
    $cur->execute([$userId]);
    $curUrl = trim((string)($cur->fetch()['avatar_url'] ?? ''));

    if ($curUrl !== '') {
        $pdo->prepare("INSERT INTO user_media_history (user_id, type, url) VALUES (?, 'avatar', ?)")
            ->execute([$userId, $curUrl]);
    }

    $pdo->prepare("UPDATE users SET avatar_url=? WHERE id=?")->execute([$url, $userId]);
} else { // banner
    $cur = $pdo->prepare("SELECT banner_url FROM users WHERE id=?");
    $cur->execute([$userId]);
    $curUrl = trim((string)($cur->fetch()['banner_url'] ?? ''));

    if ($curUrl !== '') {
        $pdo->prepare("INSERT INTO user_media_history (user_id, type, url) VALUES (?, 'banner', ?)")
            ->execute([$userId, $curUrl]);
    }

    $pdo->prepare("UPDATE users SET banner_url=? WHERE id=?")->execute([$url, $userId]);
}

// Redirect retour (pour garder UX)
$back = (string)($_POST['back'] ?? 'users.php#edit');
// mini protection: si jamais back est vide ou trop chelou
if ($back === '' || str_starts_with($back, 'http')) {
    $back = 'users.php#edit';
}

header("Location: $back");
exit;
