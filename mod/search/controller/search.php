<?php
namespace Module\Search;

use Corelib\Func;
use Corelib\Method;
use Make\Database\Pdosql;

class Search extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->view(MOD_SEARCH_THEME_PATH.'/search.tpl.php');
    }

    public function func()
    {
        function exp_keywords($keyword) {
            $exp = explode(' ', $keyword);

            $key_arr = array();

            for ($i = 0; $i < count($exp); $i++) {
                $key_arr[$i] = $exp[$i];
            }

            return $key_arr;
        }

        function get_module_contents($keyword)
        {
            $sql = new Pdosql();

            $where = '';

            foreach (exp_keywords($keyword) as $key => $value) {
                $or = '';
                if ($key > 0) {
                    $or = ' OR ';
                }
                $where .= $or.'html like \'%'.$value.'%\'';
            }

            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:contents")}
                WHERE ({$where})
                ", []
            );

            $return = NULL;

            if ($sql->getcount() > 0) {
                $arr = array();
                do {
                    $sql->specialchars = 0;

                    $arr['article'] = Func::strcut(strip_tags($sql->fetch('html')), 0, 250);
                    $arr['link'] = '';

                    $return[] = $arr;

                } while($sql->nextRec());

            }

            return $return;
        }

        function get_module_board($keyword, $board, $limit)
        {
            $sql = new Pdosql();

            $where = array(
                0 => null,
                1 => null,
                2 => null
            );

            if (!$sql->table_exists('mod:board_data_'.$board)) {
                return null;
            }

            foreach (exp_keywords($keyword) as $key => $value) {
                $or = '';
                if ($key > 0) {
                    $or = ' OR ';
                }
                $where[0] .= $or.' subject like \'%'.$value.'%\'';
                $where[1] .= $or.' article like \'%'.$value.'%\'';
                $where[2] .= $or.' writer like \'%'.$value.'%\'';
            }

            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:board_data_{$board}")}
                WHERE ({$where[0]}) OR ({$where[1]}) OR ({$where[2]}) AND dregdate IS NULL
                ORDER BY regdate DESC
                LIMIT 0, {$limit}
                ", []
            );

            $return = NULL;

            if ($sql->getcount() > 0) {
                do {
                    $arr['subject'] = $sql->fetch('subject');
                    $arr['link'] = '/'.$sql->fetch('idx');
                    $arr['info']['writer'] = $sql->fetch('writer');
                    $arr['info']['regdate'] = Func::date($sql->fetch('regdate'));

                    $sql->specialchars = 0;
                    $arr['article'] = Func::strcut(strip_tags($sql->fetch('article')), 0, 250);

                    $return[] = $arr;

                } while($sql->nextRec());

            }

            return $return;
        }
    }

    public function make()
    {
        $sql = new Pdosql();

        $req = Method::request('get', 'keyword');

        $req['keyword'] = trim(urldecode($req['keyword']));

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:search")}
            WHERE opt IS NOT NULL AND href IS NOT NULL
            ORDER BY caidx ASC
            ", []
        );

        $print_arr = array();

        if ($sql->getcount() > 0) {
            do {
                $arr = $sql->fetchs();

                $mod_arr = array();
                $opt_exp = explode('|', $arr['opt']);

                //module type
                $mod_arr['modue'] = $opt_exp[0];
                $mod_arr['title'] = $arr['title'];

                //더보기 링크 생성
                $mod_arr['href'] = PH_DOMAIN.'/'.$arr['href'];
                $mod_arr[0]['href'] = PH_DOMAIN.'/'.$arr['href'];

                if ($opt_exp[0] == 'board') {
                    $mod_arr[0]['href'] = $mod_arr['href'].'?keyword='.urlencode($req['keyword']);
                }

                //모듈별 Database 처리
                switch ($opt_exp[0]) {
                    case 'contents' :
                        $mod_arr['data'] = get_module_contents($req['keyword']);
                        break;

                    case 'board' :
                        $mod_arr['data'] = get_module_board($req['keyword'], $opt_exp[1], $opt_exp[2]);
                        break;
                }

                $print_arr[] = $mod_arr;

            } while($sql->nextRec());

        }

        $this->set('keyword', $req['keyword']);
        $this->set('print_arr', $print_arr);

    }

}
