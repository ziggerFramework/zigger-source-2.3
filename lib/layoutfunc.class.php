<?php
namespace Make\View;

use Corelib\Func;
use Module\Message\Library as Message_Library;
use Module\Alarm\Library as Alarm_Library;

class Layout {

    public function logo_title()
    {
        global $CONF;
        return $CONF['title'];
    }

    public function site_href()
    {
        return PH_DOMAIN;
    }

    public function message_new_count()
    {
        $Message_Library = new Message_Library();

        return Func::number($Message_Library->get_new_count());
    }

    public function alarm_new_count()
    {
        $Alarm_Library = new Alarm_Library();

        return Func::number($Alarm_Library->get_new_count());
    }

    public function logo_src()
    {
        global $CONF;

        if ($CONF['logo']) {
            return $CONF['logo'];

        }else{
            return PH_THEME_DIR.'/layout/images/logo.png';
        }
    }

    public function signin_href()
    {
        $link = PH_DIR.'/sign/signin?redirect='.urlencode(Func::thisuriqry());

        if (Func::thisctrlr() == 'sign' || Func::thisctrlr() == 'member') {
            $link = PH_DIR.'/sign/signin?redirect=/';
        }

        return $link;
    }

}
