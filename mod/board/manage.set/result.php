<?php
use Corelib\Func;
use Corelib\Method;
use Corelib\Valid;
use Make\Database\Pdosql;
use Make\Library\Uploader;
use Make\Library\Paging;
use Make\Library\Mail;
use Manage\ManageFunc;

class Result extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(MOD_BOARD_PATH.'/manage.set/html/result.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function board_total($arr)
        {
            return Func::number($arr['board_total']);
        }

        function data_total($arr)
        {
            global $board_id;

            $sql = new Pdosql();

            $board_id = $arr['cfg_value'];

            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:board_data_".$board_id)}
                WHERE use_notice='Y' or use_notice='N'
                ",
                array(
                    $board_id
                )
            );
            return Func::number($sql->getcount());
        }
    }

    public function make()
    {
        global $PARAM, $sortby, $searchby, $orderby;

        $sql = new Pdosql();
        $sql2 = new Pdosql();
        $paging = new Paging();
        $manage = new ManageFunc();

        $sql->query(
            "
            SELECT
            (
                SELECT COUNT(*)
                FROM {$sql->table("config")}
                WHERE cfg_type like 'mod:board:config:%' AND cfg_key='id'
            ) board_total
            ", []
        );
        $sort_arr['board_total'] = $sql->fetch('board_total');

        //orderby
        if (!$PARAM['ordtg']) {
            $PARAM['ordtg'] = 'config.cfg_regdate';
        }
        if (!$PARAM['ordsc']) {
            $PARAM['ordsc'] = 'desc';
        }
        $orderby = $PARAM['ordtg'].' '.$PARAM['ordsc'];

        //list
        $sql->query(
            $paging->query(
                "
                SELECT config.*,board_name_tbl.cfg_value AS board_name
                FROM {$sql->table("config")} config
                LEFT OUTER JOIN {$sql->table("config")} board_name_tbl
                ON config.cfg_type=board_name_tbl.cfg_type AND board_name_tbl.cfg_key='title'
                WHERE config.cfg_type like 'mod:board:config:%' AND config.cfg_key='id' $searchby
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

                $sql2->query(
                    "
                    SELECT *
                    FROM {$sql2->table("config")}
                    WHERE cfg_type='mod:board:config:{$arr['cfg_value']}'
                    ", []
                );

                $arr2 = array();

                do {
                    $arr2[$sql2->fetch('cfg_key')] = $sql2->fetch('cfg_value');

                } while($sql2->nextRec());

                $arr['no'] = $paging->getnum();
                $arr['id'] = $arr2['id'];
                $arr['title'] = $arr2['title'];
                $arr['list_level'] = $arr2['list_level'];
                $arr['read_level'] = $arr2['read_level'];
                $arr['write_level'] = $arr2['write_level'];
                $arr['regdate'] = Func::datetime($arr['cfg_regdate']);
                $arr[0]['data_total'] = data_total($arr);

                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('manage', $manage);
        $this->set('keyword', $PARAM['keyword']);
        $this->set('board_total', board_total($sort_arr));
        $this->set('pagingprint', $paging->pagingprint($manage->pag_def_param()));
        $this->set('print_arr', $print_arr);

    }

    public function form($idx)
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'makeBoardForm'.$idx);
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/mod/'.MOD_BOARD_DIR.'/result/result-clone-submit');
        $form->run();
    }

}

/***
Submit for Result clone
***/
class Result_clone_submit{

    public function init()
    {
        global $board_id, $clone_id, $board_title;

        $sql = new Pdosql();
        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post','board_id, clone_id');

        Valid::get(
            array(
                'input' => 'board_id',
                'value' => $req['board_id'],
                'check' => array(
                    'defined' => 'idx'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'clone_id',
                'value' => $req['clone_id'],
                'check' => array(
                    'defined' => 'idx'
                )
            )
        );

        $board_id = $req['board_id'];
        $clone_id = $req['clone_id'];

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("config")}
            WHERE cfg_type='mod:board:config:{$board_id}'
            ", []
        );

        if ($sql->getcount() < 1) {
            Valid::error('', '복제할 게시판이 존재하지 않습니다.');
        }

