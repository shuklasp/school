<?php
class AttributeNotFoundException extends SPP_Exception{
    public function  __construct($message,$code=2000) {
        parent::__construct($message, $code);
    }
}

class EntityNotFoundException extends SPP_Exception
{
    public function  __construct($message, $code = 2000)
    {
        parent::__construct($message, $code);
    }
}

?>