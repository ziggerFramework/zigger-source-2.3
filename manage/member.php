<?php
use Corelib\Func;
use Corelib\Method;
use Corelib\Valid;
use Make\Database\Pdosql;
use Make\Library\Uploader;
use Make\Library\Paging;
use Make\Library\Mail;
use Manage\ManageFunc;

/***
Result
***/
class Result extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/result.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function mb_total($arr)
        {
            return Func::number($arr['mb_total']);
        }

        function emchk_total($arr)
        {
            return Func::number($arr['emchk_total']);
        }

        function namechk_total($arr)
        {
            return Func::number($arr['mb_total'] - $arr['emchk_total']);
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
                FROM {$sql->table("member")}
                WHERE mb_adm!='Y' AND mb_dregdate IS NULL
            ) mb_total,
            (
                SELECT COUNT(*)
                FROM {$sql->table("member")}
                WHERE mb_email_chk='Y' AND mb_adm!='Y' AND mb_dregdate IS NULL
            ) emchk_total
            ", []
        );
        $sort_arr['mb_total'] = $sql->fetch('mb_total');
        $sort_arr['emchk_total'] = $sql->fetch('emchk_total');

        switch ($PARAM['sort']) {
            case 'emchk' :
                $sortby = 'AND mb_email_chk=\'Y\'';
                break;

            case 'noemchk' :
                $sortby = 'AND mb_email_chk=\'N\'';
                break;
        }

        //orderby
        if (!$PARAM['ordtg']) {
            $PARAM['ordtg'] = 'mb_regdate';
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
                FROM {$sql->table("member")}
                WHERE mb_adm!='Y' AND mb_dregdate IS NULL $sortby $searchby
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
                $arr['mb_point'] = Func::number($arr['mb_point']);
                $arr['mb_regdate'] = Func::datetime($arr['mb_regdate']);

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('mb_total', mb_total($sort_arr));
        $this->set('emchk_total', emchk_total($sort_arr));
        $this->set('namechk_total', namechk_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);

    }

}

/***
Regist
***/
class Regist extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/regist.tpl.php');
        $this->layout()->mng_foot();
    }

    public function make()
    {
        $manage = new ManageFunc();

        $this->set('manage', $manage);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'addmbForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/member/regist-submit');
        $form->run();
    }

}

/***
Submit for Regist
***/
class Regist_submit {

    public function init()
    {
        global $CONF;

        $manage = new ManageFunc();
        $sql = new Pdosql();
        $mail = new Mail();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'id, pwd, pwd2, name, level, gender, phone, telephone, address1, address2, address3, point, email, email_chk, mb_1, mb_2, mb_3, mb_4, mb_5, mb_6, mb_7, mb_8, mb_9, mb_10, mb_exp');
        $manage->req_hidden_inp('post');

