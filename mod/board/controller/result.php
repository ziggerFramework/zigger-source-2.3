<?php
namespace Module\Board;

use Corelib\Method;
use Corelib\Func;
use Make\Library\Paging;
use Make\Database\Pdosql;
use Module\Board\Library as Board_Library;

class Result extends \Controller\Make_Controller {

    public function init()
    {
        global $MOD_CONF, $boardconf;

        $req = Method::request('get', 'board_id, is_ftlist');

        $board_id = $MOD_CONF['id'];

        if ($req['is_ftlist'] == 'Y') {
            $board_id = $req['board_id'];
        }

        $boardlib = new Board_Library();
        $boardconf = $boardlib->load_conf($board_id);

        $this->layout()->view(MOD_BOARD_THEME_PATH.'/board/'.$boardconf['theme'].'/results.tpl.php');
    }

    public function func()
    {
        //전체 게시글 갯수
        function total_cnt($notice_cnt, $total_cnt)
        {
            return Func::number($notice_cnt + $total_cnt);
        }

        //제목
        function print_subject($arr)
        {
            global $boardconf;

            if (!$arr['dregdate']) {

                return reply_ico($arr).Func::strcut($arr['subject'],0,$boardconf['sbj_limit']);
            } else {
                return reply_ico($arr).'<strike>'.$arr['dregdate'].'에 삭제된 게시글입니다.'.'</strike>';
            }
        }

        //링크
        function get_link($arr, $page, $category, $where, $keyword, $thisuri)
        {
            return $thisuri.'?mode=view&read='.$arr['idx'].'&page='.$page.'&category='.urlencode($category).'&where='.$where.'&keyword='.urlencode($keyword);
        }

        //내용
        function print_article($arr)
        {
            global $boardconf;

            return Func::strcut(strip_tags(Func::htmldecode($arr['article'])), 0, $boardconf['txt_limit']);
        }

        //첨부파일 아이콘
        function file_ico($arr)
        {
            global $boardconf;

            $is_img = false;
            $is_file = false;

            if ($boardconf['ico_file'] == 'Y') {
                for ($i = 1; $i<=2; $i++) {
                    $file_type = Func::get_filetype($arr['file'.$i]);

                    if (Func::chkintd('match', $file_type, SET_IMGTYPE)) {
                        $is_img = true;

                    } else if ($arr['file'.$i]) {
                        $is_file = true;
                    }
                }
            }

            if ($is_img === true) {
                return '<img src="'.MOD_BOARD_THEME_DIR.'/images/picture-ico.png" align="absmiddle" title="이미지파일" alt="이미지파일" />';

            } else if ($is_file === true) {
                return '<img src="'.MOD_BOARD_THEME_DIR.'/images/file-ico.png" align="absmiddle" title="파일" alt="파일" />';
            }
        }

        //답글 아이콘
        function reply_ico($arr)
        {
            $nbsp = '';
            if ($arr['rn'] > 0) {
                for ($i = 1; $i <= $arr['rn']; $i++) {
                    $nbsp .= '&nbsp;&nbsp;';
                }
                return $nbsp.'<img src="'.MOD_BOARD_THEME_DIR.'/images/reply-ico.png" align="absmiddle" title="답글" alt="답글" class="reply-ico" />&nbsp;';
            }
        }

        //비밀글 아이콘
        function secret_ico($arr)
        {
            global $boardconf;

            if ($arr['use_secret'] == 'Y' && $boardconf['ico_secret'] == 'Y') {
                return '<img src="'.MOD_BOARD_THEME_DIR.'/images/secret-ico.png" align="absmiddle" title="비밀글" alt="비밀글" />';
            }
        }

        //NEW 아이콘
        function new_ico($arr)
        {
            global $boardconf;

            $now_date = date('Y-m-d H:i:s');
            $wr_date = date('Y-m-d H:i:s', strtotime($arr['regdate']));

            if ( ((strtotime($now_date) - strtotime($wr_date)) / 60) < $boardconf['ico_new_case'] && $boardconf['ico_new'] == 'Y') {
                return '<img src="'.MOD_BOARD_THEME_DIR.'/images/new-ico.png" align="absmiddle" title="NEW" alt="NEW" />';
            }
        }

        //HOT 아이콘
        function hot_ico($arr)
        {
            global $boardconf;

            $ico_hot_case = explode('|', $boardconf['ico_hot_case']);

            if ($boardconf['ico_hot'] == 'Y') {
                if (($ico_hot_case[1] == 'AND' && $arr['likes_cnt'] >= $ico_hot_case[0] && $arr['view'] >= $ico_hot_case[2]) || ($ico_hot_case[1] == 'OR' && ($arr['likes_cnt'] >= $ico_hot_case[0] || $arr['view'] >= $ico_hot_case[2]))) {
                    return '<img src="'.MOD_BOARD_THEME_DIR.'/images/hot-ico.png" align="absmiddle" title="HOT" alt="HOT" />';
                }
            }
        }

        //댓글 갯수
        function comment_cnt($arr)
        {
            global $boardconf;

            if ($arr['comment_cnt'] > 0 && $boardconf['use_comment'] == 'Y') {
                return Func::number($arr['comment_cnt']);
            }
        }

        //관리 버튼
        function ctr_btn()
        {
            global $MB, $boardconf;

            if ($MB['level'] <= $boardconf['ctr_level']) {
                return '<button type="button" class="btn2" id="list-ctr-btn"><i class="fa fa-ellipsis-v"></i> 선택 관리</button>';
            }
        }

        //작성 버튼
        function write_btn($page, $where, $keyword, $category, $thisuri)
        {
            global $MB, $boardconf;

            if ($MB['level'] <= $boardconf['write_level']) {
                return '<a href="'.$thisuri.'?mode=write&category='.urlencode($category).'&page='.$page.'&where='.$where.'&keyword='.urlencode($keyword).'" class="btn1">글 작성</a>';
            }
        }

        //게시물 번호
        function print_number($arr, $read, $paging)
        {
            if ($read == $arr['idx']) {
                return '<i class="fa fa-angle-right"></i>';

            } else {
                return $paging->getnum();
            }
        }

        //회원 프로필
        function print_profileimg($arr)
        {
            if ($arr['mb_profileimg']) {
                $fileinfo = Func::get_fileinfo($arr['mb_profileimg']);
                return $fileinfo['replink'];

            } else {
                return false;
            }
        }

        //회원 이름
        function print_writer($arr)
        {
            if ($arr['mb_idx'] != 0) {
                return '<a href="#" data-profile="'.$arr['mb_idx'].'">'.$arr['writer'].'</a>';

            } else {
                return $arr['writer'];
            }
        }

        //카테고리
        function print_category($category, $where, $keyword, $thisuri)
        {
            global $boardconf;

            if (!$boardconf['category']) {
                return;
            }

            $cat_exp = explode('|', $boardconf['category']);
            $html = '<ul>';

            for ($i = 0; $i<=sizeOf($cat_exp); $i++) {
                $j = $i - 1;

                if ($j == -1) {
                    if ($category) {
                        $html .= '<li><a href="'.$thisuri.'?where='.$where.'&keyword='.urlencode($keyword).'">전체</a></li>'.PHP_EOL;

                    } else {
                        $html .= '<li class="active"><a href="'.$thisuri.'?where='.$where.'&keyword='.urlencode($keyword).'">전체</a></li>'.PHP_EOL;
                    }

                } else if ($category == $cat_exp[$j]) {
                    $html .= '<li class="active"><a href="'.$thisuri.'?category='.urlencode($cat_exp[$j]).'&where='.$where.'&keyword='.urlencode($keyword).'">'.$cat_exp[$j].'</a></li>'.PHP_EOL;

                } else {
                    $html .= '<li><a href="'.$thisuri.'?category='.urlencode($cat_exp[$j]).'&where='.$where.'&keyword='.urlencode($keyword).'">'.$cat_exp[$j].'</a></li>'.PHP_EOL;
                }
            }

            $html .= '</ul>';

            return $html;
        }

        //썸네일 추출
        function thumbnail($arr)
        {
            global $CONF, $board_id;

            //본문내 첫번째 이미지 태그를 추출
            preg_match(REGEXP_IMG, Func::htmldecode($arr['article']), $match);

            //썸네일의 파일 타입을 추출
            $file_type = array();
            for ($i = 1; $i <= 2; $i++) {
                $file_type[$i] = Func::get_filetype($arr['file'.$i]);
            }

            //조건에 따라 썸네일 HTML코드 리턴
            for ($i = 1; $i <= sizeof($file_type); $i++) {
                if (Func::chkintd('match', $file_type[$i], SET_IMGTYPE)) {
                    $fileinfo = Func::get_fileinfo($arr['file'.$i]);
                    if ($fileinfo['storage'] == 'Y') {
                        $tmb = $fileinfo['replink'];

                    } else {
                        $tmb = PH_DOMAIN.MOD_BOARD_DATA_DIR.'/'.$board_id.'/thumb/'.$arr['file'.$i];
                    }
                }
            }

            if (!isset($tmb)) {
                if (isset($match[1])) {
                    $tmb = $match[1];

                } else {
                    $tmb = '';
                }
            }

            return $tmb;
        }

        //where selectbox 선택 처리
        function where_slted($where)
        {
            $arr = array('all', 'subjectAndArticle', 'subject', 'article', 'writer', 'mb_id');
            $opt = array();

            foreach ($arr as $key => $value) {
                if ($where == $value) {
                    $opt[$value] = 'selected';

                } else {
                    $opt[$value] = '';
                }
            }

            return $opt;
        }

        //list arr setting
        function get_listarr($req, $arr, $paging, $thisuri, $keyword, $category)
        {
            $arr['view'] = Func::number($arr['view']);
            $arr['date'] = Func::date($arr['regdate']);
            $arr['datetime'] = Func::datetime($arr['regdate']);
            $arr[0]['number'] = print_number($arr, $req['read'],$paging);
            $arr[0]['get_link'] = get_link($arr, $req['page'], $category, $req['where'], $keyword, $thisuri);
            $arr[0]['secret_ico'] = secret_ico($arr);
            $arr[0]['file_ico'] = file_ico($arr);
            $arr[0]['new_ico'] = new_ico($arr);
            $arr[0]['hot_ico'] = hot_ico($arr);
            $arr[0]['subject'] = print_subject($arr);
            $arr[0]['article'] = print_article($arr);
            $arr[0]['comment_cnt'] = comment_cnt($arr);
            $arr[0]['writer'] = print_writer($arr);
            $arr[0]['profileimg'] = print_profileimg($arr);
            $arr[0]['thumbnail'] = thumbnail($arr);

            return $arr;
        }
    }

