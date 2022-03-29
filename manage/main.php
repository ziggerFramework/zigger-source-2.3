<?php
use Corelib\Method;
use Corelib\Func;
use Make\Database\Pdosql;
use Make\Library\Paging;
use Manage\Func as Manage;

define('MAINPAGE',true);

class Dash extends \Controller\Make_Controller {

    public function init(){
        $this->layout()->mng_head();
        $this->layout()->view(PH_MANAGE_PATH.'/html/main.tpl.php');
        $this->layout()->mng_foot();
    }

    public function make(){
        $req = Method::request('get', 'view_dash_feed, page');

        $sql = new Pdosql();
        $paging = new Paging();

        $list_cnt = array();
        $print_arr = array();
        $pagingprint = array();

        //new member
        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_adm!='Y' AND mb_dregdate IS NULL
            ORDER BY mb_regdate DESC
            LIMIT 5
            ", []
        );
        $list_cnt['new_mb'] = $sql->getcount();

        $print_arr['new_mb'] = array();

        if ($list_cnt['new_mb'] > 0) {
            do {
                $arr = $sql->fetchs();
                $arr['mb_regdate'] = Func::datetime($arr['mb_regdate']);
                $arr['mb_id'] = Func::strcut($arr['mb_id'], 0, 20);
                $print_arr['new_mb'][] = $arr;
            } while ($sql->nextRec());
        }

        //visit member
        $sql->query(
            "
            SELECT visit.*,IFNULL(member.mb_level,10) mb_level
            FROM {$sql->table("visitcount")} visit
            LEFT OUTER JOIN {$sql->table("member")} member
            ON visit.mb_idx=member.mb_idx
            ORDER BY regdate DESC
            LIMIT 5
            ", []
        );
        $list_cnt['visit_mb'] = $sql->getcount();

        $print_arr['visit_mb'] = array();

        if ($list_cnt['visit_mb'] > 0) {
            do {
                $arr = $sql->fetchs();
                if(!$arr['mb_id']){
                    $arr['mb_id'] = '비회원';
                    $arr['regdate'] = Func::datetime($arr['regdate']);
                } else {
                    $arr['mb_id'] = Func::strcut($arr['mb_id'], 0, 20);
                }
                $print_arr['visit_mb'][] = $arr;
            } while ($sql->nextRec());
        }

        //session member
        $sql->query(
            "
            SELECT sess.*,member.*,IFNULL(member.mb_level,10) mb_level
            FROM {$sql->table("session")} sess
            LEFT OUTER JOIN
            {$sql->table("member")} member
            ON sess.mb_idx=member.mb_idx
            WHERE regdate>=DATE_SUB(now(),interval 10 minute)
            ORDER BY regdate DESC
            ", []
        );
        $list_cnt['stat_mb'] = $sql->getcount();

        $print_arr['stat_mb'] = array();

        if ($list_cnt['stat_mb'] > 0) {
            do {
                $arr = $sql->fetchs();
                if (!$arr['mb_id']) {
                    $arr['mb_id'] = '비회원';
                    $arr['regdate'] = Func::datetime($arr['regdate']);
                } else {
                    $arr['mb_id'] = Func::strcut($arr['mb_id'], 0, 20);
                }
                $print_arr['stat_mb'][] = $arr;
            } while ($sql->nextRec());
        }

        //manage feeds
        if (isset($req['view_dash_feed'])) {
            if ($req['view_dash_feed'] == 'read_all') {

                $sql->query(
                    "
                    UPDATE {$sql->table("mng_feeds")}
                    SET chked='Y'
                    WHERE chked='N'
                    ", []
                );

            } else {

                $sql->query(
                    "
                    UPDATE {$sql->table("mng_feeds")}
                    SET chked='Y'
                    WHERE idx=:col1 AND chked='N'
                    ",
                    array(
                        $req['view_dash_feed']
                    )
                );

            }

        }

        $no_chked = 0;
        $paging->setlimit(20);

        $sql->query(
            $paging->query(
                "
                SELECT *,
                (
                    SELECT COUNT(*)
                    FROM {$sql->table("mng_feeds")}
                    WHERE chked='N'
                ) AS total
                FROM {$sql->table("mng_feeds")}
                ORDER BY regdate DESC
                ", []
            )
        );
        $list_cnt['mng_feed'] = $sql->getcount();
        $news_newfeeds_count = Func::number($sql->fetch('total'));
        $total_cnt = Func::number($paging->totalCount);
        $print_arr['mng_feed'] = array();

        if ($list_cnt['mng_feed'] > 0) {
            do {
                $arr = $sql->fetchs();
                $sql->specialchars = 0;
                $sql->nl2br = 0;
                $arr['memo'] = $sql->fetch('memo');
                $arr['regdate'] = Func::datetime($arr['regdate']);

                if ($arr['chked'] == 'N') {
                    $no_chked++;
                }
                $print_arr['mng_feed'][] = $arr;

            } while ($sql->nextRec());
        }

        $pagingprint['mng_feed'] = $paging->pagingprint('');

        $this->set('print_arr', $print_arr);
        $this->set('list_cnt', $list_cnt);
        $this->set('pagingprint', $pagingprint);
        $this->set('news_newfeeds_count', $news_newfeeds_count);
        $this->set('page', $req['page']);
    }

}