        Valid::get(
            array(
                'input' => 'id',
                'value' => $req['id'],
                'check' => array(
                    'defined' => 'id'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'email',
                'value' => $req['email'],
                'check' => array(
                    'defined' => 'email'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'pwd',
                'value' => $req['pwd'],
                'check' => array(
                    'defined' => 'password'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'pwd2',
                'value' => $req['pwd2'],
                'check' => array(
                    'defined' => 'password'
                )
            )
        );

        if ($req['pwd'] != $req['pwd2']) {
            Valid::error('pwd2', '비밀번호와 비밀번호확인이 일치하지 않습니다.');
        }

        Valid::get(
            array(
                'input' => 'name',
                'value' => $req['name'],
                'check' => array(
                    'defined' => 'nickname'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'phone',
                'value' => $req['phone'],
                'check' => array(
                    'null' => true,
                    'defined' => 'phone'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'telephone',
                'value' => $req['telephone'],
                'check' => array(
                    'null' => true,
                    'defined' => 'phone'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'point',
                'value' => $req['point'],
                'check' => array(
                    'charset' => 'number',
                    'minlen' => 1,
                    'maxlen' => 10
                )
            )
        );

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_id=:col1 AND mb_dregdate IS NULL
            ",
            array(
                $req['id']
            )
        );

        if ($sql->getcount() > 0) {
            Valid::error('id', '이미 존재하는 아이디입니다.');
        }

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_email=:col1 AND mb_dregdate IS NULL
            ",
            array(
                $req['email']
            )
        );

        if ($sql->getcount() > 0) {
            Valid::error('email', '이미 사용중인 이메일입니다.');
        }

        if ($CONF['use_emailchk'] == 'Y') {
            $mbchk_var = 'N';

        } else {
            $mbchk_var = 'Y';
        }

        $mb_exp = $sql->etcfd_exp(implode('|', $req['mb_exp']));

        $sql->query(
            "
            INSERT INTO {$sql->table("member")}
            (mb_id,mb_email,mb_pwd,mb_name,mb_level,mb_gender,mb_phone,mb_telephone,mb_address,mb_email_chk,mb_regdate,mb_1,mb_2,mb_3,mb_4,mb_5,mb_6,mb_7,mb_8,mb_9,mb_10,mb_exp)
            VALUES
            (:col1,:col2,password(:col3),:col4,:col5,:col6,:col7,:col8,:col9,:col10,now(),:col11,:col12,:col13,:col14,:col15,:col16,:col17,:col18,:col19,:col20,:col21)
            ",
            array(
                $req['id'], $req['email'], $req['pwd'], $req['name'], $req['level'], $req['gender'], $req['phone'], $req['telephone'], $req['address1'].'|'.$req['address2'].'|'.$req['address3'], $mbchk_var, $req['mb_1'], $req['mb_2'], $req['mb_3'], $req['mb_4'], $req['mb_5'], $req['mb_6'], $req['mb_7'], $req['mb_8'], $req['mb_9'], $req['mb_10'], $mb_exp
            )
        );

        $sql->query(
            "
            SELECT mb_idx
            FROM {$sql->table("member")}
            WHERE mb_id=:col1 AND mb_pwd=password(:col2) AND mb_dregdate IS NULL
            ",
            array(
                $req['id'], $req['pwd']
            )
        );
        $mb_idx = $sql->fetch('mb_idx');

        Func::set_mbpoint(
            array(
                'mb_idx' => $mb_idx,
                'mode' => 'in',
                'point' => $req['point'],
                'msg' => '관리자에 의한 회원가입 포인트 발생'
            )
        );

        if ($CONF['use_emailchk'] == 'Y') {
            $chk_code = md5(date('YmdHis').$req['id']);
            $chk_url = PH_DOMAIN.'/sign/emailchk?chk_code='.$chk_code;

            $mail->set(
                array(
                    'tpl' => 'signup',
                    'to' => array(
                        [
                            'email' => $req['email'],
                            'name' => $req['name']
                        ]
                    ),
                    'subject' => $req['name'].'님, '.$CONF['title'].' 이메일 인증을 해주세요.',
                    'chk_url' => '<a href="'.$chk_url.'" target="_blank">'.$chk_url.'</a>',
                )
            );
            $mail->send();

            $sql->query(
                "
                INSERT INTO {$sql->table("mbchk")}
                (mb_idx,chk_code,chk_chk,chk_regdate)
                VALUES
                (:col1,:col2,'N',now())
                ",
                array(
                    $mb_idx, $chk_code
                )
            );

            $succ_msg = '회원이 이메일로 발송된 메일을 확인하거나, 회원 관리에서 이메일 인증 처리하는 경우 회원가입이 완료됩니다.';

        } else {
            $succ_msg = '회원가입이 완료되었습니다.';
        }

        Valid::set(
            array(
                'return' => 'alert->location',
                'msg' => $succ_msg,
                'location' => PH_MANAGE_DIR.'/member/modify?idx='.$mb_idx
            )
        );
        Valid::turn();
    }

}

/***
Modify
***/
class Modify extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/modify.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func(){
        function set_checked($arr, $val)
        {
            $setarr = array(
                'Y' => '',
                'N' => '',
                'M' => '',
                'F' => ''
            );
            foreach ($setarr as $key => $value) {
                if ($key == $arr[$val]) {
                    $setarr[$key] = 'checked';
                }
            }
            return $setarr;
        }
    }

    public function make()
    {
        $sql = new Pdosql();
        $manage = new ManageFunc();

        $req = Method::request('get', 'idx');

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_adm!='Y' AND mb_dregdate IS NULL AND mb_idx=:col1
            LIMIT 1
            ",
            array(
                $req['idx']
            )
        );

        if ($sql->getcount() < 1) {
            Func::err_back('회원이 존재하지 않거나 수정할 수 없는 회원입니다.');
        }

        $arr = $sql->fetchs();

        $manage->make_target('회원 기본정보|회원 접속 정보|여분필드');

        $ex = explode('|', $arr['mb_exp']);

        for ($i = 1; $i <= 10; $i++) {
            $arr['mb_'.$i.'_exp'] = $ex[$i - 1];
        }

        $arr[0]['mb_profileimg'] = '';
        if ($arr['mb_profileimg']) {
            $fileinfo = Func::get_fileinfo($arr['mb_profileimg']);
            $arr[0]['mb_profileimg'] = $fileinfo['replink'];
        }

        $arr[0]['mb_address'] = explode('|', $arr['mb_address']);

        if (!$arr[0]['mb_address'][0]) {
            $arr[0]['mb_address'][0] = null;
            $arr[0]['mb_address'][1] = null;
            $arr[0]['mb_address'][2] = null;
        }

        $arr['mb_regdate'] = Func::datetime($arr['mb_regdate']);
        $arr['mb_lately'] = Func::datetime($arr['mb_lately']);

        $write = array();

        if (isset($arr)) {
            foreach ($arr as $key => $value){
                $write[$key] = $value;
            }

        } else {
            $write = null;
        }

        $this->set('manage', $manage);
        $this->set('write', $write);
        $this->set('print_target', $manage->print_target());
        $this->set('email_chk', set_checked($write, 'mb_email_chk'));
        $this->set('gender', set_checked($write, 'mb_gender'));
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'modifymbForm');
        $form->set('type', 'multipart');
        $form->set('action', PH_MANAGE_DIR.'/member/modify-submit');
        $form->run();
    }

}

