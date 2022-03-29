<?php
/***
Term Of Service
***/
class Terms_of_service extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->head();
        $this->layout()->view(PH_THEME_PATH.'/html/doc/terms-of-service.tpl.php');
        $this->layout()->foot();
    }

    public function make()
    {

    }

}

/***
Privacy Policy
***/
class Privacy_policy extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->head();
        $this->layout()->view(PH_THEME_PATH.'/html/doc/privacy-policy.tpl.php');
        $this->layout()->foot();
    }

    public function make()
    {

    }

}
