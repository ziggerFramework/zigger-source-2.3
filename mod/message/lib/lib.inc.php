<?php
namespace Module\Message;

use Make\Database\Pdosql;

///
// Module : Message Library
///

class Library {

    //새로운 메시지 카운팅
    public function get_new_count()
    {
        $sql = new Pdosql();

        $total_count = 0;
        if (IS_MEMBER) {
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:message")}
                WHERE to_mb_idx=:col1 AND chked IS NULL
                ",
                array(
                    MB_IDX
                )
            );
            $total_count = $sql->getcount();

        }

        return $total_count;

    }

}
