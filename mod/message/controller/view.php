<?php
namespace Module\Message;

use Corelib\Func;
use Corelib\Method;
use Make\Database\Pdosql;

/***
View
***/
class View extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->view(MOD_MESSAGE_THEME_PATH.'/view.tpl.php');
    }

    public function make()
    {
        $sql = new Pdosql();

        $req = Method::request('get', 'refmode, idx, page');

        Func::getlogin(SET_NOAUTH_MSG);

        //메시지 본문
        $sql->query(
            "
            SELECT message.*,
            fmember.mb_name AS f_mb_name,fmember.mb_id AS f_mb_id,
            tmember.mb_name AS t_mb_name,tmember.mb_id AS t_mb_id
            FROM {$sql->table("mod:message")} AS message
            LEFT OUTER JOIN
            {$sql->table("member")} AS fmember
            ON message.from_mb_idx=fmember.mb_idx
            LEFT OUTER JOIN
            {$sql->table("member")} AS tmember
            ON message.to_mb_idx=tmember.mb_idx
            WHERE message.idx=:col1 AND (message.to_mb_idx=:col2 OR message.from_mb_idx=:col2)
            ORDER BY message.regdate DESC
            ",
            array(
                $req['idx'], MB_IDX
            )
        );

        if ($sql->getcount() < 1) {
            Func::err_back('메시지가 존재하지 않습니다.');
        }

        $arr = $sql->fetchs();

        $arr['regdate'] = Func::datetime($arr['regdate']);
        $arr[0]['list-link'] = '?mode='.$req['refmode'].'&page='.$req['page'];
        $arr[0]['reply-link'] = '?mode=send&reply='.$req['idx'];

        //메시지 읽음 처리
        $chked_date = date('Y.m.d H:i:s');

        if (!$arr['chked'] && $arr['to_mb_idx'] == MB_IDX) {
            $sql->query(
                "
                UPDATE {$sql->table("mod:message")}
                SET chked=:col3
                WHERE idx=:col1 AND to_mb_idx=:col2
                ",
                array(
                    $req['idx'], MB_IDX, $chked_date
                )
            );
            $arr['chked'] = $chked_date;

        } else {
            $arr['chked'] = Func::datetime($arr['chked']);

        }

        //메시지 history
        $sql->query(
            "
            SELECT message.*,member.mb_name,member.mb_id
            FROM {$sql->table("mod:message")} AS message
            LEFT OUTER JOIN
            {$sql->table("member")} AS member
            ON message.from_mb_idx=member.mb_idx
            WHERE message.parent_idx=:col1 AND message.regdate < :col2 AND message.idx!=:col3
            ORDER BY message.regdate DESC
            ",
            array(
                $arr['parent_idx'], $arr['regdate'], $arr['idx']
            )
        );

        $history_arr = array();

        if ($sql->getcount() > 0) {
            do {
                $hisarr = $sql->fetchs();
                $hisarr['regdate'] = Func::datetime($hisarr['regdate']);

                $history_arr[] = $hisarr;

            } while($sql->nextRec());
        }

        $this->set('view', $arr);
        $this->set('history_arr', $history_arr);
        $this->set('from_mb_id', $arr['f_mb_id']);
        $this->set('reply_parent_idx', $arr['parent_idx']);
        $this->set('refmode', $req['refmode']);
    }

    public function message_tab()
    {
        $fetch = new \Controller\Make_View_Fetch();
        $fetch->set('doc', MOD_MESSAGE_PATH.'/controller/message.tab.inc.php');
        $fetch->set('className', 'Module\Message\message_tab_inc');
        $fetch->run();
    }

}
