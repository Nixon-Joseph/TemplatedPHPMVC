<?php
/**
 * Every class deriving from Controller must implement Index() method
 * Index() method is the index page of the controller
 * Routing is based on controller class and it's methods
 * It is structured as: http(s)://address/class/method/[optional parameters divided by a '/']
 * Every page of the controller can accept optional parameters from the uri
 */
class HomeController extends Controller {
    /**
     * http://localhost/examplecontroller
     */
    function index () {
        $model = new TestModel();
        $model->id = 123;
        $model->name = "test";
        $model->size = 450;
        $model->arrData = array(new TestModel($model), new TestModel($model, true));
        $model->arrStr = array("abc", "123");
        $this->view($model);
    }
}

class TestModel extends Model {
    public function __construct(object $model = null, bool $fudgeData = false) {
        parent::__construct();
        if ($model !== null) {
            $this->id = $fudgeData ? $model->id  * 12 : $model->id;
            $this->name = $fudgeData ? $model->name  . "fudged" : $model->name;
            $this->size = $fudgeData ? $model->size  * 4 : $model->size;
        }
    }

    public $id;
    public $name;
    public $size;
    public $arrData;
    public $arrStr;
}
?>