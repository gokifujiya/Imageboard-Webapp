<?php
namespace Models;

class Post {
    public function __construct(
        private ?int $id = null,
        private ?int $replyToId = null,
        private ?string $subject = null,
        private string $content = "",
        private ?string $imagePath = null,
        private ?string $thumbnailPath = null,
        private ?string $createdAt = null,
        private ?string $updatedAt = null
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getReplyToId(): ?int { return $this->replyToId; }
    public function getSubject(): ?string { return $this->subject; }
    public function getContent(): string { return $this->content; }
    public function getImagePath(): ?string { return $this->imagePath; }
    public function getThumbnailPath(): ?string { return $this->thumbnailPath; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }
}
