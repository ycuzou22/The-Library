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
$stmt = $pdo->prepare("SELECT avatar_url, banner_url FROM users WHERE id=?");
$stmt->execute([$userId]);
$current = $stmt->fetch() ?: ['avatar_url' => null, 'banner_url' => null];

function ensureDir(string $dir): void {
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Impossible de créer le dossier: $dir");
        }
    }
}

function saveImageUpload(string $field, string $destDir, string $publicBase): ?string {
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Erreur upload ($field).");
    }
    $tmp = (string)$_FILES[$field]['tmp_name'];
    $size = (int)($_FILES[$field]['size'] ?? 0);
    if ($size > 10 * 1024 * 1024) { // 10MB
        throw new RuntimeException("Image trop lourde (max 10MB).");
    }

    $mime = @mime_content_type($tmp) ?: '';
    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default => null,
    };
    if ($ext === null) {
        throw new RuntimeException("Format image invalide (jpg/png/webp).");
    }

    ensureDir($destDir);

    $name = bin2hex(random_bytes(12)) . '.' . $ext;
    $destPath = rtrim($destDir, '/') . '/' . $name;

    if (!move_uploaded_file($tmp, $destPath)) {
        throw new RuntimeException("Impossible d'enregistrer l'image.");
    }

    return rtrim($publicBase, '/') . '/' . $name;
}

try {
    $bio = trim((string)($_POST['bio'] ?? ''));

    $avatarUrl = saveImageUpload('avatar', __DIR__ . '/uploads/avatars', '/uploads/avatars');
    $bannerUrl = saveImageUpload('banner', __DIR__ . '/uploads/banners', '/uploads/banners');

    // Update dynamique: seulement ce qui est fourni
    $fields = [];
    $params = [];
    
    $fields[] = "bio = ?";
    $params[] = ($bio !== '' ? $bio : null);
    
    if ($avatarUrl !== null) { $fields[] = "avatar_url = ?"; $params[] = $avatarUrl; }
    if ($bannerUrl !== null) { $fields[] = "banner_url = ?"; $params[] = $bannerUrl; }
    
    if ($avatarUrl !== null && !empty($current['avatar_url'])) {
    $ins = $pdo->prepare("INSERT INTO user_media_history (user_id, type, url) VALUES (?, 'avatar', ?)");
    $ins->execute([$userId, (string)$current['avatar_url']]);
    }
    if ($bannerUrl !== null && !empty($current['banner_url'])) {
        $ins = $pdo->prepare("INSERT INTO user_media_history (user_id, type, url) VALUES (?, 'banner', ?)");
        $ins->execute([$userId, (string)$current['banner_url']]);
    }
    $params[] = $userId;

    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header("Location: users.php");
    exit;
} catch (Throwable $e) {
    http_response_code(400);
    echo "Erreur: " . htmlspecialchars($e->getMessage());
}
