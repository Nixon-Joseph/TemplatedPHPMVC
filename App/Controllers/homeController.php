<?php
class HomeController extends \devpirates\MVC\Base\Controller {
    function Index () {
        $this->outputCache("home_index", 3600, function () {
            //$postHelper = new PostHelper();
            //$posts = $postHelper->GetRecentPosts();
            $model = null;
            //if (isset($posts) && count($posts) > 0) {
            //    $model = new HomeVM($posts);
            //}
            return $this->getView($model, ACTION_NAME, "_layoutHome", SITE_DATA);
        });
    }
}
?>