/***
Submit for Modify
***/
class Modify_submit {

    public function init()
    {
        global $req, $file;

        $manage = new Managefunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'mode, idx, pwd, pwd2, name, level, gender, phone, telephone, point, email, address1, address2, address3, email_chk, mb_1, mb_2, mb_3, mb_4, mb_5, mb_6, mb_7, mb_8, mb_9, mb_10, mb_exp');
        $file = Method::request('file', 'profileimg');
        $manage->req_hidden_inp('post');

        switch ($req['mode']) {
            case 'mod' :
                $this->get_modify();
                break;

            case 'del' :
                $this->get_delete();
                break;
        }
    }

    ///
    // modify
    ///
    public function get_modify()
    {
        global $req, $file;

        $sql = new Pdosql();
        $uploader = new Uploader();

        Valid::get(
            array(
                'input' => 'name',
                'value' => $req['name'],
                'check' => array(
                    'defined' => 'nickname'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'email',
                'value' => $req['email'],
                'check' => array(
                    'defined' => 'email'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'phone',
                'value' => $req['phone'],
                'check' => array(
                    'null' => true,
                    'defined' => 'phone'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'telephone',
                'value' => $req['telephone'],
                'check' => array(
                    'null' => true,
                    'defined' => 'phone'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'point',
                'value' => $req['point'],
                'check' => array(
                    'charset' => 'number',
                    'minlen' => 1,
                    'maxlen' => 10
                )
            )
        );

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_dregdate IS NULL AND mb_email=:col1 AND mb_idx!=:col2
            ",
            array(
                $req['email'],
                $req['idx']
            )
        );
        if ($sql->getcount() > 0) {
            Valid::error('email', '다른 회원이 사용중인 email 입니다.');
        }

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_adm!='Y' AND mb_dregdate IS NULL AND mb_idx=:col1
            LIMIT 1
            ",
            array(
                $req['idx']
            )
        );
        $arr = $sql->fetchs();

        if ($req['pwd'] != $req['pwd2']) {
            Valid::error('pwd2', '비밀번호와 비밀번호 확인이 일치하지 않습니다.');
        }
        if ($req['pwd'] != '') {
            Valid::get(
                array(
                    'input' => 'pwd',
                    'value' => $req['pwd'],
                    'check' => array(
                        'defined' => 'password'
                    )
                )
            );
        }

        if ($req['point'] != $arr['mb_point']) {
            $point_chg = $req['point'] - $arr['mb_point'];

            if ($point_chg > 0) {
                Func::set_mbpoint(
                    array(
                        'mb_idx' => $arr['mb_idx'],
                        'mode' => 'in',
                        'point' => $point_chg,
                        'msg' => '관리자에 의한 포인트 조정'
                    )
                );

            } else if ($point_chg < 0){
                Func::set_mbpoint(
                    array(
                        'mb_idx' => $arr['mb_idx'],
                        'mode' => 'out',
                        'point' => $point_chg / -1,
                        'msg' => '관리자에 의한 포인트 조정'
                    )
                );
            }
        }

        $uploader->path= PH_DATA_PATH.'/memberprofile';
        $uploader->chkpath();

        $profileimg_name = '';

        if ($file['profileimg']['size'] > 0) {
            $uploader->file = $file['profileimg'];
            $uploader->intdict = SET_IMGTYPE;
            if ($uploader->chkfile('match') !== true) {
                Valid::error('profileimg', '허용되지 않는 프로필 이미지 유형입니다.');
            }
            if ($uploader->chkbyte(512000) !== true) {
                Valid::error('profileimg', '프로필 이미지 용량이 허용 용량을 초과합니다.');
            }
            $profileimg_name = $uploader->replace_filename($file['profileimg']['name']);
            if (!$uploader->upload($profileimg_name)) {
                Valid::error('profileimg', '프로필 이미지 업로드 실패');
            }
        }
        if (($file['profileimg']['size'] > 0 && $arr['mb_profileimg'] != '')) {
            $uploader->drop($arr['mb_profileimg']);
        }
        if ($arr['mb_profileimg'] != '' && !$file['profileimg']['name']) {
            $profileimg_name = $arr['mb_profileimg'];
        }

        $mb_exp = $sql->etcfd_exp(implode('|', $req['mb_exp']));

        if ($req['pwd'] != '') {

            $sql->query(
                "
                UPDATE {$sql->table("member")}
                SET mb_pwd=password(:col1),mb_name=:col2,mb_gender=:col3,mb_phone=:col4,mb_telephone=:col5,mb_address=:col6,mb_point=:col7,mb_profileimg=:col8,mb_level=:col9,mb_email=:col10,mb_email_chk=:col11,mb_1=:col12,mb_2=:col13,mb_3=:col14,mb_4=:col15,mb_5=:col16,mb_6=:col17,mb_7=:col18,mb_8=:col19,mb_9=:col20,mb_10=:col21,mb_exp=:col22
                WHERE mb_adm!='Y' AND mb_dregdate IS NULL AND mb_idx=:col23
                ",
                array(
                    $req['pwd'], $req['name'], $req['gender'], $req['phone'], $req['telephone'], $req['address1'].'|'.$req['address2'].'|'.$req['address3'], $req['point'], $profileimg_name, $req['level'], $req['email'], $req['email_chk'], $req['mb_1'], $req['mb_2'], $req['mb_3'], $req['mb_4'], $req['mb_5'], $req['mb_6'], $req['mb_7'], $req['mb_8'], $req['mb_9'], $req['mb_10'], $mb_exp, $req['idx']
                )
            );

        } else {

            $sql->query(
                "
                UPDATE {$sql->table("member")}
                SET mb_pwd=:col1,mb_name=:col2,mb_gender=:col3,mb_phone=:col4,mb_telephone=:col5,mb_address=:col6,mb_point=:col7,mb_profileimg=:col8,mb_level=:col9,mb_email=:col10,mb_email_chk=:col11,mb_1=:col12,mb_2=:col13,mb_3=:col14,mb_4=:col15,mb_5=:col16,mb_6=:col17,mb_7=:col18,mb_8=:col19,mb_9=:col20,mb_10=:col21,mb_exp=:col22
                WHERE mb_adm!='Y' AND mb_dregdate IS NULL AND mb_idx=:col23
                ",
                array(
                    $arr['mb_pwd'], $req['name'], $req['gender'], $req['phone'], $req['telephone'], $req['address1'].'|'.$req['address2'].'|'.$req['address3'], $req['point'], $profileimg_name, $req['level'], $req['email'], $req['email_chk'], $req['mb_1'], $req['mb_2'], $req['mb_3'], $req['mb_4'], $req['mb_5'], $req['mb_6'], $req['mb_7'], $req['mb_8'], $req['mb_9'], $req['mb_10'], $mb_exp, $req['idx']
                )
            );

        }

        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '성공적으로 변경 되었습니다.'
            )
        );
        Valid::turn();
    }

    ///
    // delete
    ///
    public function get_delete()
    {
        global $req;

        $sql = new Pdosql();
        $uploader = new Uploader();
        $manage = new ManageFunc();

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_adm!='Y' AND mb_dregdate IS NULL AND mb_idx=:col1
            LIMIT 1
            ",
            array(
                $req['idx']
            )
        );
        $arr = $sql->fetchs();

        if ($sql->getcount() < 1) {
            Valid::error('', '회원이 존재하지 않습니다.');
        }

        $uploader->path= PH_DATA_PATH.'/memberprofile';
        $uploader->chkpath();
        $uploader->drop($arr['mb_profileimg']);

        $sql->query(
            "
            UPDATE {$sql->table("member")}
            SET mb_dregdate=now()
            WHERE mb_dregdate IS NULL AND mb_idx=:col1
            ",
            array(
                $req['idx']
            )
        );

        Valid::set(
            array(
                'return' => 'alert->location',
                'msg' => '성공적으로 탈퇴 되었습니다.',
                'location' => PH_MANAGE_DIR.'/member/result'.$manage->retlink('')
            )
        );
        Valid::turn();
    }

}

