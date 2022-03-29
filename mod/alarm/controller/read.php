<?php
namespace Module\Alarm;

use Corelib\Func;
use Corelib\Method;
use Make\Database\Pdosql;
use Module\Alarm\Library as Alarm_Library;

/***
Read
***/
class Read extends \Controller\Make_Controller {

    public function init()
    {
        $this->layout()->view('');
    }

    public function make()
    {
        $sql = new Pdosql();

        $req = Method::request('get', 'alarm, allcheck, page');

        Func::getlogin(SET_NOAUTH_MSG);

        //전체 읽음 처리
        if ($req['allcheck'] == 1) {
            $sql->query(
                "
                UPDATE
                {$sql->table("mod:alarm")} SET
                chked='Y'
                WHERE to_mb_idx=:col1
                ",
                array(
                    MB_IDX
                )
            );

            Func::location('?page='.$req['page']);
        }

        //단일 읽음 처리
        else {
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:alarm")}
                WHERE to_mb_idx=:col1 AND idx=:col2
                ",
                array(
                    MB_IDX, $req['alarm']
                )
            );

            if ($sql->getcount() < 1) {
                Func::err_back('알림이 존재하지 않습니다.');
            }

            $arr = $sql->fetchs();

            $sql->specialchars = 0;
            $arr['href'] = $sql->fetch('href');

            $sql->query(
                "
                UPDATE
                {$sql->table("mod:alarm")} SET
                chked='Y'
                WHERE to_mb_idx=:col1 AND idx=:col2
                ",
                array(
                    MB_IDX, $req['alarm']
                )
            );

            Func::location(PH_DIR.$arr['href']);
        }
    }

}
