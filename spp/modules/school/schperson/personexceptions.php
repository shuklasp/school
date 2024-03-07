<?php
class AjaxRoutineNotFoundException extends SPP_Exception{
    public function  __construct($message,$code=2000) {
        parent::__construct($message, $code);
    }
}


class AjaxVariableNotFoundException extends SPP_Exception{
    public function  __construct($message,$code=2000) {
        parent::__construct($message, $code);
    }
}
?>