<?php
namespace Corelib;

use Corelib\Session;
use Corelib\Func;
use Make\Database\Pdosql;

class Statistic {

    static public function rec_visitcount()
    {
        global $MB;

        $sql = new Pdosql();

        if (!Session::is_sess('VISIT_MB_IDX') || Session::sess('VISIT_MB_IDX') != $MB['idx']) {

            $device = Func::chkdevice();

            $sql->query(
                "
                select count(*) as visit_count
                from {$sql->table("visitcount")}
                where ip=:col1 and regdate>=date_sub(now(),interval 1 hour)
                ",
                array(
                    $_SERVER['REMOTE_ADDR']
                )
            );

            if ($sql->fetch('visit_count') < 1) {
                $sql->query(
                    "
                    insert into {$sql->table("visitcount")}
                    (mb_idx, mb_id, ip, device, browser, regdate)
                    values
                    (:col1, :col2, :col3, :col4, :col5, now())
                    ",
                    array(
                        $MB['idx'],
                        $MB['id'],
                        $_SERVER['REMOTE_ADDR'],
                        $device,
                        $_SERVER['HTTP_USER_AGENT']
                    ), false
                );

            }

            else if ($MB['idx'] != $sql->fetch('mb_idx') && $sql->fetch('mb_idx') != '') {

                $sql->query(
                    "
                    update {$sql->table("visitcount")}
                    set mb_idx=:col2, mb_id=:col3, device=:col4, browser=:col5
                    where ip=:col1
                    order by regdate desc
                    limit 1
                    ",
                    array(
                        $_SERVER['REMOTE_ADDR'],
                        $MB['idx'],
                        $MB['id'],
                        $device,
                        $_SERVER['HTTP_USER_AGENT']
                    )
                );

            }

            Session::set_sess('VISIT_MB_IDX', $MB['idx']);
        }
    }

}

Statistic::rec_visitcount();