    public function make()
    {
        global $MB, $MOD_CONF, $boardconf, $board_id, $search;

        $sql = new Pdosql();
        $boardlib = new Board_Library();
        $paging = new Paging();

        $req = Method::request('get', 'page, where, keyword, read, category, is_ftlist');

        if (!$req['is_ftlist']) {
            $board_id = $MOD_CONF['id'];
        }

        if ($req['is_ftlist'] == 'Y') {
            $ft_req = Method::request('get','board_id, thisuri');
            $board_id = $ft_req['board_id'];
        }

        if (isset($ft_req['thisuri'])) {
            $thisuri = $ft_req['thisuri'];

        } else {
            $thisuri = Func::thisuri();
        }

        //add title
        if (!$req['is_ftlist']) {
            Func::add_title($boardconf['title']);
        }

        //add stylesheet & javascript
        if (!$req['is_ftlist']) {
            $boardlib->print_headsrc($boardconf['theme']);
        }

        //접근 권한 검사
        if (!IS_MEMBER && $MB['level'] > $boardconf['list_level']) {
            Func::getlogin(SET_NOAUTH_MSG);

        } else if (IS_MEMBER && $MB['level'] > $boardconf['list_level']) {
            Func::err_location('접근 권한이 없습니다.', PH_DOMAIN);
        }

        //카테고리 처리
        $category = urldecode($req['category']);
        $search = '';

        if ($category) {
            $search = 'AND board.category=\''.$req['category'].'\'';
        }

        //검색 키워드 처리
        $keyword = htmlspecialchars(urlencode($req['keyword']));

        if ($keyword) {
            $keyword = urldecode($req['keyword']);
            $where_arr = array('subject', 'article', 'writer', 'mb_id');

            switch ($req['where']) {
                case 'subjectAndArticle' :
                    $search .= 'AND (';
                    $search .= 'board.subject like \'%'.$req['keyword'].'%\'';
                    $search .= 'OR board.article like \'%'.$req['keyword'].'%\'';
                    $search .= ')';
                    break;

                case 'subject' :
                case 'article' :
                case 'writer' :
                case 'mb_id' :
                    $search .= 'AND board.'.$req['where'].' like \'%'.$req['keyword'].'%\'';
                    break;

                default :
                    $search .= 'AND (';
                    foreach ($where_arr as $key => $value) {
                        $search .= ($key > 0 ? ' OR ' : '').'board.'.$value.' like \'%'.$req['keyword'].'%\'';
                    }
                    $search .= ')';
            }
        }

        if ($boardconf['use_category'] == 'Y' && $boardconf['category'] != '') {
            $is_category_show = true;

        } else {
            $is_category_show = false;
        }

        if ($MB['level'] <= $boardconf['ctr_level'] || $MB['adm'] == 'Y') {
            $is_ctr_show = true;

        } else {
            $is_ctr_show = false;
        }

        if ($boardconf['use_comment'] == 'Y') {
            $is_comment_show = true;

        } else {
            $is_comment_show = false;
        }

        if ($boardconf['use_likes'] == 'Y') {
            $is_likes_show = true;

        } else {
            $is_likes_show = false;
        }

        if ($req['is_ftlist'] == 'Y') {
            $is_tf_source_show = false;

        } else {
            $is_tf_source_show = true;
        }

        //notice
        $sql->query(
            "
            SELECT *, member.mb_profileimg,
            (
                SELECT COUNT(*)
                FROM {$sql->table("mod:board_cmt_".$board_id)}
                WHERE bo_idx=board.idx
            ) comment_cnt,
            (
                SELECT COUNT(*)
                FROM {$sql->table("mod:board_like")}
                WHERE id='$board_id' AND data_idx=board.idx AND likes>0
            ) likes_cnt,
            (
                SELECT COUNT(*)
                FROM {$sql->table("mod:board_like")}
                WHERE id='$board_id' AND data_idx=board.idx AND unlikes>0
            ) unlikes_cnt
            FROM {$sql->table("mod:board_data_".$board_id)} board
            LEFT OUTER JOIN {$sql->table("member")} member
            ON board.mb_idx=member.mb_idx
            WHERE board.use_notice='Y'
            ORDER BY board.regdate DESC
            ", []
        );
        $notice_cnt = $sql->getcount();
        $print_notice = array();

        if ($notice_cnt > 0) {
            do {
                $arr = $sql->fetchs();
                $print_notice[] = get_listarr($req, $arr, $paging, $thisuri, $keyword, $category);

            } while ($sql->nextRec());
        }

        //list
        $paging->thispage = $thisuri;
        $paging->setlimit($boardconf['list_limit']);

        $sql->query(
            $paging->query(
                "
                SELECT *, member.mb_profileimg,
                (
                    SELECT COUNT(*)
                    FROM {$sql->table("mod:board_cmt_".$board_id)}
                    WHERE bo_idx=board.idx
                ) comment_cnt,
                (
                    SELECT COUNT(*)
                    FROM {$sql->table("mod:board_like")}
                    WHERE id='$board_id' AND data_idx=board.idx AND likes>0
                ) likes_cnt,
                (
                    SELECT COUNT(*)
                    FROM {$sql->table("mod:board_like")}
                    WHERE id='$board_id' AND data_idx=board.idx AND unlikes>0
                ) unlikes_cnt
                FROM {$sql->table("mod:board_data_".$board_id)} board
                LEFT OUTER JOIN {$sql->table("member")} member
                ON board.mb_idx=member.mb_idx
                WHERE board.use_notice='N' $search
                ORDER BY board.ln DESC, board.rn ASC, board.regdate DESC
                ", []
            )
        );
        $total_cnt = Func::number($paging->totalCount);
        $print_arr = array();

        if ($sql->getcount() > 0) {
            do {
                $arr = $sql->fetchs();
                $print_arr[] = get_listarr($req, $arr, $paging, $thisuri, $keyword, $category);

            } while($sql->nextRec());
        }

        $pagingprint = $paging->pagingprint('&category='.$category.'&where='.$req['where'].'&keyword='.$keyword);

        $this->set('print_notice', $print_notice);
        $this->set('print_arr', $print_arr);
        $this->set('pagingprint', $pagingprint);
        $this->set('is_category_show', $is_category_show);
        $this->set('is_comment_show', $is_comment_show);
        $this->set('is_ctr_show', $is_ctr_show);
        $this->set('is_likes_show', $is_likes_show);
        $this->set('is_tf_source_show', $is_tf_source_show);
        $this->set('category', $category);
        $this->set('page', $req['page']);
        $this->set('where', $req['where']);
        $this->set('board_id', $board_id);
        $this->set('keyword', $keyword);
        $this->set('thisuri', $thisuri);
        $this->set('print_category', print_category($category, $req['where'], $keyword, $thisuri));
        $this->set('total_cnt', total_cnt($notice_cnt, $total_cnt));
        $this->set('ctr_btn', ctr_btn());
        $this->set('write_btn', write_btn($req['page'], $req['where'], $keyword, $category, $thisuri));
        $this->set('where_slted', where_slted($req['where']));
        $this->set('cancel_link', $thisuri);
        $this->set('top_source', $boardconf['top_source']);
        $this->set('bottom_source', $boardconf['bottom_source']);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'board-listForm');
        $form->set('type', 'static');
        $form->set('target', 'view');
        $form->set('method', 'get');
        $form->run();
    }

    public function sch_form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'board-sch');
        $form->set('type', 'static');
        $form->set('target', 'view');
        $form->set('method', 'get');
        $form->run();
    }

}
