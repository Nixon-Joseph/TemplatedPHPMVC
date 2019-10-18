<?php
class PostHelper {
    /**
     * @var PostRepo
     */
    private $repo;

    public function __construct() {
        $this->repo = new PostRepo();
    }

    public function GetRecentPosts(): array {
        return $this->repo->GetRecentPosts();
    }

    public function GetPost($postId): Post {
        return $this->repo->GetPost($postId);
    }
}
?>