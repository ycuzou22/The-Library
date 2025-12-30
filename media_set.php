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

if ($type === 'avatar') {
    // historise l'actuel avant switch (optionnel mais recommandé)
    $cur = $pdo->prepare("SELECT avatar_url FROM users WHERE id=?");
    $cur->execute([$userId]);
    $curUrl = (string)($cur->fetch()['avatar_url'] ?? '');
    if ($curUrl !== '') {
        $pdo->prepare("INSERT INTO user_media_history (user_id, type, url) VALUES (?, 'avatar', ?)")
            ->execute([$userId, $curUrl]);
    }

    $pdo->prepare("UPDATE users SET avatar_url=? WHERE id=?")->execute([$url, $userId]);
} elseif ($type === 'banner') {
    $cur = $pdo->prepare("SELECT banner_url FROM users WHERE id=?");
    $cur->execute([$userId]);
    $curUrl = (string)($cur->fetch()['banner_url'] ?? '');
    if ($curUrl !== '') {
        $pdo->prepare("INSERT INTO user_media_history (user_id, type, url) VALUES (?, 'banner', ?)")
            ->execute([$userId, $curUrl]);
    }

    $pdo->prepare("UPDATE users SET banner_url=? WHERE id=?")->execute([$url, $userId]);
} else {
    http_response_code(400);
    exit("Type invalide.");
}

header("Location: users.php#edit");
exit;
