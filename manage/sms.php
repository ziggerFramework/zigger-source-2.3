<?php
use Corelib\Func;
use Corelib\Method;
use Corelib\Valid;
use Make\Database\Pdosql;
use Make\Library\Paging;
use Make\Library\Sms;
use Make\Library\Uploader;
use Manage\ManageFunc;

/***
Tomember
***/
class Tomember extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/sms/tomember.tpl.php');
        $this->layout()->mng_foot();
    }

    public function make()
    {
        global $CONF;

        $manage = new ManageFunc();

        $req = Method::request('get', 'smsto');

        $is_show_wait = false;
        if ($CONF['use_sms'] != 'Y') {
            $is_show_wait = true;
        }

        $this->set('manage', $manage);
        $this->set('smsto', $req['smsto']);
        $this->set('is_show_wait', $is_show_wait);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'smsSendForm');
        $form->set('type', 'multipart');
        $form->set('action', PH_MANAGE_DIR.'/sms/tomember-submit');
        $form->run();
    }

}

/***
Send
***/
class Send extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/sms/send.tpl.php');
        $this->layout()->mng_foot();
    }

    public function make()
    {
        global $CONF;

        $manage = new ManageFunc();

        $req = Method::request('get', 'smsto');

        $is_show_wait = false;
        if ($CONF['use_sms'] != 'Y') {
            $is_show_wait = true;
        }

        $this->set('manage', $manage);
        $this->set('is_show_wait', $is_show_wait);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'smsSendForm');
        $form->set('type', 'multipart');
        $form->set('action', PH_MANAGE_DIR.'/sms/tomember-submit');
        $form->run();
    }

}

/***
Submit for Send
***/
class Tomember_submit{

    public function init()
    {
        global $CONF;

        $sql = new Pdosql();
        $sms = new Sms();
        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'type, to_mb, to_phone, level_from, level_to, subject, memo, use_resv, resv_date, resv_hour, resv_min');
        $file = Method::request('file', 'image');
        $manage->req_hidden_inp('post');

        if ($CONF['use_sms'] != 'Y') {
            Valid::error('', 'SMS 발송 기능이 활성화 되지 않아 SMS 발송이 불가합니다.');
        }

        Valid::get(
            array(
                'input' => 'memo',
                'value' => $req['memo']
            )
        );

        $rcv_sms = array();

        if ($req['type'] == 1) {

            Valid::get(
                array(
                    'input' => 'to_mb',
                    'value' => $req['to_mb']
                )
            );

            $sql->query(
                "
                SELECT *
                FROM {$sql->table("member")}
                WHERE mb_id=:col1 AND mb_dregdate IS NULL
                ",
                array(
                    $req['to_mb']
                )
            );

            if ($sql->getcount() < 1) {
                Valid::error('', '존재하지 않는 회원 id 입니다.');
            }
            if (!$sql->fetch('mb_phone')) {
                Valid::error('', '회원 휴대전화 번호가 등록되어 있지 않습니다.');
            }
            $rcv_sms[] = $sql->fetch('mb_phone');

            $req['level_from'] = 0;
            $req['level_to'] = 0;
            $req['to_phone'] = '';
        }

        if ($req['type'] == 2) {

            if ($req['level_from'] > $req['level_to']) {
                Valid::error('level_to', '수신 종료 level 보다 시작 level이 클 수 없습니다.');
            }

            $sql->query(
                "
                SELECT *
                FROM {$sql->table("member")}
                WHERE mb_level>=:col1 AND mb_level<=:col2 AND mb_phone IS NOT NULL AND mb_phone!='' AND mb_dregdate IS NULL
                ORDER BY mb_idx ASC
                ",
                array(
                    $req['level_from'],
                    $req['level_to']
                )
            );

            if ($sql->getcount() < 1) {
                Valid::error('', '범위내 수신할 회원이 존재하지 않습니다.');
            }

            do {
                $rcv_sms[] = $sql->fetch('mb_phone');

            } while ($sql->nextRec());

            $req['to_mb'] = '';
            $req['to_phone'] = '';

        }

        if ($req['type'] == 3) {

            $phone_exp = explode(',', $req['to_phone']);

            foreach($phone_exp as $key => $value) {
                $value = trim($value);

                Valid::get(
                    array(
                        'input' => 'to_phone',
                        'value' => $value,
                        'check' => array(
                            'defined' => 'phone'
                        )
                    )
                );

                $rcv_sms[] = $value;
            }

            $req['to_mb'] = '';
            $req['level_from'] = 0;
            $req['level_to'] = 0;

        }

        //send
        $sms_arr = array();
        $sms_arr = array(
            'to' => $rcv_sms,
            'memo' => stripslashes($req['memo'])
        );

        if ($req['subject']) {
            $sms_arr['subject'] = $req['subject'];
        }

        if ($req['use_resv'] == 'checked') {
            Valid::get(
                array(
                    'input' => 'resv_date',
                    'value' => $req['resv_date']
                )
            );
            $sms_arr['reserveTime'] = $req['resv_date'].' '.$req['resv_hour'].':'.$req['resv_min'];
            $req['use_resv'] = 'Y';

        } else {
            $req['use_resv'] = 'N';
            $req['resv_date'] = '';
            $req['resv_hour'] = '';
            $req['resv_min'] = '';
        }

