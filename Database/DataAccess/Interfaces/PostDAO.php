<?php
namespace Database\DataAccess\Interfaces;

use Models\Post;

interface PostDAO {
    public function create(Post $postData): bool;
    public function getById(int $id): ?Post;
    public function update(Post $postData): bool;
    public function delete(int $id): bool;
    public function createOrUpdate(Post $postData): bool;

    /**
     * @param int $offset
     * @param int $limit
     * @return Post[] All main threads (posts with reply_to_id = null)
     */
    public function getAllThreads(int $offset, int $limit): array;

    /**
     * @param Post $postData - The thread being replied to
     * @param int $offset
     * @param int $limit
     * @return Post[] All replies for this thread
     */
    public function getReplies(Post $postData, int $offset, int $limit): array;
}
