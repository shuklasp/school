<?php

namespace EventHandlers;

class PageNotFound extends \SPP\EventHandler
{
    public function initHandler()
    {
        $this->addAfterHandler('after_pnf');
//        $this->addBeforeHandler('before_test1');
        $this->addOverrideHandler('override_pnf');
    }

    public function after_pnf()
    {
        $params = $this->getParams();
        //print_r($params);
        echo 'Page requested "'.$params['page'].'" was not found on this server.';
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

    public function override_pnf()
    {
        echo '<h1>Page Not Found</h1>';
        //echo 'override_pnf';
    }
}