        $arr = array();

        do {
            $cfg = $sql->fetchs();
            $arr[$cfg['cfg_key']] = $cfg['cfg_value'];

        } while($sql->nextRec());

        $board_title = addSlashes($arr['title']);

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("config")}
            WHERE cfg_type='mod:board:config:{$clone_id}'
            ", []
        );

        if ($sql->getcount() > 0) {
            Valid::error('clone_id', '생성할 게시판 id가 이미 존재하는 id입니다.');
        }

        foreach ($arr as $key => $value) {

            if ($key == 'title') {
                $value = $board_title.'에서 복제됨';
            }
            if ($key == 'id') {
                $value = $clone_id;
            }

            $sql->query(
                "
                INSERT INTO {$sql->table("config")}
                (cfg_type,cfg_key,cfg_value,cfg_regdate)
                VALUES
                ('mod:board:config:{$clone_id}','{$key}','{$value}',now())
                ", []
            );
        }

        $sql->query(
            "
            CREATE TABLE IF NOT EXISTS {$sql->table("mod:board_data_")}$clone_id (
            idx int(11) NOT NULL auto_increment,
            category varchar(255) default NULL,
            ln int(11) default '0',
            rn int(11) default '0',
            mb_idx int(11) default '0',
            mb_id varchar(255) default NULL,
            writer varchar(255) default NULL,
            pwd text,
            email varchar(255) default NULL,
            article text,
            subject varchar(255) default NULL,
            file1 text,
            file1_cnt int(11) default '0',
            file2 text,
            file2_cnt int(11) default '0',
            use_secret char(1) default 'N',
            use_notice char(1) default 'N',
            use_html char(1) default 'Y',
            use_email char(1) default 'Y',
            view int(11) default '0',
            ip varchar(255) default NULL,
            regdate datetime default NULL,
            dregdate datetime default NULL,
            data_1 text,
            data_2 text,
            data_3 text,
            data_4 text,
            data_5 text,
            data_6 text,
            data_7 text,
            data_8 text,
            data_9 text,
            data_10 text,
            PRIMARY KEY(idx)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ", []
        );

        $sql->query(
            "
            CREATE TABLE IF NOT EXISTS {$sql->table("mod:board_cmt_")}$clone_id (
            idx int(11) NOT NULL auto_increment,
            ln int(11) default '0',
            rn int(11) default '0',
            bo_idx int(11) default NULL,
            mb_idx int(11) default '0',
            writer varchar(255) default NULL,
            comment text,
            ip varchar(255) default NULL,
            regdate datetime default NULL,
            cmt_1 text,
            cmt_2 text,
            cmt_3 text,
            cmt_4 text,
            cmt_5 text,
            cmt_6 text,
            cmt_7 text,
            cmt_8 text,
            cmt_9 text,
            cmt_10 text,
            PRIMARY KEY(idx)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ", []
        );

        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '게시판이 성공적으로 복제 되었습니다.'
            )
        );
        Valid::turn();
    }

}

/***
Regist
***/
class Regist extends \Controller\Make_Controller {

    public function init(){
        $this->layout()->mng_head();
        $this->layout()->view(MOD_BOARD_PATH.'/manage.set/html/regist.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function board_theme(){
            $tpath = PH_THEME_PATH.'/mod-'.MOD_BOARD.'/board/';
            $topen = opendir($tpath);
            $topt = '';

            while ($dir = readdir($topen)) {
                if ($dir != '.' && $dir != '..') {
                    $topt .= '<option value="'.$dir.'">'.$dir.'</option>';
                    $bd_theme[] = $dir;
                }
            }
            return $topt;
        }
    }

    public function make()
    {
        $manage = new ManageFunc();

        Func::add_javascript(PH_PLUGIN_DIR.'/'.PH_PLUGIN_CKEDITOR.'/ckeditor.js');

        $manage->make_target('게시판 기본 설정|권한 설정|아이콘 출력 설정|여분필드');

        $this->set('manage', $manage);
        $this->set('print_target', $manage->print_target());
        $this->set('board_theme', board_theme());
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'makeBoardForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/mod/'.MOD_BOARD_DIR.'/result/regist-submit');
        $form->run();
    }

}

