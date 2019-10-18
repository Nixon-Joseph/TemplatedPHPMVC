<?php
class HomeVM {
    /**
     * @var string
     */
    public $MainFeaturePostSnippet;
    /**
     * @var string
     */
    public $MainFeaturePostTitle;
    /**
     * @var int
     */
    public $MainFeaturePostUid;
    /**
     * @var array
     */
    public $SubFeaturePosts;
    /**
     * @var array
     */
    public $Posts;

    public function __construct(?array $posts) {
        if (isset($posts)) {
            $postCount = count($posts);
            if ($postCount > 0) {
                $this->MainFeaturePostSnippet = $posts[0]->Snippet;
                $this->MainFeaturePostTitle = $posts[0]->Title;
                $this->MainFeaturePostUid = $posts[0]->Uid;
            }
            if ($postCount > 1) {
                $this->SubFeaturePosts = array();
                $this->SubFeaturePosts[] = $posts[1];
            }
            if ($postCount > 2) {
                $this->SubFeaturePosts[] = $posts[2];
            }
            if ($postCount > 3) {
                $this->Posts = array();
                for ($i=3; $i < $postCount; $i++) { 
                    $this->Posts[] = $posts[$i];
                }
            }
        }
    }
}
?>