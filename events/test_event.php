<?php
namespace EventHandlers;

class test_event extends \SPP\EventHandler{
    public function initHandler()
    {
        $this->addAfterHandler('after_test1');
        $this->addBeforeHandler('before_test1');
        $this->addOverrideHandler('override_test1');
    }

    public function after_test1()
    {
        echo 'after_test1';
    }

    public function before_test1()
    {
        echo 'before_test1';
    }

    public function override_test1()
    {
        echo 'override_test1';
    }
}