/***
Submit for Regist
***/
class Regist_submit {

    public function init(){
        global $board_id;

        $sql = new Pdosql();
        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'id, title, theme, use_category, category, use_list, m_use_list, list_limit, m_list_limit, sbj_limit, m_sbj_limit, txt_limit, m_txt_limit, use_likes, use_reply, use_comment, use_secret, use_seek, ico_secret_def, use_file1, use_file2, use_mng_feed, file_limit, article_min_len, top_source, bottom_source, ctr_level, list_level, write_level, secret_level, comment_level, reply_level, delete_level, read_level, write_level, read_point, write_point, ico_file, ico_secret, ico_new, ico_new_case, ico_hot, ico_hot_case_1, ico_hot_case_2, ico_hot_case_3, conf_1, conf_2, conf_3, conf_4, conf_5, conf_6, conf_7, conf_8, conf_9, conf_10, conf_exp');

        $board_id = $req['id'];

        Valid::get(
            array(
                'input' => 'id',
                'value' => $req['id'],
                'check' => array(
                    'defined' => 'idx'
                )
            )
        );
        Valid::get(
            array(
                'input' => 'title',
                'value' => $req['title']
            )
        );
        Valid::get(
            array(
                'input' => 'file_limit',
                'value' => $req['file_limit'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 50
                )
            )
        );
        Valid::get(
            array(
                'input' => 'ico_new_case',
                'value' => $req['ico_new_case'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 10
                )
            )
        );
        Valid::get(
            array(
                'input' => 'ico_hot_case_1',
                'value' => $req['ico_hot_case_1'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 10
                )
            )
        );
        Valid::get(
            array(
                'input' => 'ico_hot_case_2',
                'value' => $req['ico_hot_case_2'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 10
                )
            )
        );

        if ($req['use_category'] == 'Y' && !$req['category']) {
            Valid::error('category', '카테고리 설정을 확인하세요.');
        }
        if (!$req['list_limit']) {
            $req['list_limit'] = 15;
        }
        if (!$req['m_list_limit']) {
            $req['m_list_limit'] = 10;
        }
        if (!$req['sbj_limit']) {
            $req['sbj_limit'] = 50;
        }
        if (!$req['m_sbj_limit']) {
            $req['m_sbj_limit'] = 30;
        }
        if (!$req['txt_limit']) {
            $req['txt_limit'] = 150;
        }
        if (!$req['m_txt_limit']) {
            $req['m_txt_limit'] = 100;
        }
        if (!$req['article_min_len']) {
            $req['article_min_len'] = 30;
        }
        if (!$req['read_point']) {
            $req['read_point'] = 0;
        }
        if (!$req['write_point']) {
            $req['write_point'] = 0;
        }

        $conf_exp = $sql->etcfd_exp(implode('|', $req['conf_exp']));

        $req['use_list'] = $req['use_list'].'|'.$req['m_use_list'];
        $req['list_limit'] = $req['list_limit'].'|'.$req['m_list_limit'];
        $req['sbj_limit'] = $req['sbj_limit'].'|'.$req['m_sbj_limit'];
        $req['txt_limit'] = $req['txt_limit'].'|'.$req['m_txt_limit'];
        $req['ico_hot_case'] = $req['ico_hot_case_1'].'|'.$req['ico_hot_case_3'].'|'.$req['ico_hot_case_2'];