/***
Unsigned
***/
class Unsigned extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/unsigned.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function mb_total($arr)
        {
            return Func::number($arr['mb_total']);
        }
    }

    public function make()
    {
        global $PARAM, $sortby, $searchby, $orderby;

        $sql = new Pdosql();
        $paging = new Paging();
        $manage = new ManageFunc();

        $sql->query(
            "
            SELECT
            (
                SELECT count(*)
                FROM {$sql->table("member")}
                WHERE mb_adm!='Y' AND mb_dregdate IS NOT NULL
            ) mb_total
            ", []
        );
        $sort_arr['mb_total'] = $sql->fetch('mb_total');

        //orderby
        if (!$PARAM['ordtg']) {
            $PARAM['ordtg'] = 'mb_regdate';
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
                FROM {$sql->table("member")}
                WHERE mb_adm!='Y' AND mb_dregdate IS NOT NULL $sortby $searchby
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
                $arr['mb_point'] = Func::number($arr['mb_point']);
                $arr['mb_regdate'] = Func::datetime($arr['mb_regdate']);
                $arr['mb_dregdate'] = Func::datetime($arr['mb_dregdate']);

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('mb_total', mb_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);

    }

}

/***
Record
***/
class Record extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/record.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function mbtype($arr)
        {
            if ($arr['mb_id']) {
                return '회원';

            } else {
                return '비회원';
            }
        }

        function visit_total($arr)
        {
            return Func::number($arr['visit_total']);
        }

        function device_per($arr)
        {
            if ($arr['device_total'] > 0) {
                $pc_per = @round(100 / ($arr['device_total'] / $arr['device_pc']), 1);
                $mo_per = 100 - $pc_per;
                return 'pc <strong>'.$pc_per.'%</strong> / mobile <strong>'.$mo_per.'%</strong>';

            } else {
                return '-';
            }
        }

        function member_per($arr)
        {
            if ($arr['device_total'] > 0) {
                $mb_per = @round(100 / ($arr['device_total'] / $arr['member_total']), 1);
                $gu_per = 100 - $mb_per;
                return '회원 <strong>'.$mb_per.'%</strong> / 비회원 <strong>'.$gu_per.'%</strong>';

            } else {
                return '-';
            }
        }

        function user_agent($type, $arr)
        {
            $brw = $arr['browser'];
            $agt = '';
            $os = '';

            if ($type == 'os') {
                if(stristr($brw,'android')){
                    $os = 'Android';
                } else if (stristr($brw, 'iphone')) {
                    $os = 'iPhone';
                } else if (stristr($brw, 'ipad')) {
                    $os = 'iPad';
                } else if (stristr($brw, 'ipod')) {
                    $os = 'iPod';
                } else if (stristr($brw, 'macintosh')) {
                    $os = 'Macintosh';
                } else if (stristr($brw, 'symbianos')) {
                    $os = 'SymbianOS';
                } else if (stristr($brw, 'blackberry')) {
                    $os = 'BlackBerry';
                } else if (stristr($brw, 'bb10')) {
                    $os = 'BB10';
                } else if (stristr($brw, 'nokia')) {
                    $os = 'Nokia';
                } else if (stristr($brw, 'sonyericsson')) {
                    $os = 'SonyEricsson';
                } else if (stristr($brw, 'webos')) {
                    $os = 'webOS';
                } else if(stristr($brw, 'palmos')) {
                    $os = 'PalmOS';
                } else if (stristr($brw, 'linux')) {
                    $os = 'LINUX';
                } else if (stristr($brw, 'windows')) {
                    $os = 'Windows';
                } else if (stristr($brw, 'googlebot')) {
                    $os = '* Googlebot';
                } else if (stristr($brw, 'bingbot')) {
                    $os = '* Bingbot';
                } else if (stristr($brw, 'yahoobot')) {
                    $os = '* Yahoobot';
                } else if (stristr($brw, 'naverbot')) {
                    $os = '* Naverbot';
                } else if (stristr($brw, 'baiduspider')) {
                    $os = '* Baiduspider';
                } else {
                    $os = '기타 OS';
                }
                return $os;

            } else if ($type == 'browser') {
                if (stristr($brw,'Edge')) {
                    $agt = 'Edge';
                } else if (stristr($brw, 'rv:11.0')) {
                    $agt = 'IE 11';
                } else if (stristr($brw, 'msie 10')) {
                    $agt = 'IE 10';
                } else if (stristr($brw, 'msie 9')) {
                    $agt = 'IE 9';
                } else if (stristr($brw, 'msie 8')) {
                    $agt = 'IE 8';
                } else if (stristr($brw, 'msie 7')) {
                    $agt = 'IE 7';
                } else if (stristr($brw, 'msie 6')) {
                    $agt = 'IE 6';
                } else if (stristr($brw, 'opera')) {
                    $agt = 'Opera';
                } else if (stristr($brw, 'firefox')) {
                    $agt = 'Firefox';
                } else if (stristr($brw, 'chrome')) {
                    $agt = 'Chrome';
                } else if (stristr($brw, 'safari')) {
                    $agt = 'Safari';
                } else {
                    $agt = '기타 Browser';
                }
                return $agt;
            }
        }
    }

    public function make()
    {
        global $PARAM, $sortby, $searchby, $orderby;

        $sql = new Pdosql();
        $paging = new Paging();
        $manage = new ManageFunc();

        $req = Method::request('get', 'nowdate, fdate, tdate');

        //date sortby
        if (!$req['fdate']) {
            $req['fdate'] = date('Y-m-d');
        }
        if (!$req['tdate']) {
            $req['tdate'] = date('Y-m-d');
        }

        //sortby
        $sortby = '';
        $sort_arr = array();

        $sql->query(
            "
            SELECT
            (
                SELECT COUNT(*)
                FROM {$sql->table("visitcount")}
            ) visit_total,
            (
                SELECT COUNT(*)
                FROM {$sql->table("visitcount")}
                WHERE DATE_FORMAT(regdate,'%Y-%m-%d') BETWEEN :col1 AND :col2
            ) device_total,
            (
                SELECT COUNT(*)
                FROM {$sql->table("visitcount")}
                WHERE DATE_FORMAT(regdate,'%Y-%m-%d') BETWEEN :col1 AND :col2 AND device='pc'
            ) device_pc,
            (
                SELECT COUNT(*)
                FROM {$sql->table("visitcount")}
                WHERE DATE_FORMAT(regdate,'%Y-%m-%d') BETWEEN :col1 AND :col2 AND mb_idx!=0
            ) member_total
            ",
            array(
                $req['fdate'],
                $req['tdate']
            )
        );
        $sort_arr['visit_total'] = $sql->fetch('visit_total');
        $sort_arr['device_total'] = $sql->fetch('device_total');
        $sort_arr['device_pc'] = $sql->fetch('device_pc');
        $sort_arr['member_total'] = $sql->fetch('member_total');

        //orderby
        if (!$PARAM['ordtg']) {
            $PARAM['ordtg'] = 'regdate';
        }
        if (!$PARAM['ordsc']) {
            $PARAM['ordsc'] = 'desc';
        }
        $orderby = $PARAM['ordtg'].' '.$PARAM['ordsc'];
        $PARAM[0]['fdate'] = $req['fdate'];
        $PARAM[0]['tdate'] = $req['tdate'];
        $PARAM[0]['nowdate'] = $req['nowdate'];

        //list
        $sql_arr = array($req['fdate'], $req['tdate']);
        $sql->query(
            $paging->query(
                "
                SELECT visit.*,IFNULL(member.mb_level,10) mb_level
                FROM {$sql->table("visitcount")} visit
                LEFT OUTER JOIN {$sql->table("member")} member
                ON visit.mb_idx=member.mb_idx
                WHERE DATE_FORMAT(visit.regdate,'%Y-%m-%d') BETWEEN date('{$req['fdate']}') AND date('{$req['tdate']}') $sortby $searchby
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
                $arr[0]['user_agent']['os'] = user_agent('os', $arr);
                $arr[0]['user_agent']['browser'] = user_agent('browser', $arr);

                if (!$arr['mb_level']) {
                    $arr['mb_level'] = 10;
                }

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('fdate', $req['fdate']);
        $this->set('tdate', $req['tdate']);
        $this->set('visit_total', visit_total($sort_arr));
        $this->set('device_per', device_per($sort_arr));
        $this->set('member_per', member_per($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param().'&fdate='.$req['fdate'].'&tdate='.$req['tdate']));
        $this->set('print_arr', $print_arr);
    }

}

/***
Session
***/
class Session extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/session.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func(){
        function stat_total($arr)
        {
            return Func::number($arr['stat_total']);
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
                FROM {$sql->table("session")}
                WHERE regdate>=DATE_SUB(now(),interval 10 minute)
            ) stat_total
            ", []
        );
        $sort_arr['stat_total'] = $sql->fetch('stat_total');

        //orderby
        if (!$PARAM['ordtg']) {
            $PARAM['ordtg'] = 'mb_regdate';
        }
        if (!$PARAM['ordsc']) {
            $PARAM['ordsc'] = 'desc';
        }
        $orderby = $PARAM['ordtg'].' '.$PARAM['ordsc'];

        //list
        $sql->query(
            $paging->query(
                "
                SELECT sess.*,member.*,IFNULL(member.mb_level,10) mb_level
                FROM {$sql->table("session")} sess
                LEFT OUTER JOIN
                {$sql->table("member")} member
                ON sess.mb_idx=member.mb_idx
                WHERE regdate>=DATE_SUB(now(),interval 10 minute) $sortby $searchby
                ORDER BY $orderby
                ", []
            ),''
        );
        $list_cnt = $sql->getcount();
        $total_cnt = Func::number($paging->totalCount);
        $print_arr = array();

        if ($list_cnt > 0) {
            do {
                $arr = $sql->fetchs();

                $arr['no'] = $paging->getnum();
                $arr['regdate'] = Func::datetime($arr['regdate']);

                if (!$arr['mb_level']) {
                    $arr['mb_level'] = 10;
                }

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('stat_total', stat_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);

    }

}

/***
Point
***/
class Point extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/member/point.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func(){
        function act_total($arr)
        {
            return Func::number($arr['act_total']);
        }

        function in_total($arr)
        {
            return Func::number($arr['in_total']);
        }

        function out_total($arr)
        {
            return Func::number($arr['out_total']);
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
                FROM {$sql->table("mbpoint")}
            ) act_total,
            (
                SELECT SUM(p_in)
                FROM {$sql->table("mbpoint")}
                WHERE p_in>0
            ) in_total,
            (
                SELECT SUM(p_out)
                FROM {$sql->table("mbpoint")}
                WHERE p_out>0
            ) out_total
            ", []
        );
        $sort_arr['act_total'] = $sql->fetch('act_total');
        $sort_arr['in_total'] = $sql->fetch('in_total');
        $sort_arr['out_total'] = $sql->fetch('out_total');

        if ($PARAM['sort']) {
            $sortby = 'AND '.$PARAM['sort'].'>0';
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
                SELECT mbpoint.*,member.*
                FROM {$sql->table("mbpoint")} mbpoint
                LEFT OUTER JOIN
                {$sql->table("member")} member
                ON mbpoint.mb_idx=member.mb_idx
                WHERE 1 $sortby $searchby
                ORDER BY $orderby
                ", []
            ),''
        );
        $list_cnt = $sql->getcount();
        $total_cnt = Func::number($paging->totalCount);
        $print_arr = array();

        if ($list_cnt > 0) {
            do {
                $arr = $sql->fetchs();

                $arr['no'] = $paging->getnum();
                $arr['p_in'] = Func::number($arr['p_in']);
                $arr['p_out'] = Func::number($arr['p_out']);
                $arr['regdate'] = Func::datetime($arr['regdate']);

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('act_total', act_total($sort_arr));
        $this->set('in_total', in_total($sort_arr));
        $this->set('out_total', out_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);

    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'mbpointForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/member/point-submit');
        $form->run();
    }

}

