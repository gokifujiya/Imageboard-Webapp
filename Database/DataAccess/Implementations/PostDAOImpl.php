<?php
namespace Database\DataAccess\Implementations;

use Database\DataAccess\Interfaces\PostDAO;
use Database\DatabaseManager;
use Models\Post;

class PostDAOImpl implements PostDAO {
    public function create(Post $postData): bool {
        $mysqli = DatabaseManager::getMysqliConnection();

        return $mysqli->prepareAndExecute(
            "INSERT INTO posts (reply_to_id, subject, content, image_path, thumbnail_path) VALUES (?, ?, ?, ?, ?)",
            "issss",
            [
                $postData->getReplyToId(),
                $postData->getSubject(),
                $postData->getContent(),
                $postData->getImagePath(),
                $postData->getThumbnailPath(),
            ]
        );
    }

    public function getById(int $id): ?Post {
        $mysqli = DatabaseManager::getMysqliConnection();
        $rows = $mysqli->prepareAndFetchAll("SELECT * FROM posts WHERE post_id = ?", "i", [$id]);
        return $rows ? $this->mapToPost($rows[0]) : null;
    }

    public function update(Post $postData): bool {
        $mysqli = DatabaseManager::getMysqliConnection();
        return $mysqli->prepareAndExecute(
            "UPDATE posts SET subject=?, content=?, image_path=?, thumbnail_path=? WHERE post_id=?",
            "ssssi",
            [
                $postData->getSubject(),
                $postData->getContent(),
                $postData->getImagePath(),
                $postData->getThumbnailPath(),
                $postData->getId()
            ]
        );
    }

    public function delete(int $id): bool {
        $mysqli = DatabaseManager::getMysqliConnection();
        return $mysqli->prepareAndExecute("DELETE FROM posts WHERE post_id=?", "i", [$id]);
    }

    public function createOrUpdate(Post $postData): bool {
        return $postData->getId() ? $this->update($postData) : $this->create($postData);
    }

    public function getAllThreads(int $offset, int $limit): array {
        $mysqli = DatabaseManager::getMysqliConnection();
        $rows = $mysqli->prepareAndFetchAll(
            "SELECT * FROM posts WHERE reply_to_id IS NULL ORDER BY created_at DESC LIMIT ?, ?",
            "ii", [$offset, $limit]
        );
        return array_map([$this, 'mapToPost'], $rows ?? []);
    }

    public function getReplies(Post $postData, int $offset, int $limit): array {
        $mysqli = DatabaseManager::getMysqliConnection();
        $rows = $mysqli->prepareAndFetchAll(
            "SELECT * FROM posts WHERE reply_to_id = ? ORDER BY created_at ASC LIMIT ?, ?",
            "iii", [$postData->getId(), $offset, $limit]
        );
        return array_map([$this, 'mapToPost'], $rows ?? []);
    }

    private function mapToPost(array $row): Post {
        return new Post(
            id: $row["post_id"],
            replyToId: $row["reply_to_id"],
            subject: $row["subject"],
            content: $row["content"],
            imagePath: $row["image_path"],
            thumbnailPath: $row["thumbnail_path"],
            createdAt: $row["created_at"],
            updatedAt: $row["updated_at"]
        );
    }
}
