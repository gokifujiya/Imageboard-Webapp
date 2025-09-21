<?php
namespace Helpers;

class ImageHelper {
    public static function saveImage(array $file, int $postId, string $createdAt): array {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif'])) {
            throw new \Exception("Unsupported file type.");
        }

        $hash = hash('sha256', $postId . $createdAt);
        $basePath = __DIR__ . "/../storage/uploads/";

        if (!is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $filename = $hash . "." . $ext;
        $filePath = $basePath . $filename;
        move_uploaded_file($file['tmp_name'], $filePath);

        // Thumbnail
        $thumbPath = $basePath . $hash . "_thumb.jpg";
        $cmd = ($ext === "gif")
            ? "convert '{$filePath}[0]' -resize 150x150 '$thumbPath'"
            : "convert '$filePath' -resize 150x150! '$thumbPath'";
        exec($cmd);

        return [$filePath, $thumbPath];
    }
}

