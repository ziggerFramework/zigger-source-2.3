<?php
use \Corelib\Func;
use \Make\Database\Pdosql;

class Latest_fetch extends \Controller\Make_Controller {

    static private $called_func = 0;

    public function init()
    {
        global $FETCH_CONF;

        if (self::$called_func == 0) {
            $this->latest_func();
        }

        $lat_skin = MOD_BOARD_THEME_PATH.'/latest/'.$FETCH_CONF['theme'].'/latest.tpl.php';

        if (!file_exists($lat_skin)) {
            Func::core_err('최근게시물 테마 파일이 존재하지 않습니다. : \''.$FETCH_CONF['theme'].'\'');
        }

        $this->layout()->view($lat_skin);
    }

    public function latest_func()
    {
        //게시판 링크
        function get_board_link()
        {
            global $FETCH_CONF;
            return $FETCH_CONF['uri'];
        }

        //게시글 링크
        function get_link($list)
        {
            global $FETCH_CONF;
            return $FETCH_CONF['uri'].'?mode=view&read='.$list['idx'];
        }

        //제목
        function print_subject($list)
        {
            global $FETCH_CONF;
            return Func::strcut($list['subject'], 0, $FETCH_CONF['subject']);
        }

        //내용
        function print_article($list)
        {
            global $FETCH_CONF;
            return Func::strcut(strip_tags(Func::deHtmlspecialchars($list['article'])), 0, $FETCH_CONF['article']);
        }

        //댓글 갯수
        function comment_cnt($list)
        {
            if ($list['comment_cnt'] > 0) {
                return Func::number($list['comment_cnt']);
            }
        }

        //썸네일 추출
        function thumbnail($list)
        {
            global $CONF, $FETCH_CONF, $boardinfo;

            //본문내 첫번째 이미지 태그를 추출
            preg_match(REGEXP_IMG,Func::htmldecode($list['article']), $match);

            //썸네일의 파일 타입을 추출
            $file_type = array();

            for ($i=1; $i <= 2; $i++) {
                $file_type[$i] = Func::get_filetype($list['file'.$i]);
            }

            //조건에 따라 썸네일 HTML코드 리턴
            for ($i=1; $i <= sizeof($file_type); $i++) {
                if (Func::chkintd('match', $file_type[$i], SET_IMGTYPE)) {
                    $tmb = $list['file'.$i];
                }
            }

            if (!isset($tmb) && isset($match[0])) {
                $tmb = basename($match[1]);
            }

            if (isset($tmb)) {
                $fileinfo = Func::get_fileinfo($tmb);

                if ($fileinfo['storage'] == 'Y') {
                    $tmb = $fileinfo['replink'];

                } else {
                    $tmb = PH_DOMAIN.MOD_BOARD_DATA_DIR.'/'.$FETCH_CONF['id'].'/thumb/'.$tmb;
                }

            } else {
                $tmb = SET_BLANK_IMG;
            }

            return $tmb;
        }

        self::$called_func = 1;
    }

    public function make()
    {
        global $FETCH_CONF, $boardinfo, $orderby;

        $sql = new Pdosql();

        //게시판 검사
        $sql->query(
            "
            SELECT *
            FROM {$sql->table("config")}
            WHERE cfg_type='mod:board:config:{$FETCH_CONF['id']}'
            ", []
        );

        $boardinfo = array();

        do {
            $cfg = $sql->fetchs();

            $boardinfo[$cfg['cfg_key']] = $cfg['cfg_value'];

        } while($sql->nextRec());


        if (!$FETCH_CONF['id'] || $sql->getcount() < 1) {
            Func::core_err('최근게시물 게시판 id가 올바르지 않습니다. : \''.$FETCH_CONF['id'].'\'');
        }

        //옵션 값 검사
        if (!isset($FETCH_CONF['limit']) || !$FETCH_CONF['limit'] || $FETCH_CONF['limit'] < 1) {
            Func::core_err('최근게시물 limit 옵션이 올바르지 않습니다. : "'.$FETCH_CONF['limit'].'"');
        }
        if (!isset($FETCH_CONF['orderby']) || !$FETCH_CONF['orderby']) {
            $FETCH_CONF['orderby'] = 'recent';
        }
        if (!isset($FETCH_CONF['subject']) || !$FETCH_CONF['subject']) {
            $FETCH_CONF['subject'] = 30;
        }
        if (!isset($FETCH_CONF['article']) || !$FETCH_CONF['article']) {
            $FETCH_CONF['article'] = 50;
        }
        if (!isset($FETCH_CONF['img-width']) || !$FETCH_CONF['img-width']) {
            $FETCH_CONF['img-width'] = 150;
        }
        if (!isset($FETCH_CONF['img-height']) || !$FETCH_CONF['img-height']) {
            $FETCH_CONF['img-height'] = 150;
        }
        if (!isset($FETCH_CONF['uri']) || !$FETCH_CONF['uri']) {
            Func::core_err('uri 옵션이 올바르지 않습니다. : \''.$FETCH_CONF['uri'].'\'');
        }

        //게시물 가져옴
        switch ($FETCH_CONF['orderby']) {
            case 'recent' :
                $orderby = 'board.regdate DESC, board.idx DESC';
                break;

            case 'view' :
                $orderby = 'board.view DESC, board.regdate DESC';
                break;

            case 'like' :
                $orderby = 'likes_cnt DESC, board.regdate DESC';
                break;
        }

        $sql->query(
            "
            SELECT *,
            (
                SELECT COUNT(*)
                FROM {$sql->table("mod:board_cmt_".$boardinfo['id'])}
                WHERE bo_idx=board.idx
            ) comment_cnt,
            (
                SELECT COUNT(*)
                FROM {$sql->table("mod:board_like")}
                WHERE id='{$boardinfo['id']}' AND data_idx=board.idx AND likes>0
            ) likes_cnt
            FROM {$sql->table("mod:board_data_".$boardinfo['id'])} board
            WHERE board.use_notice='N' AND board.rn=0 AND board.dregdate IS NULL
            ORDER BY $orderby
            LIMIT {$FETCH_CONF['limit']}
            ", []
        );

        $lat_cnt = $sql->getcount();
        $print_arr = array();

        if ($lat_cnt > 0) {
            do {
                $lat_arr = $sql->fetchs();

                $lat_arr[0]['get_link'] = get_link($lat_arr);
                $lat_arr[0]['print_subject'] = print_subject($lat_arr);
                $lat_arr[0]['print_article'] = print_article($lat_arr);
                $lat_arr[0]['thumbnail'] = thumbnail($lat_arr);
                $lat_arr[0]['comment_cnt'] = comment_cnt($lat_arr);
                $lat_arr['date'] = Func::date($lat_arr['regdate']);
                $lat_arr['img-width'] = $FETCH_CONF['img-width'];
                $lat_arr['img-height'] = $FETCH_CONF['img-height'];

                $print_arr[] = $lat_arr;

            } while ($sql->nextRec());
        }

        $this->set('print_arr', $print_arr);
        $this->set('get_board_link', get_board_link());
        $this->set('board_title', $boardinfo['title']);
    }

}
