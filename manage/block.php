<?php
use Corelib\Func;
use Corelib\Method;
use Corelib\Valid;
use Make\Database\Pdosql;
use Make\Library\Paging;
use Manage\ManageFunc;

/***
Ip
***/
class Ip extends \Controller\Make_Controller {

    public function init(){
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/block/ip.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func(){
        function block_total($arr)
        {
            return Func::number($arr['total']);
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
                FROM {$sql->table("blockmb")}
                WHERE ip IS NOT NULL AND ip!=''
            ) total
            ", []
        );
        $sort_arr['total'] = $sql->fetch('total');

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
                FROM {$sql->table("blockmb")}
                WHERE ip IS NOT NULL AND ip!='' $sortby $searchby
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

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('block_total', block_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'blockipForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/block/ip-submit');
        $form->run();
    }

    public function form2(){
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'blockipDelForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/block/ip-submit');
        $form->run();
    }

}

/***
Submit for Ip
***/
class Ip_submit{

    public function init(){
        global $req;

        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'mode, idx, ip, memo');
        $manage->req_hidden_inp('post');

        switch ($req['mode']) {
            case 'add' :
                $this->get_add();
                break;

            case 'del' :
                $this->get_delete();
                break;
        }
    }

    ///
    // add
    ///
    public function get_add()
    {
        global $req;

        $sql = new Pdosql();

        Valid::get(
            array(
                'input' => 'ip',
                'value' => $req['ip']
            )
        );
        Valid::get(
            array(
                'input' => 'memo',
                'value' => $req['memo']
            )
        );

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("blockmb")}
            WHERE ip=:col1
            ",
            array(
                $req['ip']
            )
        );

        if ($sql->getcount() > 0) {
            Valid::error('ip', '이미 등록된 ip입니다.');
        }

        $sql->query(
            "
            INSERT INTO
            {$sql->table("blockmb")}
            (ip,memo,regdate)
            VALUES
            (:col1,:col2,now())
            ",
            array(
                $req['ip'], $req['memo']
            )
        );

        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '성공적으로 추가 되었습니다.'
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

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("blockmb")}
            WHERE idx=:col1
            ",
            array(
                $req['idx']
            )
        );

        if ($sql->getcount() < 1) {
            Valid::error('', '등록되지 않은 차단 정보입니다.');
        }

        $sql->query(
            "
            DELETE FROM {$sql->table("blockmb")}
            WHERE idx=:col1
            ",
            array(
                $req['idx']
            )
        );

        Valid::set(
            array(
                'return' => 'alert->reload'
            )
        );
        Valid::turn();
    }

}

/***
Member
***/
class Member extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/block/member.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function block_total($arr)
        {
            return Func::number($arr['total']);
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
                FROM {$sql->table("blockmb")}
                WHERE mb_id IS NOT NULL AND mb_id!=''
            ) total
            ", []
        );
        $sort_arr['total'] = $sql->fetch('total');

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
                FROM {$sql->table("blockmb")}
                WHERE mb_id IS NOT NULL AND mb_id!='' $sortby $searchby
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

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('block_total', block_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'blockmbForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/block/member-submit');
        $form->run();
    }

    public function form2()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'blockmbDelForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/block/member-submit');
        $form->run();
    }

}

/***
Submit for Member
***/
class Member_submit{

    public function init(){
        global $req;

        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'mode, idx, id, memo');
        $manage->req_hidden_inp('post');

        switch ($req['mode']) {
            case 'add' :
                $this->get_add();
                break;

            case 'del' :
                $this->get_delete();
                break;
        }
    }

    ///
    // add
    ///
    public function get_add()
    {
        global $req;

        $sql = new Pdosql();

        Valid::get(
            array(
                'input' => 'id',
                'value' => $req['id']
            )
        );
        Valid::get(
            array(
                'input' => 'memo',
                'value' => $req['memo']
            )
        );

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_id=:col1 AND mb_dregdate IS NULL AND mb_adm!='Y'
            ",
            array(
                $req['id']
            )
        );

        if ($sql->getcount() < 1) {
            Valid::error('id', '존재하지 않는 회원 id입니다.');
        }

        $mb_id = $req['id'];
        $mb_idx = $sql->fetch('mb_idx');

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("blockmb")}
            WHERE mb_idx=:col1 AND mb_id=:col2
            ",
            array(
                $mb_idx,
                $mb_id
            )
        );

        if ($sql->getcount() > 0) {
            Valid::error('id', '이미 등록된 회원 입니다.');
        }

        $sql->query(
            "
            INSERT INTO
            {$sql->table("blockmb")}
            (mb_idx,mb_id,memo,regdate)
            VALUES
            (:col1,:col2,:col3,now())
            ",
            array(
                $mb_idx,
                $mb_id,
                $req['memo']
            )
        );

        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '성공적으로 추가 되었습니다.'
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

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("blockmb")}
            WHERE idx=:col1
            ",
            array(
                $req['idx']
            )
        );

        if ($sql->getcount() < 1) {
            Valid::error('', '등록되지 않은 차단 정보입니다.');
        }

        $sql->query(
            "
            DELETE FROM {$sql->table("blockmb")}
            WHERE idx=:col1
            ",
            array(
                $req['idx']
            )
        );

        Valid::set(
            array(
                'return' => 'alert->reload'
            )
        );
        Valid::turn();
    }

}
