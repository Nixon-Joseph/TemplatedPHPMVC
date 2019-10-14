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
    function Index () {
        $this->view();
    }
}
?>