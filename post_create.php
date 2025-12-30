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

function ensureDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Impossible de créer le dossier: $dir");
        }
    }
}

function saveUpload(string $field, string $destDir, string $publicBase, int $maxBytes, array $allowedMimes, array $extByMime): ?string {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur upload ($field).");
    }

    $tmp = (string)$_FILES[$field]['tmp_name'];
    $size = (int)($_FILES[$field]['size'] ?? 0);
    if ($size > $maxBytes) {
        throw new RuntimeException("$field trop lourd.");
    }

    $mime = @mime_content_type($tmp) ?: '';
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException("Type de fichier interdit pour $field.");
    }

    $ext = $extByMime[$mime] ?? null;
    if ($ext === null) {
        throw new RuntimeException("Extension inconnue pour $field.");
    }

    ensureDir($destDir);
    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $destPath = rtrim($destDir, '/') . '/' . $name;

    if (!move_uploaded_file($tmp, $destPath)) {
        throw new RuntimeException("Impossible d'enregistrer $field.");
    }

    return rtrim($publicBase, '/') . '/' . $name;
}

try {
    $content = trim((string)($_POST['content'] ?? ''));

    // au moins 1 champ
    $hasAny = ($content !== '')
        || (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)
        || (isset($_FILES['video']) && ($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)
        || (isset($_FILES['audio']) && ($_FILES['audio']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

    if (!$hasAny) {
        throw new RuntimeException("Post vide.");
    }

    $baseDir = __DIR__ . '/uploads/posts';
    $baseUrl = '/uploads/posts';

    $imageUrl = saveUpload(
        'image', $baseDir, $baseUrl,
        12 * 1024 * 1024,
        ['image/jpeg','image/png','image/webp'],
        ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']
    );

    $videoUrl = saveUpload(
        'video', $baseDir, $baseUrl,
        120 * 1024 * 1024,
        ['video/mp4','video/webm','video/ogg'],
        ['video/mp4'=>'mp4','video/webm'=>'webm','video/ogg'=>'ogv']
    );

    $audioUrl = saveUpload(
        'audio', $baseDir, $baseUrl,
        50 * 1024 * 1024,
        ['audio/mpeg','audio/mp3','audio/ogg','audio/wav'],
        ['audio/mpeg'=>'mp3','audio/mp3'=>'mp3','audio/ogg'=>'ogg','audio/wav'=>'wav']
    );

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image_url, video_url, audio_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $content !== '' ? $content : null,
        $imageUrl,
        $videoUrl,
        $audioUrl
    ]);

    header("Location: users.php");
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    echo "Erreur: " . htmlspecialchars($e->getMessage());
}
