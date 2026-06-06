<?php

class FileRenderer { // Lo dejamos con fin didáctico, (no se utiliza).
    public function __construct() {
    }

    public function render($viewName,$resultado = array()){
        include_once("view/header.mustache");
        include_once("view/". $viewName. "View.php");
        include_once("view/footer.mustache");
    }
}

?>