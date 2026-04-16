<?php
namespace SPP\Exceptions;

class AttributeNotFoundException extends \SPP\SPPException{
    public function  __construct($message,$code=2000) {
        parent::__construct($message, $code);
    }
}

class EntityNotFoundException extends \SPP\SPPException
{
    public function  __construct($message, $code = 2000)
    {
        parent::__construct($message, $code);
    }
}

class EntityConfigurationException extends \SPP\SPPException
{
    public function  __construct($message, $code = 2001)
    {
        parent::__construct($message, $code);
    }
}

?>