        $sql->query(
            "
            CREATE TABLE IF NOT EXISTS {$sql->table("mod:board_data_")}$board_id (
            idx int(11) NOT NULL auto_increment,
            category varchar(255) default NULL,
            ln int(11) default '0',
            rn int(11) default '0',
            mb_idx int(11) default '0',
            mb_id varchar(255) default NULL,
            writer varchar(255) default NULL,
            pwd text,
            email varchar(255) default NULL,
            article text,
            subject varchar(255) default NULL,
            file1 text,
            file1_cnt int(11) default '0',
            file2 text,
            file2_cnt int(11) default '0',
            use_secret char(1) default 'N',
            use_notice char(1) default 'N',
            use_html char(1) default 'Y',
            use_email char(1) default 'Y',
            view int(11) default '0',
            ip varchar(255) default NULL,
            regdate datetime default NULL,
            dregdate datetime default NULL,
            data_1 text,
            data_2 text,
            data_3 text,
            data_4 text,
            data_5 text,
            data_6 text,
            data_7 text,
            data_8 text,
            data_9 text,
            data_10 text,
            PRIMARY KEY(idx)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ", []
        );

        $sql->query(
            "
            CREATE TABLE IF NOT EXISTS {$sql->table("mod:board_cmt_")}$board_id (
            idx int(11) NOT NULL auto_increment,
            ln int(11) default '0',
            rn int(11) default '0',
            bo_idx int(11) default NULL,
            mb_idx int(11) default '0',
            writer varchar(255) default NULL,
            comment text,
            ip varchar(255) default NULL,
            regdate datetime default NULL,
            cmt_1 text,
            cmt_2 text,
            cmt_3 text,
            cmt_4 text,
            cmt_5 text,
            cmt_6 text,
            cmt_7 text,
            cmt_8 text,
            cmt_9 text,
            cmt_10 text,
            PRIMARY KEY(idx)
            )ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ", []
        );

        $data = array(
            'id' => $req['id'],
            'theme' => $req['theme'],
            'title' => $req['title'],
            'use_list' => $req['use_list'],
            'use_secret' => $req['use_secret'],
            'use_seek' => $req['use_seek'],
            'use_comment' => $req['use_comment'],
            'use_likes' => $req['use_likes'],
            'use_reply' => $req['use_reply'],
            'use_file1' => $req['use_file1'],
            'use_file2' => $req['use_file2'],
            'use_mng_feed' => $req['use_mng_feed'],
            'use_category' => $req['use_category'],
            'category' => $req['category'],
            'file_limit' => $req['file_limit'],
            'list_limit' => $req['list_limit'],
            'sbj_limit' => $req['sbj_limit'],
            'txt_limit' => $req['txt_limit'],
            'article_min_len' => $req['article_min_len'],
            'list_level' => $req['list_level'],
            'write_level' => $req['write_level'],
            'secret_level' => $req['secret_level'],
            'comment_level' => $req['comment_level'],
            'delete_level' => $req['delete_level'],
            'read_level' => $req['read_level'],
            'ctr_level' => $req['ctr_level'],
            'reply_level' => $req['reply_level'],
            'write_point' => $req['write_point'],
            'read_point' => $req['read_point'],
            'top_source' => $req['top_source'],
            'bottom_source' => $req['bottom_source'],
            'ico_file' => $req['ico_file'],
            'ico_secret' => $req['ico_secret'],
            'ico_secret_def' => $req['ico_secret_def'],
            'ico_new' => $req['ico_new'],
            'ico_new_case' => $req['ico_new_case'],
            'ico_hot' => $req['ico_hot'],
            'ico_hot_case' => $req['ico_hot_case'],
            'conf_1' => $req['conf_1'],
            'conf_2' => $req['conf_2'],
            'conf_3' => $req['conf_3'],
            'conf_4' => $req['conf_4'],
            'conf_5' => $req['conf_5'],
            'conf_6' => $req['conf_6'],
            'conf_7' => $req['conf_7'],
            'conf_8' => $req['conf_8'],
            'conf_9' => $req['conf_9'],
            'conf_10' => $req['conf_10'],
            'conf_exp' => $conf_exp
        );

        foreach ($data as $key => $value) {
            $sql->query(
                "
                INSERT INTO {$sql->table("config")}
                (cfg_type,cfg_key,cfg_value,cfg_regdate)
                VALUES
                ('mod:board:config:{$req['id']}','{$key}','{$value}',now())
                ", []
            );
        }

        Valid::set(
            array(
                'return' => 'alert->location',
                'msg' => '성공적으로 추가 되었습니다.',
                'location' => PH_MANAGE_DIR.'/mod/'.MOD_BOARD.'/result/modify?id='.$req['id']
            )
        );
        Valid::turn();
    }

}

/***
Modify
***/
class Modify extends \Controller\Make_Controller {

