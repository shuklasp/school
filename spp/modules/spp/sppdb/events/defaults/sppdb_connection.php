<?php
namespace EventHandlers\Defaults;

class sppdb_connection extends \SPP\EventHandler {
public function initHandler(){
//    $this->addOverrideHandler('default1');
}

public function default1(){
    echo 'This is handler default1';
}

}