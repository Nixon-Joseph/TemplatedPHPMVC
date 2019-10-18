<?php
class PostsController extends Controller {
    public function index() {
        header("location: /404/notfound/");
    }

    public function Post($postId, $postName) {
        $postHelper = new PostHelper();
        $post = $postHelper->GetPost($postId);
        $this->view($post);
    }
}
?>