        if (isset($file['image'])) {

            $uploader = new Uploader();
            $uploader->file = $file['image'];
            $uploader->intdict = 'jpg,jpeg';

            if ($uploader->chkfile('match') !== true) {
                Valid::error('image', '허용되지 않는 이미지 유형입니다.');
            }

            $sms_arr['attach'] = array(
                $file['image']['tmp_name']
            );
        }

        $sms->set($sms_arr);
        $sms->send();

        $sql->query(
            "
            INSERT INTO {$sql->table("sentsms")}
            (sendtype,to_mb,level_from,level_to,subject,memo,use_resv,resv_date,resv_hour,resv_min,to_phone,regdate)
            VALUES
            (:col1,:col2,:col3,:col4,:col5,:col6,:col7,:col8,:col9,:col10,:col11,now())
            ",
            array(
                $sms->sendType, $req['to_mb'], $req['level_from'], $req['level_to'], $req['subject'], $req['memo'], $req['use_resv'], $req['resv_date'], $req['resv_hour'], $req['resv_min'], trim($req['to_phone'])
            )
        );

        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '성공적으로 발송 되었습니다.'
            )
        );
        Valid::turn();
    }

}

/***
History
***/
class History extends \Controller\Make_Controller {

    public function init(){
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/sms/history.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function sent_total($arr)
        {
            return Func::number($arr['total']);
        }

        function to_mb_total($arr)
        {
            return Func::number($arr['to_mb_total']);
        }

        function level_from_total($arr)
        {
            return Func::number($arr['level_from_total']);
        }

        function to_phone_total($arr)
        {
            return Func::number($arr['to_phone_total']);
        }

        function print_level($arr)
        {
            global $MB;

            if ($arr['to_mb'] || $arr['to_phone']) {
                return '-';
            } else {
                return $arr['level_from'].' ('.$MB['type'][$arr['level_from']].') ~ '.$arr['level_to'].' ('.$MB['type'][$arr['level_to']].')';
            }
        }

        function print_to_mb($arr)
        {
            global $MB;
            if (!$arr['to_mb']) {
                return '-';
            } else {
                return $arr['to_mb'];
            }
        }

        function print_to_phone($arr)
        {
            global $MB;
            if (!$arr['to_phone']) {
                return '-';
            } else {
                return $arr['to_phone'];
            }
        }
    }

    public function make()
    {
        global $PARAM, $sortby, $searchby, $orderby;

        $sql = new Pdosql();
        $paging = new Paging();
        $manage = new ManageFunc();

        //sortby
        $sortby = '';
        $sort_arr = array();

        $sql->query(
            "
            SELECT
            (
                SELECT COUNT(*)
                FROM {$sql->table("sentsms")}
            ) total,
            (
                SELECT COUNT(*)
                FROM {$sql->table("sentsms")}
                WHERE to_mb IS NOT NULL AND to_mb!=''
            ) to_mb_total,
            (
                SELECT COUNT(*)
                FROM {$sql->table("sentsms")}
                WHERE (to_mb IS NULL OR to_mb='') AND (to_phone IS NULL OR to_phone='')
            ) level_from_total,
            (
                SELECT COUNT(*)
                FROM {$sql->table("sentsms")}
                WHERE to_phone IS NOT NULL AND to_phone!=''
            ) to_phone_total
            ", []
        );
        $sort_arr['total'] = $sql->fetch('total');
        $sort_arr['to_mb_total'] = $sql->fetch('to_mb_total');
        $sort_arr['level_from_total'] = $sql->fetch('level_from_total');
        $sort_arr['to_phone_total'] = $sql->fetch('to_phone_total');

        switch ($PARAM['sort']) {
            case 'to_mb' :
                $sortby = 'AND to_mb IS NOT NULL AND to_mb!=\'\'';
                break;

            case 'level_from' :
                $sortby = 'AND ((to_mb IS NULL OR to_mb=\'\') AND (to_phone IS NULL OR to_phone=\'\'))';
                break;

            case 'to_phone' :
                $sortby = 'AND to_phone IS NOT NULL AND to_phone!=\'\'';
                break;
        }

        //orderby
        if (!$PARAM['ordtg']) {
            $PARAM['ordtg'] = 'regdate';
        }
        if (!$PARAM['ordsc']) {
            $PARAM['ordsc'] = 'desc';
        }
        $orderby = $PARAM['ordtg'].' '.$PARAM['ordsc'];

        //list
        $sql->query(
            $paging->query(
                "
                SELECT *
                FROM {$sql->table("sentsms")}
                WHERE 1 $sortby $searchby
                ORDER BY $orderby
                ", []
            )
        );
        $list_cnt = $sql->getcount();
        $total_cnt = Func::number($paging->totalCount);
        $print_arr = array();

        if ($list_cnt > 0) {
            do {
                $arr = $sql->fetchs();

                $arr['no'] = $paging->getnum();
                $arr['regdate'] = Func::datetime($arr['regdate']);
                $arr[0]['print_level'] = print_level($arr);
                $arr[0]['print_to_mb'] = print_to_mb($arr);
                $arr[0]['print_to_phone'] = print_to_phone($arr);
                $arr[0]['subject'] = Func::strcut($arr['subject'], 0, 15);
                $arr[0]['memo'] = Func::strcut($arr['memo'], 0, 20);

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('sent_total', sent_total($sort_arr));
        $this->set('to_mb_total', to_mb_total($sort_arr));
        $this->set('level_from_total', level_from_total($sort_arr));
        $this->set('to_phone_total', to_phone_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);
    }

}
