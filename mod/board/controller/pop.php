<?php
namespace Module\Board;

use Corelib\Method;
use Corelib\Valid;
use Corelib\Func;
use Make\Database\Pdosql;
use Make\Library\Uploader;
use Module\Board\Library as Board_Library;

/***
Control
***/
class Ctrl extends \Controller\Make_Controller {

    public function init(){
        global $req, $boardconf;

        $this->layout()->view('');

        if ($req['request'] == 'manage') {
            $this->layout()->view(MOD_BOARD_PATH.'/manage.set/html/ctrpop.tpl.php', false);
        } else {
            $this->layout()->view(MOD_BOARD_THEME_PATH.'/board/'.$boardconf['theme'].'/ctrpop.tpl.php', false);
        }
    }

    public function func()
    {
        //게시판 목록
        function board_opt_list()
        {
            global $req;

            $sql = new Pdosql();

            $sql->query(
                "
                SELECT config.*,board_name_tbl.cfg_value AS board_name
                FROM {$sql->table("config")} config
                LEFT OUTER JOIN {$sql->table("config")} board_name_tbl
                ON config.cfg_type=board_name_tbl.cfg_type AND board_name_tbl.cfg_key='title'
                WHERE config.cfg_type like 'mod:board:config:%' AND config.cfg_key='id'
                ", []
            );

            $opt = '';

            do {
                $arr = $sql->fetchs();

                $opt_slted = '';
                if ($req['board_id'] == $arr['cfg_value']) {
                    $opt_slted = 'selected';
                }
                $opt .= '<option value="'.$arr['cfg_value'].'" '.$opt_slted.'>'.$arr['board_name'].'('.$arr['cfg_value'].')</option>';

            } while ($sql->nextRec());

            return $opt;
        }
    }

    public function make()
    {
        global $req, $boardconf;

        $boardlib = new Board_Library();

        $req = Method::request('post', 'cnum, board_id, page, category, where, keyword, sort, ordtg, ordsc, request');
        $boardconf = $boardlib->load_conf($req['board_id']);


        for ($i = 0; $i < sizeof($req['cnum']); $i++) {
            if (!isset($cnum_arr)) {
                $cnum_arr = $req['cnum'][$i];
            } else {
                $cnum_arr .= ','.$req['cnum'][$i];
            }
        }

        $this->set('req', $req);
        $this->set('slt_count', sizeof($req['cnum']));
        $this->set('board_opt_list', board_opt_list());
        $this->set('cnum_arr', $cnum_arr);
        $this->set('board_id', $req['board_id']);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'board_ctrpopForm');
        $form->set('type', 'html');
        $form->set('action', MOD_BOARD_DIR.'/controller/pop/ctrl-submit');
        $form->run();
    }

}

/***
Submit for Control
***/
class Ctrl_submit {

    public function init()
    {
        global $MB, $boardconf, $req, $cnum, $board_id, $t_board_id;

        $boardlib = new Board_Library();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'cnum, type, board_id, t_board_id, category, page, where, keyword, thisuri, request, sort, ordtg, ordsc, request');

        $board_id = $req['board_id'];
        $t_board_id = $req['t_board_id'];

        //load config
        $boardconf = $boardlib->load_conf($board_id);

        //관리 권한 검사
        if ($MB['level'] > $boardconf['ctr_level']) {
            Valid::error('', '글을 관리할 권한이 없습니다.');
        }

        //게시물 번호 분리
        $cnum = explode(',', $req['cnum']);
        $cnum = array_reverse($cnum);

