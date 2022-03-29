<?php
use Corelib\Func;
use Corelib\Method;
use Corelib\Valid;
use Make\Database\Pdosql;
use Manage\ManageFunc;

/***
Result
***/
class Result extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->mng_head();
        $this->layout()->view(MOD_SEARCH_PATH.'/manage.set/html/result.tpl.php');
        $this->layout()->mng_foot();
    }

    public function make()
    {

    }

    public function form()
    {

        $form = new \Controller\Make_View_Form();
        $form->set('id', 'searchListForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/mod/'.MOD_SEARCH.'/result/searchList-submit');
        $form->run();
    }

    public function form2()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'searchModifyForm');
        $form->set('type', 'html');
        $form->set('action', PH_MANAGE_DIR.'/mod/'.MOD_SEARCH.'/result/searchModify-submit');
        $form->run();
    }

}

/***
Search List
***/
class SearchList extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->view(MOD_SEARCH_PATH.'/manage.set/html/searchList.tpl.php');
    }

    public function make()
    {
        $sql = new Pdosql();
        $sql2 = new Pdosql();
        $sql3 = new Pdosql();

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:search")}
            WHERE CHAR_LENGTH(caidx)=4
            ORDER BY caidx ASC
            ", []
        );
        $list_cnt = $sql->getcount();

        $print_arr = array();

        if ($list_cnt > 0) {
            do {

                $arr = $sql->fetchs();
                $print_arr[] = $arr;

            } while ($sql->nextRec());
        }

        $this->set('print_arr', $print_arr);

    }

}

/***
Submit for Search List
***/
class SearchList_submit{

    public function init()
    {
        global $req;

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'type, idx, org_caidx, caidx, new_caidx');

        switch ($req['type']) {
            case 'add' :
                $this->get_add();
                break;

            case 'modify' :
                $this->get_modify();
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

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:search")}
            ORDER BY idx DESC
            LIMIT 1
            ", []
        );

        $recent_idx = $sql->fetch('idx');

        if ($recent_idx) {
            $recent_idx++;

        } else {
            $recent_idx = 1;
        }

        $sql->query(
            "
            INSERT INTO
            {$sql->table("mod:search")}
            (idx,caidx,title,children)
            VALUES
            (:col1,:col2,:col3,:col4)
            ",
            array(
                $recent_idx,
                $req['new_caidx'],
                '새로운 통합검색 콘텐츠',
                0
            )
        );

        Valid::set(
            array(
                'return' => 'callback',
                'function' => 'mod_searchResult.action(\'list_reload\');'
            )
        );
        Valid::turn();
    }

    ///
    // modify
    ///
    public function get_modify()
    {
        global $req, $where;

        $sql = new Pdosql();

        $where = '';

        if (count($req['idx']) < 1) {
            $where = 'idx!=-1';

        } else {
            for ($i = 0; $i < count($req['idx']); $i++) {
                if ($i == 0) {
                    $where .= 'idx!=\''.$req['idx'][$i].'\'';

                } else {
                    $where .= ' AND idx!=\''.$req['idx'][$i].'\'';
                }
            }
        }

        $sql->query(
            "
            DELETE
            FROM {$sql->table("mod:search")}
            WHERE $where
            ", []
        );

        $children_count = array();

        for ($i = 0; $i < count($req['idx']); $i++) {
            $sql->query(
                "
                SELECT COUNT(*) count
                FROM {$sql->table("mod:search")}
                WHERE caidx LIKE :col1
                ",
                array(
                    $req['org_caidx'][$i].'%'
                )
            );
            $children_count[$i] = $sql->fetch('count') - 1;
        }

        for ($i = 0; $i < count($req['idx']); $i++) {
            $sql->query(
                "
                UPDATE {$sql->table("mod:search")}
                SET
                caidx=:col1,children=:col2
                WHERE idx=:col3
                ",
                array(
                    $req['caidx'][$i],
                    $children_count[$i],
                    $req['idx'][$i]
                )
            );
        }

        Valid::set(
            array(
                'return' => 'callback',
                'function' => 'mod_searchResult.action(\'list_reload\');'
            )
        );
        Valid::turn();
    }
}

/***
Search Modify
***/
class SearchModify extends \Controller\Make_Controller {

    public function init(){
        $this->layout()->view(MOD_SEARCH_PATH.'/manage.set/html/searchModify.tpl.php');
    }

    public function func()
    {
        function get_modules()
        {
            $sql = new Pdosql();
            $sltarr = array();

            //baord
            $sql->query(
                "
                SELECT board.cfg_value as board_id, board_title.cfg_value as board_title
                FROM {$sql->table("config")} board
                LEFT OUTER JOIN {$sql->table("config")} board_title
                ON board.cfg_type=board_title.cfg_type AND board_title.cfg_key='title'
                WHERE board.cfg_type like 'mod:board:config:%' AND board.cfg_key='id'
                ORDER BY board.cfg_value ASC;
                ", []
            );

            do {
                $arr['type'] = 'board';
                $arr['type-txt'] = '게시판';
                $arr['title'] = $sql->fetch('board_title');
                $arr['id'] = $sql->fetch('board_id');
                $arr['option-txt'] = $arr['type-txt'].'모듈 - '.$arr['title'].' ('.$arr['id'].')';

                $sltarr[] = $arr;

            } while($sql->nextRec());

            //contents
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:contents")}
                ORDER BY data_key ASC
                ", []
            );

            do {
                $arr['type'] = 'contents';
                $arr['type-txt'] = '콘텐츠';
                $arr['title'] = $sql->fetch('title');
                $arr['id'] = $sql->fetch('data_key');
                $arr['option-txt'] = $arr['type-txt'].'모듈 - '.$arr['title'].' ('.$arr['id'].')';

                $sltarr[] = $arr;

            } while($sql->nextRec());

            return $sltarr;
        }
    }

    public function make()
    {
        $req = Method::request('get', 'idx');

        $sql = new Pdosql();

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:search")}
            WHERE idx=:col1
            ",
            array(
                $req['idx']
            )
        );
        $arr = $sql->fetchs();

        $arr[0]['module'] = '';
        $arr[0]['limit'] = '';

        if ($arr['opt']) {
            $opt_exp = explode('|', $arr['opt']);
            $arr[0]['module'] = $opt_exp[0].'|'.$opt_exp[1];
            $arr[0]['limit'] = $opt_exp[2];
        }

        $write = array();

        if (isset($arr)) {
            foreach ($arr as $key => $value) {
                $write[$key] = $value;
            }

        } else {
            $write = null;
        }

        $this->set('write', $write);
        $this->set('get_modules', get_modules());
    }
}

/***
Submit for Search Modify
***/
class SearchModify_submit{

    public function init()
    {
        $manage = new ManageFunc();
        $sql = new Pdosql();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'idx, title, href, module, limit');

        Valid::get(
            array(
                'input' => 'title',
                'value' => $req['title']
            )
        );
        Valid::get(
            array(
                'input' => 'href',
                'value' => $req['href']
            )
        );
        Valid::get(
            array(
                'input' => 'module',
                'value' => $req['module'],
                'check' => array(
                    'selected' => true
                )
            )
        );

        $sql->query(
            "
            UPDATE {$sql->table("mod:search")}
            SET
            title=:col1,href=:col2,opt=:col3
            WHERE idx=:col4
            ",
            array(
                $req['title'],
                $req['href'],
                $req['module'].'|'.$req['limit'],
                $req['idx']
            )
        );

        Valid::set(
            array(
                'return' => 'callback',
                'function' => 'mod_searchResult.action(\'secc_modify\');'
            )
        );
        Valid::turn();
    }

}
