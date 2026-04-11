<?php
namespace EventHandlers;

class sppdb_connection extends \SPP\EventHandler{
    public function initHandler(){
//        $this->addBeforeHandler('b_hand1');
//        $this->addAfterHandler('a_hand1');
        // $this->addBeforeHandler('b_hand2');
        // $this->addAfterHandler('a_hand2');
//        $this->addOverrideHandler('o_hand1');
    }

    public function o_hand1(array &$params = []){
        echo('override handler 1');
    }

    public function b_hand1(array &$params = []){
        echo 'before handler 1';
    }

    public function a_hand1(array &$params = []){
        echo 'after handler 1';
    }

    public function b_hand2(array &$params = [])
    {
        echo 'before handler 2';
    }

    public function a_hand2(array &$params = [])
    {
        echo 'after handler 2';
    }
}