/***
Submit for Point
***/
class Point_submit{

    public function init()
    {
        global $id_qry;

        $sql = new Pdosql();
        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'id, point, memo');
        $manage->req_hidden_inp('post');

        Valid::get(
            array(
                'input' => 'id',
                'value' => $req['id']
            )
        );
        Valid::get(
            array(
                'input' => 'point',
                'value' => $req['point'],
                'check' => array(
                    'charset' => 'neganumber',
                    'minlen' => 1,
                    'maxlen' => 10
                )
            )
        );
        Valid::get(
            array(
                'input' => 'memo',
                'value' => $req['memo']
            )
        );

        $id_ex = explode('|', $req['id']);
        $id_ex = array_unique($id_ex);

        $id_qry = '';

        for ($i = 0; $i < count($id_ex); $i++) {
            if ($i == 0) {
                $id_qry .= 'mb_id=\''.$id_ex[$i].'\'';
            } else {
                $id_qry .= 'OR mb_id=\''.$id_ex[$i].'\'';
            }
        }

        $sql->query(
            "
            SELECT mb_idx
            FROM {$sql->table("member")}
            WHERE mb_dregdate IS NULL AND ($id_qry)
            ", []
        );
        if ($sql->getcount() < count($id_ex)) {
            Valid::error('id', '존재하지 않는 회원 아이디가 포함되어 있습니다.');
        }

        do {
            $mb_idx = $sql->fetch('mb_idx');

            if ($req['point'] > 0) {
                $p_type = 'in';
            } else {
                $p_type = 'out';
                $req['point'] = $req['point'] / -1;
            }
            Func::set_mbpoint(
                array(
                    'mb_idx' => $mb_idx,
                    'mode' => $p_type,
                    'point' => $req['point'],
                    'msg' => $req['memo']
                )
            );

        } while ($sql->nextRec());

        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '성공적으로 반영 되었습니다.'
            )
        );
        Valid::turn();
    }

}
