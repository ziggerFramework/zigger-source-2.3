<?php

/***
Contents
***/
class Contents extends \controller\Make_Controller {

    public function init()
    {
        $this->layout()->category_key(2);
        $this->layout()->head();
        $this->layout()->view();
        $this->layout()->foot();
    }

    public function make()
    {
        $this->module();
    }

    public function module()
    {
        $module = new \Module\Contents\Make_Controller();
        $module->set('key', 'sample');
        $module->run();
    }

}

/***
Manager
***/
class Manager extends \controller\Make_Controller {

    public function init()
    {
        $this->layout()->category_key(3);
        $this->layout()->head();
        $this->layout()->view(PH_THEME_PATH.'/html/sub/manager.tpl.php');
        $this->layout()->foot();
    }

    public function make()
    {

    }

}


/***
Contactus
***/
class Contactus extends \controller\Make_Controller {

    public function init()
    {
        $this->layout()->category_key(7);
        $this->layout()->head();
        $this->layout()->view();
        $this->layout()->foot();
    }

    public function make()
    {
        $this->module();
    }

    public function module()
    {
        $module = new \Module\Contactform\Make_Controller();
        $module->run();
    }

}