    public function init(){
        $this->layout()->mng_head();
        $this->layout()->view(MOD_BOARD_PATH.'/manage.set/html/modify.tpl.php');
        $this->layout()->mng_foot();
    }

    public function func()
    {
        function board_theme($arr)
        {
            $tpath = PH_THEME_PATH.'/mod-'.MOD_BOARD.'/board/';
            $topen = opendir($tpath);
            $topt = '';

            while ($dir = readdir($topen)) {
                $slted = '';
                if ($dir != '.' && $dir != '..') {
                    if ($dir == $arr['theme']) {
                        $slted = 'selected';
                    }
                    $topt .= '<option value="'.$dir.'" '.$slted.'>'.$dir.'</option>';
                }
            }
            return $topt;
        }

        function set_chked($arr, $val)
        {
            $setarr = array(
                'Y' => '',
                'N' => '',
                'AND' => '',
                'OR' => ''
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

        $req = Method::request('get', 'id');

        Func::add_javascript(PH_PLUGIN_DIR.'/'.PH_PLUGIN_CKEDITOR.'/ckeditor.js');

        $manage->make_target('게시판 기본 설정|권한 설정|아이콘 출력 설정|여분필드');

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("config")}
            WHERE cfg_type='mod:board:config:{$req['id']}'
            ", []
        );

        if ($sql->getcount() < 1) {
            Func::err_back('게시판이 존재하지 않습니다.');
        }

        $arr = array();

        do {
            $sql->specialchars = 1;
            $sql->nl2br = 1;

            $cfg = $sql->fetchs();
            $arr[$cfg['cfg_key']] = $cfg['cfg_value'];

            if ($cfg['cfg_key'] == 'top_source' || $cfg['cfg_key'] == 'bottom_source') {
                $sql->specialchars = 0;
                $sql->nl2br = 0;

                $arr[$cfg['cfg_key']] = $sql->fetch('cfg_value');
            }

        } while($sql->nextRec());

        $use_list = explode('|', $arr['use_list']);
        $arr['use_list'] = $use_list[0];
        $arr['m_use_list'] = $use_list[1];

        $list_limit = explode('|', $arr['list_limit']);
        $arr['list_limit'] = $list_limit[0];
        $arr['m_list_limit'] = $list_limit[1];

        $sbj_limit = explode('|', $arr['sbj_limit']);
        $arr['sbj_limit'] = $sbj_limit[0];
        $arr['m_sbj_limit'] = $sbj_limit[1];

        $txt_limit = explode('|', $arr['txt_limit']);
        $arr['txt_limit'] = $txt_limit[0];
        $arr['m_txt_limit'] = $txt_limit[1];

        $ico_hot_case = explode('|', $arr['ico_hot_case']);
        $arr['ico_hot_case_1'] = $ico_hot_case[0];
        $arr['ico_hot_case_2'] = $ico_hot_case[2];
        $arr['ico_hot_case_3'] = $ico_hot_case[1];

        $ex = explode('|', $arr['conf_exp']);

        for ($i = 1; $i <= 10; $i++) {
            $arr['conf_'.$i.'_exp'] = $ex[$i - 1];
        }

        $write = array();

        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $write[$key] = $value;
            }

        } else {
            $write = null;
        }

        $this->set('manage', $manage);
        $this->set('write', $write);
        $this->set('print_target', $manage->print_target());
        $this->set('board_theme', board_theme($arr));
        $this->set('use_category', set_chked($arr, 'use_category'));
        $this->set('use_list', set_chked($arr, 'use_list'));
        $this->set('m_use_list', set_chked($arr, 'm_use_list'));
        $this->set('use_likes', set_chked($arr, 'use_likes'));
        $this->set('use_reply', set_chked($arr, 'use_reply'));
        $this->set('use_comment', set_chked($arr, 'use_comment'));
        $this->set('use_secret', set_chked($arr, 'use_secret'));
        $this->set('ico_secret_def', set_chked($arr, 'ico_secret_def'));
        $this->set('use_seek', set_chked($arr, 'use_seek'));
        $this->set('use_file1', set_chked($arr, 'use_file1'));
        $this->set('use_file2', set_chked($arr, 'use_file2'));
        $this->set('use_mng_feed', set_chked($arr, 'use_mng_feed'));
        $this->set('ico_file', set_chked($arr, 'ico_file'));
        $this->set('ico_secret', set_chked($arr, 'ico_secret'));
        $this->set('ico_new', set_chked($arr, 'ico_new'));
        $this->set('ico_hot', set_chked($arr, 'ico_hot'));
        $this->set('ico_hot_case_3', set_chked($arr, 'ico_hot_case_3'));
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'modifyBoardForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/mod/'.MOD_BOARD_DIR.'/result/modify-submit');
        $form->run();
    }

}

