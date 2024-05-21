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

    public function o_hand1(){
        echo('override handler 1');
    }

    public function b_hand1(){
        echo 'before handler 1';
    }

    public function a_hand1(){
        echo 'after handler 1';
    }

    public function b_hand2()
    {
        echo 'before handler 2';
    }

    public function a_hand2()
    {
        echo 'after handler 2';
    }
}