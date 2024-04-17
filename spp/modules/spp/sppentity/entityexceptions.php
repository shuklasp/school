<?php
class AttributeNotFoundException extends \SPP\SPP_Exception{
    public function  __construct($message,$code=2000) {
        parent::__construct($message, $code);
    }
}

class EntityNotFoundException extends \SPP\SPP_Exception
{
    public function  __construct($message, $code = 2000)
    {
        parent::__construct($message, $code);
    }
}

?>