<?php
namespace SPP;

interface SPP_iModule{
    public function declare_events();
    public function handle_event($event);
}