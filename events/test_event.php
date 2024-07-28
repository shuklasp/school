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
        $params=$this->getParams();
        print_r($params);
        echo 'after_test1';
    }

    public function before_test1()
    {
        $params = $this->getParams();
        $params['var1'] = 'new_value1';
        $params['var2'] = 'new_value2';
        $this->setParams($params);
        //print_r(\SPP\Registry::get('__events'));
        //print_r($params);
        echo 'before_test1';
    }

    public function override_test1()
    {
        echo 'override_test1';
    }
}