        switch ($req['type']) {

            case 'del' :
                $this->get_del();
                break;

            case 'move' :
                $this->get_move();
                break;

            case 'copy' :
                $this->get_copy();
                break;

        }
    }


    ///
    // 게시물 삭제
    ///
    private function get_del()
    {
        global $CONF, $cnum, $req, $board_id, $del_where_sum;

        $uploader = new Uploader();
        $sql = new Pdosql();
        $sql2 = new Pdosql();

        $del_where = array();

        for ($i = 0; $i < count($cnum); $i++) {

            if ($cnum[$i] != '') {

                //원글 게시물 정보
                $sql->query(
                    "
                    SELECT *
                    FROM {$sql->table("mod:board_data_".$board_id)}
                    WHERE idx=:col1
                    ",
                    array(
                        $cnum[$i]
                    )
                );
                $org_arr = $sql->fetchs();

                //최소/최대 ln값 구함
                $ln_min = (int)(ceil($org_arr['ln'] / 1000) * 1000) - 1000;
                $ln_max = (int)(ceil($org_arr['ln'] / 1000) * 1000);

                //부모글인 경우 범위 조건문 구함
                if ($org_arr['rn'] == 0) {
                    $del_where[$i] = '(ln>'.$ln_min.' AND ln<='.$ln_max.')';
                }

                //자식글(답글)인 경우 범위 조건문 구함
                if ($org_arr['rn'] >= 1) {
                    $sql->query(
                        "
                        SELECT MAX(ln)+1000 AS ln_max
                        FROM {$sql->table("mod:board_data_".$board_id)}
                        ",
                        array(
                            $ln_min, $org_arr['ln'], $org_arr['rn']
                        )
                    );
                    $tar_ln = $sql->fetch('ln');

                    if ($tar_ln == '') {
                        $del_where[$i] = '(ln<='.$org_arr['ln'].' AND ln>'.$ln_min.' AND rn>='.$org_arr['rn'].')';

                    } else {
                        $del_where[$i] = '(ln<='.$org_arr['ln'].' AND ln>'.$tar_ln.' AND rn>='.$org_arr['rn'].')';
                    }
                }
            }
        }

        //삭제 범위 조건문을 하나의 구문으로 합침
        for ($i = 0; $i < count($del_where); $i++) {
            if ($i == 0) {
                $del_where_sum = $del_where[$i];

            } else {
                $del_where_sum .= ' OR '.$del_where[$i];
            }
        }

        //삭제 범위 내 게시물 정보
        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE $del_where_sum
            ", []
        );

        //첨부파일 삭제
        if ($sql->getcount() > 0) {
            do {
                $del_arr = $sql->fetchs();
                for ($i = 1; $i<=2; $i++) {
                    if ($del_arr['file'.$i]) {
                        $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id;
                        $uploader->drop($del_arr['file'.$i]);

                        if ($uploader->isfile(MOD_BOARD_DATA_PATH.'/'.$board_id.'/thumb/'.$del_arr['file'.$i]) && $CONF['use_s3'] == 'N') {
                            $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id.'/thumb';
                            $uploader->drop($del_arr['file'.$i]);
                        }
                    }
                }
            } while ($sql->nextRec());
        }

        //댓글 삭제
        if ($sql->getcount() > 0) {
            do {
                $del_arr['idx'] = $sql->fetch('idx');
                $sql2->query(
                    "
                    DELETE
                    FROM {$sql2->table("mod:board_cmt_".$board_id)}
                    WHERE bo_idx=:col1
                    ",
                    array(
                        $del_arr['idx']
                    )
                );
            } while ($sql->nextRec());
        }

        //게시글 삭제
        $sql->query(
            "
            DELETE
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE $del_where_sum
            ", []
        );

        //return
        $return_url = '?page='.$req['page'].'&where='.$req['where'].'&keyword='.$req['keyword'].'&category='.urlencode($req['category']);
        if (isset($req['request']) && $req['request'] == 'manage') {
            $return_url = '?page='.$req['page'].'&sort='.$req['sort'].'&ordtg='.$req['ordtg'].'&ordsc='.$req['ordsc'].'&category='.urlencode($req['category']).'&id='.$board_id.'&where='.$req['where'].'&keyword='.$req['keyword'];
        }
        Valid::set(
            array(
                'return' => 'alert->location',
                'location' => $return_url,
                'msg' => '성공적으로 삭제 되었습니다.'
            )
        );
        Valid::turn();
    }

    ///
    // 게시물 이동
    ///
    private function get_move()
    {
        global $CONF, $cnum, $req, $board_id, $t_board_id, $ln_where;

        $uploader = new Uploader();

        $sql = new Pdosql();
        $cp_sql = new Pdosql();
        $cp_sql2 = new Pdosql();

        //선택된 게시물의 ln,rn 정보
        $ln_where = array();
        for ($i = 0; $i < count($cnum); $i++) {
            if ($i == 0) {
                $ln_where = 'idx=\''.$cnum[$i].'\'';

            } else {
                $ln_where .= ' OR idx=\''.$cnum[$i].'\'';
            }
        }

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE $ln_where
            ", []
        );

        $i = 0;

        do {
            $ln_arr = $sql->fetchs();
            $ln[$i] = $ln_arr['ln'];
            $rn[$i] = $ln_arr['rn'];
            $i++;
        } while ($sql->nextRec());

        //이동 실행
        for ($i = 0; $i < count($cnum); $i++) {

            //부모글인 경우에만 이동 실행
            if ($rn[$i] == 0) {

                //글의 최소/최대 ln값 구함
                $ln_min = (int)(ceil($ln[$i] / 1000) * 1000) - 1000;
                $ln_max = (int)(ceil($ln[$i] / 1000) * 1000);

                //자식글의 범위를 구함
                $ln_where = 'ln>'.$ln_min.' AND ln<='.$ln_max;
                $sql->query(
                    "
                    SELECT *
                    FROM {$sql->table("mod:board_data_".$board_id)}
                    WHERE $ln_where
                    ", []
                );

                //대상 게시판의 최대 ln값 불러옴
                $cp_sql->query(
                    "
                    SELECT MAX(ln)+1000 AS ln_max
                    FROM {$cp_sql->table("mod:board_data_".$t_board_id)}
                    ORDER BY ln DESC
                    LIMIT 1
                    ", []
                );

                $tar_ln = $cp_sql->fetch('ln_max');

                if (!$tar_ln) {
                    $tar_ln = 1000;
                }
                $tar_ln = ceil($tar_ln / 1000) * 1000;

                //복사 대상 범위에 해당하는 게시물의 이동 시작
                do {
                    $sql->specialchars = 0;
                    $sql->nl2br = 0;
                    $arr = $sql->fetchs();

                    //원본들의 내용을 addslashes 시킴
                    foreach ($arr as $key => $value) {
                        $arr[$key] = addslashes($arr[$key]);
                    }

                    //대상 게시판으로 첨부파일 복사
                    $old_path = MOD_BOARD_DATA_PATH.'/'.$board_id;
                    $tar_path = MOD_BOARD_DATA_PATH.'/'.$t_board_id;

                    $uploader->path = MOD_BOARD_DATA_PATH;
                    $uploader->chkpath();
                    $uploader->path = $tar_path;
                    $uploader->chkpath();
                    $uploader->path = $tar_path.'/thumb';
                    $uploader->chkpath();
                    $uploader->path = '';

                    $filename = array();

                    for ($fn = 1; $fn <= 2; $fn++) {

                        if ($arr['file'.$fn] != '') {
                            $filename[$fn] = $uploader->replace_filename($arr['file'.$fn]);
                            $uploader->filecopy($old_path.'/'.$arr['file'.$fn],$tar_path.'/'.$filename[$fn]);

                            if ($uploader->isfile($old_path.'/thumb/'.$arr['file'.$fn])) {
                                $uploader->filecopy($old_path.'/thumb/'.$arr['file'.$fn],$tar_path.'/thumb/'.$fn_re);
                            }

                            $uploader->path = $old_path;
                            $uploader->drop($arr['file'.$fn]);
                            $uploader->path = $old_path.'/thumb';
                            $uploader->drop($arr['file'.$fn]);

                        } else {
                            $filename[$fn] = '';
                        }

                    }

                    //대상 게시판으로 글을 복사
                    $cp_dregdate = null;

                    if ($arr['dregdate']) {
                        $cp_dregdate = $arr['dregdate'];
                    }

                    $cp_sql->query(
                        "
                        INSERT INTO
                        {$cp_sql->table("mod:board_data_".$t_board_id)}
                        (category,ln,rn,mb_idx,mb_id,writer,pwd,email,article,subject,file1,file1_cnt,file2,file2_cnt,use_secret,use_html,use_email,view,ip,regdate,dregdate,data_1,data_2,data_3,data_4,data_5,data_6,data_7,data_8,data_9,data_10)
                        VALUES
                        (:col1,:col2,:col3,:col4,:col5,:col6,:col7,:col8,:col9,:col10,:col11,:col12,:col13,:col14,:col15,:col16,:col17,:col18,:col19,now(),:col20,:col21,:col22,:col23,:col24,:col25,:col26,:col27,:col28,:col29,:col30)
                        ",
                        array(
                            $arr['category'], $tar_ln,$arr['rn'], $arr['mb_idx'], $arr['mb_id'], $arr['writer'], $arr['pwd'], $arr['email'], $arr['article'], $arr['subject'], $filename[1], $arr['file1_cnt'], $filename[2], $arr['file2_cnt'], $arr['use_secret'], $arr['use_html'], $arr['use_email'], $arr['view'], $arr['ip'], $cp_dregdate, $arr['data_1'], $arr['data_2'], $arr['data_3'], $arr['data_4'], $arr['data_5'], $arr['data_6'], $arr['data_7'], $arr['data_8'], $arr['data_9'], $arr['data_10']
                        )
                    );

                    //이동된 글의 idx값을 다시 불러옴
                    $cp_sql->query(
                        "
                        SELECT idx
                        FROM {$cp_sql->table("mod:board_data_".$t_board_id)}
                        WHERE ln=:col1
                        ",
                        array(
                            $tar_ln
                        )
                    );
                    $cped_idx = $cp_sql->fetch('idx');

                    //좋아요 이동
                    $cp_sql->query(
                        "
                        UPDATE
                        {$cp_sql->table("mod:board_like")}
                        SET
                        id=:col1,data_idx=:col2
                        WHERE id=:col3 AND data_idx=:col4
                        ",
                        array(
                            $t_board_id, $cped_idx, $board_id, $arr['idx']
                        )
                    );

                    //댓글 복사를 위해 대상 댓글 테이블의 최대 ln값 구함
                    $cp_sql->query(
                        "
                        SELECT MAX(ln)+1000 AS ln_max
                        FROM {$cp_sql->table("mod:board_data_".$t_board_id)}
                        ORDER BY ln DESC
                        LIMIT 1
                        ", []
                    );
                    $c_tar_ln = $cp_sql->fetch('ln_max');

                    if (!$c_tar_ln) {
                        $c_tar_ln = 1000;
                    }

                    $c_tar_ln = ceil($c_tar_ln / 1000) * 1000;

                    //댓글 복사를 위해 원본 댓글 테이블의 댓글 정보 가져옴
                    $cp_sql->query(
                        "
                        SELECT *
                        FROM {$cp_sql->table("mod:board_cmt_".$board_id)}
                        WHERE bo_idx=:col1
                        ",
                        array(
                            $arr['idx']
                        )
                    );

                    if ($cp_sql->getcount() > 0) {
                        do {
                            $cp_sql->specialchars = 0;
                            $cp_sql->nl2br = 0;
                            $cmt_arr = $cp_sql->fetchs();

                            //가져온 원본들의 내용을 addslashes 시킴
                            foreach ($cmt_arr as $key => $value) {
                                $cmt_arr[$key] = addslashes($cmt_arr[$key]);
                            }

                            $cp_sql2->query(
                                "
                                INSERT INTO
                                {$cp_sql2->table("mod:board_cmt_".$t_board_id)}
                                (ln,rn,bo_idx,mb_idx,writer,comment,ip,regdate,cmt_1,cmt_2,cmt_3,cmt_4,cmt_5,cmt_6,cmt_7,cmt_8,cmt_9,cmt_10)
                                VALUES
                                (:col1,:col2,:col3,:col4,:col5,:col6,:col7,:col8,:col9,:col10,:col11,:col12,:col13,:col14,:col15,:col16,:col17,:col18)
                                ",
                                array(
                                    $cmt_arr['ln'], $cmt_arr['rn'], $cped_idx, $cmt_arr['mb_idx'], $cmt_arr['writer'], $cmt_arr['comment'], $cmt_arr['ip'], $cmt_arr['regdate'], $cmt_arr['cmt_1'], $cmt_arr['cmt_2'], $cmt_arr['cmt_3'], $cmt_arr['cmt_4'], $cmt_arr['cmt_5'], $cmt_arr['cmt_6'], $cmt_arr['cmt_7'], $cmt_arr['cmt_8'], $cmt_arr['cmt_9'], $cmt_arr['cmt_10']
                                )
                            );

                        } while ($cp_sql->nextRec());
                    }

                    //기존 댓글 삭제
                    $cp_sql->query(
                        "
                        DELETE
                        FROM {$cp_sql->table("mod:board_cmt_".$board_id)}
                        WHERE bo_idx=:col1
                        ",
                        array(
                            $arr['idx']
                        )
                    );

                    //원본글 삭제
                    $cp_sql->query(
                        "
                        DELETE
                        FROM {$cp_sql->table("mod:board_data_".$board_id)}
                        WHERE idx=:col1
                        ",
                        array(
                            $arr['idx']
                        )
                    );

                    $tar_ln--;

                } while ($sql->nextRec());

            }
        }

        //return
        $return_url = '?page='.$req['page'].'&where='.$req['where'].'&keyword='.$req['keyword'].'&category='.urlencode($req['category']);
        if (isset($req['request']) && $req['request'] == 'manage') {
            $return_url = '?page='.$req['page'].'&sort='.$req['sort'].'&ordtg='.$req['ordtg'].'&ordsc='.$req['ordsc'].'&category='.urlencode($req['category']).'&id='.$board_id.'&where='.$req['where'].'&keyword='.$req['keyword'];
        }
        Valid::set(
            array(
                'return' => 'alert->location',
                'location' => $return_url,
                'msg' => '성공적으로 이동 되었습니다.'
            )
        );
        Valid::turn();
    }

    ///
    // 게시물 복사
    ///
    private function get_copy()
    {
        global $CONF, $cnum, $req, $board_id, $t_board_id;

        $uploader = new Uploader();
        $sql = new Pdosql();

        for ($i = 0; $i < count($cnum); $i++) {
            //원본글의 정보를 불러옴
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:board_data_".$board_id)}
                WHERE idx=:col1
                ",
                array(
                    $cnum[$i]
                )
            );
            $sql->specialchars = 0;
            $sql->nl2br = 0;
            $arr = $sql->fetchs();

            //원본들의 내용을 addslashes 시킴
            foreach ($arr as $key => $value){
                $arr[$key] = addslashes($arr[$key]);
            }

            //부모글인 경우만 복사 실행
            if($arr['rn']==0){

                //대상 게시판의 최대 ln값 불러옴
                $sql->query(
                    "
                    SELECT MAX(ln)+1000 AS ln_max
                    FROM {$sql->table("mod:board_data_".$t_board_id)}
                    ORDER BY ln DESC
                    LIMIT 1
                    ", []
                );

                $tar_ln = $sql->fetch('ln_max');

                if (!$tar_ln) {
                    $tar_ln = 1000;
                }

                $tar_ln = ceil($tar_ln / 1000) * 1000;

                //대상 게시판으로 첨부파일 복사
                $old_path = MOD_BOARD_DATA_PATH.'/'.$board_id;
                $tar_path = MOD_BOARD_DATA_PATH.'/'.$t_board_id;

                $uploader->path = MOD_BOARD_DATA_PATH;
                $uploader->chkpath();
                $uploader->path = $tar_path;
                $uploader->chkpath();
                $uploader->path = $tar_path.'/thumb';
                $uploader->chkpath();
                $uploader->path = '';

                $filename = array();

                for ($fn = 1; $fn <= 2; $fn++) {
                    if ($arr['file'.$fn] != '') {
                        $fn_re = $uploader->replace_filename($arr['file'.$fn]);
                        $uploader->filecopy($old_path.'/'.$arr['file'.$fn],$tar_path.'/'.$fn_re);

                        if ($uploader->isfile($old_path.'/thumb/'.$arr['file'.$fn])) {
                            $uploader->filecopy($old_path.'/thumb/'.$arr['file'.$fn],$tar_path.'/thumb/'.$fn_re);
                        }

                        $filename[$fn] = $fn_re;

                    } else {
                        $filename[$fn] = '';
                    }
                }

                //대상 게시판으로 글을 복사
                $cp_dregdate = null;

                if ($arr['dregdate']) {
                    $cp_dregdate = $arr['dregdate'];
                }

                $sql->query(
                    "
                    INSERT INTO
                    {$sql->table("mod:board_data_".$t_board_id)}
                    (category,ln,rn,mb_idx,mb_id,writer,pwd,email,article,subject,file1,file1_cnt,file2,file2_cnt,use_secret,use_html,use_email,view,ip,regdate,dregdate,data_1,data_2,data_3,data_4,data_5,data_6,data_7,data_8,data_9,data_10)
                    VALUES
                    (:col1,:col2,:col3,:col4,:col5,:col6,:col7,:col8,:col9,:col10,:col11,:col12,:col13,:col14,:col15,:col16,:col17,:col18,:col19,now(),:col20,:col21,:col22,:col23,:col24,:col25,:col26,:col27,:col28,:col29,:col30)
                    ",
                    array(
                        $arr['category'], $tar_ln,$arr['rn'], $arr['mb_idx'], $arr['mb_id'], $arr['writer'], $arr['pwd'], $arr['email'], $arr['article'], $arr['subject'], $filename[1], 0, $filename[2], 0, $arr['use_secret'], $arr['use_html'], $arr['use_email'], 0, $arr['ip'], $cp_dregdate, $arr['data_1'], $arr['data_2'], $arr['data_3'], $arr['data_4'], $arr['data_5'], $arr['data_6'], $arr['data_7'], $arr['data_8'], $arr['data_9'], $arr['data_10']
                    )
                );
            }
        }

        //return
        Valid::set(
            array(
                'return' => 'alert->reload',
                'msg' => '성공적으로 복사 되었습니다.'
            )
        );
        Valid::turn();
    }

}

