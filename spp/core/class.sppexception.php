<?php
/**
 * class SPP_Exception
 * Top level class for all the exceptions defined in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Exception extends Exception {
    public function __construct($message,$code=1000) {
        parent::__construct($message,$code);
    }
}

/**
 * class SPP_Syntax_Exception
 * Top level class for all the syntax exceptions defined in SPP.
 *
 * @author Satya Prakash Shukla
 */
class SPP_Syntax_Exception extends SPP_Exception{
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
class SPP_Logic_Exception extends SPP_Exception{
    public function  __construct($message,$code=3000) {
        parent::__construct($message, $code);
    }
}
?>