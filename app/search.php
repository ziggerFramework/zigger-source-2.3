<?php
use Corelib\Func;
use Corelib\Method;
use Make\Database\Pdosql;

/***
Index
***/
class index extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->head();
        $this->layout()->view();
        $this->layout()->foot();
    }

    public function make()
    {
        $this->module();
    }

    public function module(){
        $module = new \Module\Search\Make_Controller();
        $module->run();
    }

}
