<?php
class PostRepo extends Repo {
    public function __construct() {
        parent::__construct("Post");
    }

    public function GetRecentPosts(): array {
        return $this->_getAll(6, "Date", false);
    }

    public function GetPost($postId): Post {
        return $this->_getById($postId);
    }
}

class Post {
    public $Uid;
    public $Name;
    public $Title;
    public $Date;
    public $Author;
    public $Snippet;
    public $Contents;
    public $Category;
    public $Image;
    public $Featured;
}
?>