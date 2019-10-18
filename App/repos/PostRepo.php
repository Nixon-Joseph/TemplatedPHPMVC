<?php
class PostRepo {
    /**
     * @var PDO
     */
    private $db;

    public function __construct() {
        global $app;
        $this->db = $app->db;
    }

    public function GetRecentPosts(): array {
        $statement = $this->db->query("SELECT `Uid`, `Name`, `Title`, `Date`, `Author`, `Snippet`, `Contents`, `Category`, `Image`, `Featured` FROM `posts` ORDER BY `Date` DESC LIMIT 6");
        $posts = $statement->fetchAll(PDO::FETCH_CLASS, 'Post');
        return $posts;
    }

    public function GetPost($postId): Post {
        $statement = $this->db->prepare("SELECT `Uid`, `Name`, `Title`, `Date`, `Author`, `Snippet`, `Contents`, `Category`, `Image`, `Featured` FROM `posts` WHERE `Uid`=? LIMIT 1");
        $statement->setFetchMode(PDO::FETCH_CLASS, 'Post');
        $statement->execute([$postId]);
        $post = $statement->fetch();
        return $post;
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