/***
Submit for Modify
***/
class Modify_submit {

    public function init()
    {
        global $req;

        $manage = new ManageFunc();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'mode, id, title, theme, use_category, category, use_list, m_use_list, list_limit, m_list_limit, sbj_limit, m_sbj_limit, txt_limit, m_txt_limit, use_likes, use_reply, use_comment, use_secret, ico_secret_def, use_seek, use_file1, use_file2, use_mng_feed, file_limit, article_min_len, top_source, bottom_source, ctr_level, list_level, write_level, secret_level, comment_level, reply_level, delete_level, read_level, write_level, read_point, write_point, ico_file, ico_secret, ico_new, ico_new_case, ico_hot, ico_hot_case_1, ico_hot_case_2, ico_hot_case_3, conf_1, conf_2, conf_3, conf_4, conf_5, conf_6, conf_7, conf_8, conf_9, conf_10, conf_exp');
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
        global $req;

        $sql = new Pdosql();

        Valid::get(
            array(
                'input' => 'title',
                'value' => $req['title']
            )
        );
        Valid::get(
            array(
                'input' => 'file_limit',
                'value' => $req['file_limit'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 50
                )
            )
        );
        Valid::get(
            array(
                'input' => 'ico_new_case',
                'value' => $req['ico_new_case'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 10
                )
            )
        );
        Valid::get(
            array(
                'input' => 'ico_hot_case_1',
                'value' => $req['ico_hot_case_1'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 10
                )
            )
        );
        Valid::get(
            array(
                'input' => 'ico_hot_case_2',
                'value' => $req['ico_hot_case_2'],
                'check' => array(
                    'charset' => 'number',
                    'maxlen' => 10
                )
            )
        );

        if ($req['use_category'] == 'Y' && !$req['category']) {
            Valid::error('category', '카테고리 설정을 확인하세요.');
        }
        if (!$req['list_limit']) {
            $req['list_limit'] = 15;
        }
        if (!$req['m_list_limit']) {
            $req['m_list_limit'] = 10;
        }
        if (!$req['sbj_limit']) {
            $req['sbj_limit'] = 50;
        }
        if (!$req['m_sbj_limit']) {
            $req['m_sbj_limit'] = 30;
        }
        if (!$req['txt_limit']) {
            $req['txt_limit'] = 150;
        }
        if (!$req['m_txt_limit']) {
            $req['m_txt_limit'] = 100;
        }
        if (!$req['article_min_len']) {
            $req['article_min_len'] = 30;
        }
        if (!$req['read_point']) {
            $req['read_point'] = 0;
        }
        if (!$req['write_point']) {
            $req['write_point'] = 0;
        }

        $conf_exp = $sql->etcfd_exp(implode('|', $req['conf_exp']));

        $req['use_list'] = $req['use_list'].'|'.$req['m_use_list'];
        $req['list_limit'] = $req['list_limit'].'|'.$req['m_list_limit'];
        $req['sbj_limit'] = $req['sbj_limit'].'|'.$req['m_sbj_limit'];
        $req['txt_limit'] = $req['txt_limit'].'|'.$req['m_txt_limit'];
        $req['ico_hot_case'] = $req['ico_hot_case_1'].'|'.$req['ico_hot_case_3'].'|'.$req['ico_hot_case_2'];

