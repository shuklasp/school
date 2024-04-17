<?php
require_once 'class.sppevent.php';
require_once 'class.spplogger.php';

class LoginEvent extends \SPP\SPP_Event{
    protected function handler()
    {
        SPP_Logger::write_to_log('Login');
    }
}

class LogoutEvent extends \SPP\SPP_Event{
    protected function handler()
    {
        SPP_Logger::write_to_log('Logout');
    }
}
?>