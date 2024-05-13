<?php
require_once 'class.sppevent.php';
require_once 'class.spplogger.php';

class LoginEvent extends \SPP\SPPEvent{
    protected function handler()
    {
        \SPPMod\SPP_Logger::write_to_log('Login');
    }
}

class LogoutEvent extends \SPP\SPPEvent{
    protected function handler()
    {
        \SPPMod\SPP_Logger::write_to_log('Logout');
    }
}
?>