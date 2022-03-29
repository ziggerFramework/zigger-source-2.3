<?php
namespace Module\Message;

use Corelib\Method;

/***
Messsage_tab_inc
***/
class Message_tab_inc extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->view(MOD_MESSAGE_THEME_PATH.'/message.tab.inc.tpl.php');
    }

    public function make()
    {
        $req = Method::request('get', 'mode, refmode');

        $tab_active = '';

        if ($req['mode'] != 'view') {
            if ($req['mode']) {
                $tab_active = $req['mode'];
            } else {
                $tab_active = 'received';
            }


        } else {
            $tab_active = $req['refmode'];
        }

        $this->set('tab_active', $tab_active);
    }

}
