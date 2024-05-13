<?php
namespace SPP;

interface iModule{
    public function declare_events();
    public function handle_event($event);
}