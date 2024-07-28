<?php
namespace SPPMod\SPPView;

class SPPRequest extends \SPP\SPPObject
{
    private $data;
    private $call_code;
    private $request_url;
    private $request_method;
    public function __construct(){
        $this->request_method = $_SERVER['REQUEST_METHOD'];
        strtoupper($this->request_method)== 'POST' ? $this->data = $_POST: $this->data = $_GET;
        $this->request_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";;
    }

    public function getData(){
        return $this->data;
    }
    public function getRequestUrl(){
        return $this->request_url;
    }
    public function getRequestMethod(){
        return $this->request_method;
    }

    public function __get($propname)
    {
        return $this->data[$propname];
    }

    public function generateFromJson($jason_data){
        $this->data = $jason_data;
    }
}
