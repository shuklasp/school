<?php
namespace SPP;
/**
 * class \SPP\SPPException
 * Top level class for all the exceptions defined in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPPException extends \Exception {
    public function __construct($message,$code=1000) {
        parent::__construct($message,$code);
    }

    public static function createException($eName)
    {
        $message="Exception $eName";
        $code=1000;
        $cls=new class($message,$code) extends \Exception {
            public function __construct($message, $code=1000)
            {
                parent::__construct($message, $code);
            }
        };
        return class_alias(get_class($cls), $eName, false);
    }

    public static function createExceptionInstance($eName, $message, $code)
    {
        switch ($eName) {
            case 'SPP_Syntax_Exception':
                return new SPP_Syntax_Exception($message, $code);
            case 'SPP_Logic_Exception':
                return new SPP_Logic_Exception($message, $code);
            default:
                return new SPPException($message, $code);
        }
    }
}

/**
 * class SPP_Syntax_Exception
 * Top level class for all the syntax exceptions defined in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Syntax_Exception extends \SPP\SPPException{
    public function  __construct($message,$code=2000) {
        parent::__construct($message, $code);
    }
}

/**
 * class SPP_Logic_Exception
 * Top level class for all the logic exceptions defined in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Logic_Exception extends \SPP\SPPException{
    public function  __construct($message,$code=3000) {
        parent::__construct($message, $code);
    }
}
?>