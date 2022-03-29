<?php
namespace Module\Alarm;

use Make\Database\Pdosql;

///
// Module : Alarm Library
///

class Library {

    //새로운 알림 카운팅
    public function get_new_count()
    {
        $sql = new Pdosql();

        $total_count = 0;
        if (IS_MEMBER) {
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:alarm")}
                WHERE to_mb_idx=:col1 AND chked='N'
                ",
                array(
                    MB_IDX
                )
            );
            $total_count = $sql->getcount();

        }

        return $total_count;

    }

    //새로운 알림 등록
    public function get_add_alarm($arr)
    {
        $sql = new Pdosql();

        $sql->query(
            "
            INSERT INTO
            {$sql->table("mod:alarm")}
            (msg_from, from_mb_idx, to_mb_idx, href, memo, regdate)
            VALUES
            (:col1, :col2, :col3, :col4, :col5, now())
            ",
            array(
                $arr['msg_from'], $arr['from_mb_idx'], $arr['to_mb_idx'], $arr['link'], $arr['memo']
            )
        );
    }

}