/***
Writer
***/
class Writer extends \Controller\Make_Controller {

    public function init()
    {
        global $boardconf;

        $this->layout()->view('');

        $this->layout()->view(MOD_BOARD_THEME_PATH.'/board/'.$boardconf['theme'].'/mbpop.tpl.php', false);
    }

    public function func()
    {
        //성별
        function gender($mbinfo)
        {
            global $CONF;

            if ($CONF['use_mb_gender'] != 'Y') {
                return '';
            }
            if ($mbinfo['mb_gender'] == 'M') {
                return '(남자)';

            } else {
                return '(여자)';
            }
        }

        //작성글 보기 링크
        function get_link($mbinfo)
        {
            return '?where=mb_id&keyword='.$mbinfo['mb_id'];
        }

        //프로필 이미지
        function get_profileimg($mbinfo)
        {
            if ($mbinfo['mb_profileimg']) {
                $fileinfo = Func::get_fileinfo($mbinfo['mb_profileimg']);
                return $fileinfo['replink'];

            } else {
                return false;
            }
        }
    }

    public function make()
    {
        global $boardconf;

        $sql = new Pdosql();
        $boardlib = new Board_Library();

        $req = Method::request('get','board_id, mb_idx');

        //load config
        $boardconf = $boardlib->load_conf($req['board_id']);

        //회원 정보 가져옴
        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_idx=:col1
            LIMIT 1
            ",
            array(
                $req['mb_idx']
            )
        );
        $mbinfo = $sql->fetchs();

        //check
        if (!isset($req['mb_idx']) || $sql->getcount() < 1) {
            Func::err_location(ERR_MSG_1, PH_DOMAIN);
        }

        $mbinfo['mb_regdate'] = Func::datetime($mbinfo['mb_regdate']);
        $mbinfo['mb_lately'] = Func::datetime($mbinfo['mb_lately']);
        $mbinfo[0]['mb_profileimg'] = get_profileimg($mbinfo);

        if (IS_MEMBER && !$mbinfo['mb_dregdate']) {
            $is_mbinfo_show = true;

        } else {
            $is_mbinfo_show = false;
        }

        $this->set('mbinfo', $mbinfo);
        $this->set('is_mbinfo_show', $is_mbinfo_show);
        $this->set('gender', gender($mbinfo));
        $this->set('get_link', get_link($mbinfo));
    }

}
