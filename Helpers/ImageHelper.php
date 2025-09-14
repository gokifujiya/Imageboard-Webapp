<?php
namespace Helpers;

use mysqli;

class ImageHelper
{
    // tune these as you like
    private const ALLOWED_EXT = ['jpg','jpeg','png','gif'];
    private const MAX_BYTES   = 5 * 1024 * 1024;           // 5MB per file
    private const MAX_FILES_PER_HOUR = 20;                 // per IP
    private const MAX_TOTAL_BYTES_PER_HOUR = 50_000_000;   // per IP
    private const MEDIA_PREFIX = 'img';                    // URL segment: https://domain/img/{slug}

    /** Handle POSTed upload */
    public static function handleUpload(): array
    {
        if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
            throw new \RuntimeException('No file uploaded.');
        }
        $file = $_FILES['image'];

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorToMessage((int)$file['error']));
        }
        if (($file['size'] ?? 0) <= 0 || $file['size'] > self::MAX_BYTES) {
            throw new \RuntimeException('File is empty or exceeds size limit (5MB).');
        }

        // Validate extension by original name
        $origName = (string)($file['name'] ?? 'upload');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            throw new \RuntimeException('Unsupported file type. Allowed: JPEG, PNG, GIF.');
        }

        // Validate real mime using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);
        $okMime = in_array($mime, ['image/jpeg','image/png','image/gif'], true);
        if (!$okMime) {
            throw new \RuntimeException('File content is not a valid image.');
        }

        // Optional expiry from form
        $expiry = $_POST['expiry'] ?? 'keep';
        $expiresAt = self::computeExpiry($expiry);

        // Rate limit checks (per IP, last hour)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        self::enforceRateLimits($ip, (int)$file['size']);

        // Generate slug + delete token
        $slug        = self::randomSlug(10);
        $deleteToken = self::randomSlug(24);

        // Storage path: public/media/{MEDIA_PREFIX}/YYYY/mm/dd/{slug}.{ext}
        $relDir  = sprintf('public/media/%s/%s/%s/%s', self::MEDIA_PREFIX, date('Y'), date('m'), date('d'));
        $absDir  = self::ensureDir($relDir);
        $relFile = $relDir . '/' . $slug . '.' . $ext;
        $absFile = $absDir . '/' . $slug . '.' . $ext;

        if (!move_uploaded_file($file['tmp_name'], $absFile)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }
        // Extra safety: chmod a readable file
        @chmod($absFile, 0644);

        // Persist to DB
        $mysqli = self::db();
        $stmt = $mysqli->prepare(
            'INSERT INTO images
            (slug, delete_token, media_type, mime, ext, original_name, stored_path, size_bytes, ip, ua, views, last_accessed_at, created_at, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), ?)'
        );
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $mediaType = self::MEDIA_PREFIX;
        $storedPath = $relFile; // relative from project root (served by your front controller or web server)
        $size = (int)$file['size'];

        $stmt->bind_param(
            'sssssssissss',
            $slug,
            $deleteToken,
            $mediaType,
            $mime,
            $ext,
            $origName,
            $storedPath,
            $size,
            $ip,
            $ua,
            $expiresAt
        );
        if (!$stmt->execute()) {
            @unlink($absFile);
            throw new \RuntimeException('DB error on insert: '.$mysqli->error);
        }

        return [
            'slug'         => $slug,
            'delete_token' => $deleteToken,
            'url'          => '/' . self::MEDIA_PREFIX . '/' . $slug,   // e.g., /img/abc123
            'delete_url'   => '/d/' . $deleteToken,
        ];
    }

    /** Fetch image by slug and increment view count + touch last_accessed_at */
    public static function getBySlug(string $slug): ?array
    {
        $slug = ValidationHelper::string($slug, 1, 64);

        $mysqli = self::db();
        // update first to avoid race (and record access)
        $stmt = $mysqli->prepare('UPDATE images SET views = views + 1, last_accessed_at = NOW() WHERE slug = ?');
        $stmt->bind_param('s', $slug);
        $stmt->execute();

        $stmt = $mysqli->prepare(
            'SELECT id, slug, delete_token, media_type, mime, ext, original_name, stored_path, size_bytes, ip, ua, views, last_accessed_at, created_at, expires_at
             FROM images WHERE slug = ? AND (expires_at IS NULL OR expires_at > NOW())'
        );
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ?: null;
    }

    /** Delete by delete token; returns true if deleted */
    public static function deleteByToken(string $token): bool
    {
        $token = ValidationHelper::string($token, 1, 128);

        $mysqli = self::db();
        // Read path to delete file
        $stmt = $mysqli->prepare('SELECT id, stored_path FROM images WHERE delete_token = ?');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if (!$row) return false;

        $absFile = self::absPath((string)$row['stored_path']);
        if (is_file($absFile)) @unlink($absFile);

        $stmt = $mysqli->prepare('DELETE FROM images WHERE id = ?');
        $stmt->bind_param('i', $row['id']);
        return $stmt->execute();
    }

    /** === helpers === */

    private static function computeExpiry(?string $choice): ?string
    {
        $choice = (string)$choice;
        if ($choice === '10m') return date('Y-m-d H:i:s', time() + 10*60);
        if ($choice === '1h')  return date('Y-m-d H:i:s', time() + 60*60);
        if ($choice === '1d')  return date('Y-m-d H:i:s', time() + 24*60*60);
        return null; // keep
    }

    private static function enforceRateLimits(string $ip, int $incomingBytes): void
    {
        $mysqli = self::db();

        // Count files in the last hour
        $stmt = $mysqli->prepare('SELECT COUNT(*) AS c, COALESCE(SUM(size_bytes),0) AS s FROM images WHERE ip = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $count = (int)($res['c'] ?? 0);
        $sum   = (int)($res['s'] ?? 0);

        if ($count >= self::MAX_FILES_PER_HOUR) {
            throw new \RuntimeException('Upload rate limit reached (files/hour). Try again later.');
        }
        if (($sum + $incomingBytes) > self::MAX_TOTAL_BYTES_PER_HOUR) {
            throw new \RuntimeException('Upload rate limit reached (total bytes/hour). Try again later.');
        }
    }

    private static function randomSlug(int $len): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $out = '';
        $bytes = random_bytes($len);
        for ($i=0; $i<$len; $i++) {
            $out .= $alphabet[ord($bytes[$i]) % strlen($alphabet)];
        }
        return $out;
    }

    private static function ensureDir(string $rel): string
    {
        $abs = self::absPath($rel);
        if (!is_dir($abs) && !@mkdir($abs, 0755, true)) {
            throw new \RuntimeException('Failed to create directory: '.$rel);
        }
        return $abs;
    }

    private static function absPath(string $rel): string
    {
        // Project root = dirname(__DIR__) because this file lives in Helpers/
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
    }

    private static function db(): \mysqli
    {
        // 1) config file override (optional, see step 3)
        $cfg = [];
        $cfgPath = __DIR__ . '/../config/database.php';
        if (is_file($cfgPath)) {
            $cfg = include $cfgPath;
        }

        // 2) read from config OR env (prefer getenv() over $_ENV)
        $host = $cfg['host'] ?? getenv('DB_HOST') ?? ($_ENV['DB_HOST'] ?? 'localhost');
        $user = $cfg['user'] ?? getenv('DB_USER') ?? ($_ENV['DB_USER'] ?? null);
        $pass = $cfg['pass'] ?? getenv('DB_PASS') ?? ($_ENV['DB_PASS'] ?? null);
        $name = $cfg['name'] ?? getenv('DB_NAME') ?? ($_ENV['DB_NAME'] ?? null);

        // 3) hard-stop if missing, to avoid silently using 'root'
        if (!$user || !$name) {
            throw new \RuntimeException('Database config missing. Set DB_HOST/DB_USER/DB_PASS/DB_NAME or create config/database.php');
        }

        $mysqli = @new \mysqli($host, $user, $pass, $name);
        if ($mysqli->connect_errno) {
            throw new \RuntimeException('DB connect failed: ' . $mysqli->connect_error);
        }
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    }


    private static function uploadErrorToMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Uploaded file is too large.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error.',
        };
    }
}

