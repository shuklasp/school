<?php

namespace EventHandlers\Defaults;

class test_event extends \SPP\EventHandler
{
    public function initHandler()
    {
        $this->addAfterHandler('after_test1');
        $this->addBeforeHandler('before_test1');
        $this->addOverrideHandler('override_test1');
    }

    public function after_test1()
    {
        echo 'default after_test1';
    }

    public function before_test1()
    {
        echo 'default before_test1';
    }

    public function override_test1()
    {
        echo 'default override_test1';
    }
}
