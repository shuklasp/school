<?php
namespace SPPMod\SPPView;

/**
 * class SPPResponse
 * @package SPPMod\SPPView
 * 
 * Defines response in SPP View
 */
class SPPResponse extends \SPP\SPPObject{
    /**
     * Main value
     * @var 
     */
    private $value=new  class{
        public $data=[];
        public $message="";
        public $status=200;
    };

    /**
     * Constructor
     * @param int $status
     */
    public function __construct($status=200){
        $this->value->status = $status;
    }

    /**
     * function getStatus()
     * Get status
     * @return int
     */
    public function getStatus(){
        return $this->value->status;
    }

    /**
     * function getData()
     * Get data
     * @return mixed
     */
    public function getData(){
        return $this->value->data;
    }

    /**
     * function getMessage()
     * Get message
     * @return string
     */
    public function getMessage(){
        return $this->value->message;
    }


    /**
     * function setMessage()
     * Set message
     * @param string $message
     */
    public function setMessage($message){
        $this->value->message = $message;
    }


    /**
     * function setData()
     * Set data
     * @param mixed $data
     */
    public function setData($data){
        $this->value->data = $data;
    }

    /**
     * function setStatus()
     * Set status
     * @param int $status
     */
    public function setStatus($status){
        $this->value->status = $status;
    }

    /**
     * function __set()
     * Set property in data
     * @param string $propname
     * @param mixed $propval
     */
    public function __set($propname, $propval) {
        $this->value->data[$propname] = $propval;
    }

    /**
     * function __get()
     * Get property from data
     * @param string $propname
     * @return mixed
     */
    public function __get($propname) {
        return $this->value->data[$propname];
    }

    /**
     * function json()
     * Get json value
     * @return bool|string
     */
    public function json(){
        return json_encode($this->value);
    }

    /**
     * function redirect()
     * Redirect to url
     * @param string $url
     */
    public static function redirect(string $url){
        header("Location: $url");
        exit;
    }
}