        $data = array(
            'theme' => $req['theme'],
            'title' => $req['title'],
            'use_list' => $req['use_list'],
            'use_secret' => $req['use_secret'],
            'use_seek' => $req['use_seek'],
            'use_comment' => $req['use_comment'],
            'use_likes' => $req['use_likes'],
            'use_reply' => $req['use_reply'],
            'use_file1' => $req['use_file1'],
            'use_file2' => $req['use_file2'],
            'use_mng_feed' => $req['use_mng_feed'],
            'use_category' => $req['use_category'],
            'category' => $req['category'],
            'file_limit' => $req['file_limit'],
            'list_limit' => $req['list_limit'],
            'sbj_limit' => $req['sbj_limit'],
            'txt_limit' => $req['txt_limit'],
            'article_min_len' => $req['article_min_len'],
            'list_level' => $req['list_level'],
            'write_level' => $req['write_level'],
            'secret_level' => $req['secret_level'],
            'comment_level' => $req['comment_level'],
            'delete_level' => $req['delete_level'],
            'read_level' => $req['read_level'],
            'ctr_level' => $req['ctr_level'],
            'reply_level' => $req['reply_level'],
            'write_point' => $req['write_point'],
            'read_point' => $req['read_point'],
            'top_source' => $req['top_source'],
            'bottom_source' => $req['bottom_source'],
            'ico_file' => $req['ico_file'],
            'ico_secret' => $req['ico_secret'],
            'ico_secret_def' => $req['ico_secret_def'],
            'ico_new' => $req['ico_new'],
            'ico_new_case' => $req['ico_new_case'],
            'ico_hot' => $req['ico_hot'],
            'ico_hot_case' => $req['ico_hot_case'],
            'conf_1' => $req['conf_1'],
            'conf_2' => $req['conf_2'],
            'conf_3' => $req['conf_3'],
            'conf_4' => $req['conf_4'],
            'conf_5' => $req['conf_5'],
            'conf_6' => $req['conf_6'],
            'conf_7' => $req['conf_7'],
            'conf_8' => $req['conf_8'],
            'conf_9' => $req['conf_9'],
            'conf_10' => $req['conf_10'],
            'conf_exp' => $conf_exp
        );

        foreach ($data as $key => $value) {
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("config")}
                WHERE cfg_type='mod:board:config:{$req['id']}' AND cfg_key='{$key}'
                ",
                array(
                    $value
                )
            );
            if ($sql->getcount() < 1) {
                $sql->query(
                    "
                    INSERT INTO
                    {$sql->table("config")}
                    (cfg_type,cfg_key)
                    VALUES
                    ('mod:board:config:{$req['id']}','{$key}')
                    ",
                    array(
                        $value
                    )
                );
            }
            $sql->query(
                "
                UPDATE
                {$sql->table("config")}
                SET
                cfg_value=:col1
                WHERE cfg_type='mod:board:config:{$req['id']}' AND cfg_key='{$key}'
                ",
                array(
                    $value
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
        global $req, $board_id;

        $sql = new Pdosql();
        $uploader = new Uploader();
        $manage = new ManageFunc();

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("config")}
            WHERE cfg_type='mod:board:config:{$req['id']}' AND cfg_key='id' AND cfg_value='{$req['id']}'
            ", []
        );

        $board_id = $sql->fetch('cfg_value');

        if ($sql->getcount() < 1) {
            Valid::error('', '게시판이 존재하지 않습니다.');
        }

        $sql->query(
            "
            DELETE
            FROM {$sql->table("config")}
            WHERE cfg_type='mod:board:config:{$board_id}'
            ", []
        );

        $sql->query(
            "
            DROP TABLE {$sql->table("mod:board_data_")}$board_id
            ", []
        );

        $sql->query(
            "
            DROP TABLE {$sql->table("mod:board_cmt_")}$board_id
            ", []
        );

        $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id.'/thumb';
        $uploader->dropdir();
        $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id;
        $uploader->dropdir();

        Valid::set(
            array(
                'return' => 'alert->location',
                'msg' => '성공적으로 삭제 되었습니다.',
                'location' => PH_MANAGE_DIR.'/mod/'.MOD_BOARD.'/result/result'.$manage->retlink('')
            )
        );
        Valid::turn();
    }

}
