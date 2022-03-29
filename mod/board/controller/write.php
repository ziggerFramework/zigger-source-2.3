<?php
namespace Module\Board;

use Corelib\Method;
use Corelib\Func;
use Corelib\Valid;
use Corelib\Session;
use Make\Library\Uploader;
use Make\Library\Imgresize;
use Make\Library\Mail;
use Make\Database\Pdosql;
use Module\Board\Library as Board_Library;
use Module\Alarm\Library as Alarm_Library;

class Write extends \Controller\Make_Controller {

    static private $show_pwdform = 0;
    static public $boardconf;

    public function init()
    {
        $this->layout()->view('');

        if (self::$show_pwdform == 0) {
            $this->layout()->view(MOD_BOARD_THEME_PATH.'/board/'.Write::$boardconf['theme'].'/write.tpl.php', false);

        } else {
            $this->layout()->view(MOD_BOARD_THEME_PATH.'/board/'.Write::$boardconf['theme'].'/password.tpl.php', false);
        }
    }

    public function func()
    {
        //category
        function category_option($arr, $category)
        {
            $cat = explode('|', Write::$boardconf['category']);
            $opt = '';

            for ($i = 0; $i < sizeOf($cat); $i++) {
                $slted = '';

                if (isset($arr['category']) && urldecode($cat[$i]) == $arr['category']) {
                    $slted = 'selected';
                }
                if (urldecode($cat[$i]) == $category) {
                    $slted = 'selected';

                }

                $opt .= '<option value="'.$cat[$i].'" '.$slted.'>'.$cat[$i].'</option>';
            }
            return $opt;
        }

        //파일명
        function uploaded_file($arr, $wrmode)
        {
            if ($wrmode != 'reply') {
                $files = array();

                for ($i = 1; $i <= 2; $i++) {
                    $files[$i] = '';

                    if (isset($arr['file'.$i])) {
                        $fileinfo = Func::get_fileinfo($arr['file'.$i]);
                        $files[$i] = $fileinfo['orgfile'];
                    }

                }

                return $files;
            }
        }

        //공지글 옵션
        function opt_notice($arr, $wrmode)
        {
            global $MB;

            $notice_opt = '<label><input type="checkbox" name="use_notice" id="use_notice" value="checked" />공지글 작성</label>';

            if ($MB['level'] == 1 || $MB['level'] <= Write::$boardconf['ctr_level']) {
                if (isset($arr['use_notice']) && $arr['use_notice'] == 'Y') {
                    $notice_opt = '<label><input type="checkbox" name="use_notice" id="use_notice" value="checked" checked="checked" />공지글 작성</label>';

                } else if ((isset($arr['rn']) && $arr['rn'] > 0) || $wrmode == 'reply') {
                    $notice_opt =  '';
                }

            } else {
                $notice_opt =  '';
            }

            return $notice_opt;
        }

        //비밀글 옵션
        function opt_secret($arr)
        {
            $secret_opt = '';

            if (Write::$boardconf['use_secret'] == 'Y' && ( ( isset($arr['use_secret']) && $arr['use_secret']=='Y' ) || Write::$boardconf['ico_secret_def'] == 'Y') ) {
                $secret_opt = '<label><input type="checkbox" name="use_secret" id="use_secret" value="checked" checked="checked" />비밀글 작성</label>';

            } else if (Write::$boardconf['use_secret'] == 'Y') {
                $secret_opt = '<label><input type="checkbox" name="use_secret" id="use_secret" value="checked" />비밀글 작성</label>';

            } else {
                $secret_opt = '';
            }

            return $secret_opt;
        }

        //이메일 답변 옵션
        function opt_return_email($arr)
        {
            $email_opt = '';
            if (isset($arr['use_email']) && $arr['use_email'] == 'Y') {
                $email_opt = '<label><input type="checkbox" name="use_email" id="use_email" value="checked" checked="checked" />이메일로 답글 알림 수신</label>';

            } else {
                $email_opt = '<label><input type="checkbox" name="use_email" id="use_email" value="checked" />이메일로 답글 알림 수신</label>';
            }

            return $email_opt;
        }

        //취소 버튼
        function cancel_btn($page, $category, $where, $keyword)
        {
            return '<a href="?page='.$page.'&category='.$category.'&where='.$where.'&keyword='.urlencode($keyword).'" class="btn2">취소</a>';
        }

        //글쓰기 타이틀
        function write_title($wrmode)
        {
            if ($wrmode == 'modify') {
                return '글 수정';

            } else if ($wrmode == 'reply') {
                return '답글 작성';

            } else {
                return '새로운 글 작성';
            }
        }

        //첨부 가능한 파일 사이즈
        function print_filesize()
        {
            global $func;

            return Func::getbyte(Write::$boardconf['file_limit'], 'M').'M';
        }
    }

    public function make()
    {
        global $MB, $MOD_CONF, $board_id;

        $sql = new Pdosql();
        $boardlib = new Board_Library();

        $req = Method::request('get','mode, wrmode, read, page, where, keyword, category');

        $board_id = $MOD_CONF['id'];

        //패스워드가 submit 된 경우
        if (isset($_POST['s_password'])) {

            $s_req = Method::request('post','s_mode,s_wrmode,s_read,s_page,s_category,s_where,s_keyword,s_password');
            $req['mode'] = $s_req['s_mode'];
            $req['wrmode'] = $s_req['s_wrmode'];
            $req['read'] = $s_req['s_read'];
            $req['page'] = $s_req['s_page'];
            $req['category'] = $s_req['s_category'];
            $req['where'] = $s_req['s_where'];
            $req['keyword'] = $s_req['s_keyword'];

        }

        //load config
        Write::$boardconf = $boardlib->load_conf($board_id);

        //add title
        Func::add_title(Write::$boardconf['title'].' - 글 작성');

        //add stylesheet & javascript
        $boardlib->print_headsrc(Write::$boardconf['theme']);
        Write::$boardconf = $boardlib->load_conf($board_id);

        //수정 or 답글인 경우 원본 글 불러옴
        if ($req['wrmode'] == 'modify' || $req['wrmode'] == 'reply') {
            $sql->query(
                "
                SELECT board.*,ceil(board.ln) ceil_ln,
                (
                    SELECT COUNT(*)
                    FROM {$sql->table("mod:board_data_".$board_id)}
                    WHERE ln<=((ceil_ln/1000)*1000) AND ln>((ceil_ln/1000)*1000)-1000 AND rn>0
                ) reply_cnt
                FROM {$sql->table("mod:board_data_".$board_id)} board
                WHERE board.idx=:col1
                ",
                array(
                    $req['read']
                )
            );
            $arr = $sql->fetchs();
            $sql->specialchars = 1;
            $sql->nl2br = 0;
            $arr['article'] = $sql->fetch('article');

            if ($sql->getcount() < 1) {
                Func::err_back('해당 글이 존재하지 않습니다.');
            }

            if ($req['wrmode'] == 'reply') {
                if ($arr['use_html'] == 'Y') {
                    $arr['article'] = '<br /><br /><br /><div><strong>Org: '.$arr['subject'].'</strong><br />'.$arr['article'].'</div>';

                } else {
                    $arr['article'] = '\n\n\nOrg: '.$arr['subject'].'\n'.$arr['article'];
                }
                $arr['subject'] = 'Re: '.$arr['subject'];
            }
        } else {
            $arr = null;
        }

        //check
        if (!$board_id) {
            Func::err_back('게시판이 지정되지 않았습니다.');
        }
        if ($MB['level'] > Write::$boardconf['write_level'] && $MB['level'] > Write::$boardconf['ctr_level']) {
            Func::err_back('글 작성 권한이 없습니다.');
        }
        if (!$req['wrmode'] || $req['wrmode'] == 'reply') {
            if (Write::$boardconf['write_point'] < 0) {
                if (!IS_MEMBER) {
                    Func::err_back('포인트 설정으로 인해 비회원은 글을 작성할 수 없습니다.');
                }
                if ($MB['point'] < (0 - Write::$boardconf['write_point'])) {
                    Func::err_back('포인트가 부족하여 글을 작성할 수 없습니다.');
                }
            }
        }
        if ($req['wrmode'] == 'reply' && Write::$boardconf['use_reply'] == 'N') {
            Func::err_back('답변글을 등록할 수 없습니다.');
        }

        //삭제된 게시글인지 검사
        if ($req['wrmode'] == 'modify' || $req['wrmode'] == 'reply') {
            if ($arr['dregdate']) {
                Func::err_back('삭제된 게시물입니다.');
            }
        }

        //수정모드인 경우 권한 검사
        if ($req['wrmode'] == 'modify') {
            if ($MB['level'] <= Write::$boardconf['ctr_level']) {
                $wr_level = 1;

            } else {
                if ($arr['mb_idx'] < 1 && !IS_MEMBER) {
                    $wr_level = 3;

                } else if ($arr['mb_idx'] == $MB['idx'] && $MB['level'] <= Write::$boardconf['write_level']) {
                    $wr_level = 1;

                } else {
                    $wr_level = 0;
                }
            }
            if ($wr_level == 0) {
                Func::err_back('수정 권한이 없습니다.');
            }
        }

        //답글 모드인 경우 권한 검사
        if ($req['wrmode'] == 'reply') {
            if (($MB['level'] > Write::$boardconf['write_level'] && $MB['level'] > Write::$boardconf['ctr_level']) || $MB['level'] > Write::$boardconf['reply_level']) {
                Func::err_back('답글 작성 권한이 없습니다.');
            }
            if ($arr['use_notice'] == 'Y') {
                Func::err_back('공지글에는 답글을 달 수 없습니다.');
            }
        }

        //패스워드가 submit된 경우 검사
        if (isset($s_req['s_password'])) {
            if ($arr['pwd'] == $s_req['s_password']) {
                $wr_level = 1;

            } else {
                Func::err_back('입력한 비밀번호가 일치하지 않습니다.');
            }
        }

        //패스워드 입력 폼 노출
        if ($req['wrmode'] == 'modify' && !IS_MEMBER && $wr_level != 1) {

            self::$show_pwdform = 1;

        }

        //작성 폼 노출
        else {

            self::$show_pwdform = 0;

            if (!IS_MEMBER || ($req['wrmode'] == 'modify' && $arr['mb_idx'] == '0')) {
                $is_writer_show = true;

            } else {
                $is_writer_show = false;
            }

            if (!IS_MEMBER || ($req['wrmode'] == 'modify' && $arr['mb_idx'] == '0')) {
                $is_pwd_show = true;
                $is_email_show = true;

            } else {
                $is_pwd_show = false;
                $is_email_show = false;
            }

            if (!IS_MEMBER) {
                $is_captcha_show = true;

            } else {
                $is_captcha_show = false;
            }

            $is_file_show = array();

            for ($i = 1; $i <= 2; $i++) {
                if (Write::$boardconf['use_file'.$i] == 'Y') {
                    $is_file_show[$i] = true;

                } else {
                    $is_file_show[$i] = false;

                }

                $is_filename_show[$i] = false;

                if ($req['wrmode'] == 'modify') {
                    if ($arr['file'.$i] != '') {
                        $is_filename_show[$i] = true;
                    }

                } else {
                    $is_filename_show[$i] = false;
                }
            }

            if (Write::$boardconf['use_category'] == 'Y' && Write::$boardconf['category'] != '' && $req['wrmode'] != 'reply' && $arr['rn'] == 0 && $arr['reply_cnt'] < 1) {
                $is_category_show = true;

            } else {
                $is_category_show = false;
            }

            if (isset($arr) && !IS_MEMBER && $req['wrmode'] != 'modify') {
                $arr['pwd'] = '';
                $arr['email'] = '';
            }

            $write = array();
            if (isset($arr)) {
                foreach ($arr as $key => $value) {
                    $write[$key] = $value;
                }

            } else {
                $write = array(
                    'subject' => '',
                    'article' => '',
                    'writer' => '',
                    'pwd' => '',
                    'email' => ''
                );

                for ($i = 1; $i <= 10; $i++) {
                    $write['data_'.$i] = '';
                }
            }

            $this->set('write', $write);
            $this->set('uploaded_file', uploaded_file($arr,$req['wrmode']));
            $this->set('cancel_btn', cancel_btn($req['page'], $req['category'], $req['where'], $req['keyword']));
            $this->set('is_category_show', $is_category_show);
            $this->set('is_writer_show', $is_writer_show);
            $this->set('is_pwd_show', $is_pwd_show);
            $this->set('is_email_show', $is_email_show);
            $this->set('is_file_show', $is_file_show);
            $this->set('is_filename_show', $is_filename_show);
            $this->set('is_captcha_show', $is_captcha_show);

        }
        $this->set('board_id', $board_id);
        $this->set('mode', $req['mode']);
        $this->set('wrmode', $req['wrmode']);
        $this->set('read', $req['read']);
        $this->set('page', $req['page']);
        $this->set('where', $req['where']);
        $this->set('keyword', $req['keyword']);
        $this->set('category', $req['category']);
        $this->set('thisuri', Func::thisuri());
        $this->set('write_title', write_title($req['wrmode']));
        $this->set('category_option', category_option($arr, $req['category']));
        $this->set('opt_notice', opt_notice($arr,$req['wrmode']));
        $this->set('opt_secret', opt_secret($arr));
        $this->set('opt_return_email', opt_return_email($arr));
        $this->set('print_filesize', print_filesize());
        $this->set('captcha', Func::get_captcha('', 1));
        $this->set('top_source', Write::$boardconf['top_source']);
        $this->set('bottom_source', Write::$boardconf['bottom_source']);
    }

    public function form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'board-writeForm');
        $form->set('type', 'multipart');
        $form->set('action', MOD_BOARD_DIR.'/controller/write/write-submit');
        $form->run();
    }

    public function pass_form()
    {
        $form = new \Controller\Make_View_Form();
        $form->set('id', 'board-pwdForm');
        $form->set('type', 'static');
        $form->set('target', 'view');
        $form->set('method', 'post');
        $form->run();
    }

}

/***
Submit
***/
class Write_submit{

    public function init(){
        global $CONF, $MB, $req, $ufile, $wr_opt, $org_arr, $board_id;

        $boardlib = new Board_Library();
        $uploader = new Uploader();
        $imgresize = new Imgresize();
        $sql = new Pdosql();

        Method::security('referer');
        Method::security('request_post');
        $req = Method::request('post', 'thisuri, board_id, wrmode, read, page, where, keyword, category_ed, use_html, category, use_notice, use_secret, use_email, writer, password, email, subject, article, file1_del, file2_del, captcha, data_1, data_2, data_3, data_4, data_5, data_6, data_7, data_8, data_9, data_10');
        $f_req = Method::request('file', 'file1, file2');

        $board_id = $req['board_id'];

        //load config
        Write::$boardconf = $boardlib->load_conf($board_id);

        //수정 or 답글인 경우 원본 글 가져옴
        if ($req['wrmode'] == 'modify' || $req['wrmode'] == 'reply') {
            $sql->query(
                "
                SELECT board.*,ceil(board.ln) ceil_ln,
                (
                    SELECT COUNT(*)
                    FROM {$sql->table("mod:board_data_".$board_id)}
                    WHERE ln<=((ceil_ln/1000)*1000) AND ln>((ceil_ln/1000)*1000)-1000 AND rn>0
                ) reply_cnt
                FROM {$sql->table("mod:board_data_".$board_id)} board
                WHERE board.idx=:col1
                ",
                array(
                    $req['read']
                )
            );
            $org_arr = $sql->fetchs();
        }

        //수정 or 답글인 경우 삭제된 게시글인지 검사
        if ($req['wrmode'] == 'modify' || $req['wrmode'] == 'reply') {
            if ($org_arr['dregdate']) {
                Func::err_back('삭제된 게시글입니다.');
            }
        }

        //옵션값 처리
        $wr_opt = array();
        if ($req['use_notice'] == 'checked') {
            $wr_opt['notice'] = 'Y';
            $wr_opt['email'] = 'N';

        } else {
            $wr_opt['notice'] = 'N';
        }

        if (Write::$boardconf['use_secret'] == 'Y') {
            if ($req['use_secret'] == 'checked') {
                $wr_opt['secret'] = 'Y';
            } else {
                $wr_opt['secret'] = 'N';
            }

        } else if(!$req['wrmode']) {
            $wr_opt['secret'] = 'N';

        } else {
            $wr_opt['secret'] = $org_arr['use_secret'];
        }

        if ($req['use_email'] == 'checked') {
            $wr_opt['email'] = 'Y';

        } else {
            $wr_opt['email'] = 'N';
        }

        //수정모드인 경우 여분필드 처리
        if ($req['wrmode'] == 'modify') {
            for ($i = 1 ;$i <= 10; $i++) {
                if (!$req['data_'.$i]) {
                    $req['data_'.$i] = $org_arr['data_'.$i];
                }
            }
        }

        //check
        if ($MB['level'] > Write::$boardconf['write_level'] && $MB['level'] > Write::$boardconf['ctr_level']) {
            $this->error('','글 작성 권한이 없습니다.');
        }

        Valid::get(
            array(
                'input' => 'subject',
                'value' => $req['subject']
            )
        );
        Valid::get(
            array(
                'input' => 'article',
                'value' => $req['article'],
                'msg' => '내용은 '.Write::$boardconf['article_min_len'].'자 이상 입력해야 합니다.',
                'check' => array(
                    'minlen' => Write::$boardconf['article_min_len']
                )
            )
        );
        Valid::get(
            array(
                'input' => 'article',
                'value' => $req['article'],
                'check' => array(
                    'chkhtml' => true
                )
            )
        );

        if (!IS_MEMBER) {

            Valid::get(
                array(
                    'input' => 'writer',
                    'value' => $req['writer'],
                    'check' => array(
                        'defined' => 'nickname'
                    )
                )
            );
            Valid::get(
                array(
                    'input' => 'password',
                    'value' => $req['password'],
                    'check' => array(
                        'defined' => 'password'
                    )
                )
            );

            if ($wr_opt['email'] == 'Y') {
                Valid::get(
                    array(
                        'input' => 'email',
                        'value' => $req['email'],
                        'check' => array(
                            'defined' => 'email'
                        )
                    )
                );
            }
            if (!Func::chk_captcha($req['captcha'])) {
                Valid::set(
                    array(
                        'return' => 'error',
                        'input' => 'captcha',
                        'err_code' => 'NOTMATCH_CAPTCHA'
                    )
                );
                Valid::turn();
            }
        }

        if ($f_req['file1']['size'] > 0 && $f_req['file2']['size'] > 0 && $f_req['file1']['name'] == $f_req['file2']['name']) {
            Valid::error('', '동일한 파일을 업로드 할 수 없습니다.');
        }

        //수정모드인 경우 검사
        if ($req['wrmode'] == 'modify' && IS_MEMBER && $org_arr['mb_idx'] == 0) {

            Valid::get(
                array(
                    'input' => 'writer',
                    'value' => $req['writer'],
                    'check' => array(
                        'defined' =>'nickname'
                    )
                )
            );
            Valid::get(
                array(
                    'input' => 'password',
                    'value' => $req['password'],
                    'check' => array(
                        'defined' =>'password'
                    )
                )
            );

            if ($wr_opt['email'] == 'Y' || $req['email'] != '') {
                Valid::get(
                    array(
                        'input' => 'email',
                        'value' => $req['email'],
                        'check' => array(
                            'defined' =>'email'
                        )
                    )
                );
            }
        }

        //글 작성인 경우, 이미 같은 내용의 글이 존재하는지 검사
        if (!$req['wrmode'] || $req['wrmode'] == 'reply') {
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:board_data_".$board_id)}
                WHERE article=:col1
                ",
                array(
                    $req['article']
                )
            );
            if ($sql->getcount() > 0) {
                Valid::error('article', '이미 같은 내용의 글이 존재합니다.');
            }
        }

        //글 작성 포인트 조정
        if (!$req['wrmode'] || $req['wrmode'] == 'reply') {
            if (Write::$boardconf['write_point'] < 0) {
                if (!IS_MEMBER) {
                    $this->error('', '포인트 설정으로 인해 비회원은 글을 작성할 수 없습니다.');
                }
                if ($MB['point'] < (0 - Write::$boardconf['write_point'])) {
                    $this->error('', '포인트가 부족하여 글을 작성할 수 없습니다.');
                }

                $point = 0 - Write::$boardconf['write_point'];
                Func::set_mbpoint(
                    array(
                        'mb_idx' => $MB['idx'],
                        'mode' => 'out',
                        'point' => $point,
                        'msg' => '게시판 글 작성 ('.Write::$boardconf['title'].')'

                    )
                );

            } else if(Write::$boardconf['write_point'] > 0) {
                Func::set_mbpoint(
                    array(
                        'mb_idx' => $MB['idx'],
                        'mode' => 'in',
                        'point' => Write::$boardconf['write_point'],
                        'msg' => '게시판 글 작성 ('.Write::$boardconf['title'].')'
                    )
                );
            }
        }

        //첨부파일 저장
        $uploader->path = MOD_BOARD_DATA_PATH;
        $uploader->chkpath(); //모듈 data 폴더 검사
        $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id;
        $uploader->chkpath(); //게시판별 폴더 검사

        $ufile = array();
        $ufile_name = array();

        for ($i = 1; $i <= 2; $i++) {
            $ufile[$i] = $f_req['file'.$i];
        }

        for ($i = 1; $i <= 2; $i++) {
            if ($ufile[$i]['size'] > 0 && Write::$boardconf['use_file'.$i]) {
                $uploader->file = $ufile[$i];

                if ($uploader->chkfile('match') === true) {
                    Valid::error('file'.$i, ERR_MSG_8);
                }

                if ($uploader->chkbyte(Write::$boardconf['file_limit']) === false) {
                    Valid::error('file'.$i, '허용 파일 용량을 초과합니다.');
                }

            }
        }

        for ($i = 1; $i <= 2; $i++) {
            if ($ufile[$i]['size'] > 0 && Write::$boardconf['use_file'.$i]) {
                $uploader->file = $ufile[$i];

                $ufile[$i]['ufile_name'] = $uploader->replace_filename($ufile[$i]['name']);
                array_push($ufile_name, $ufile[$i]['ufile_name']);

                if (!$uploader->upload($ufile[$i]['ufile_name'])) {
                    Valid::error('file'.$i, '첨부파일'.$i.' 업로드 실패');
                }
            }
        }

        //썸네일 생성
        if ($CONF['use_s3'] == 'N') {
            $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id.'/thumb';
            $uploader->chkpath();

            for ($i = 0; $i < count($ufile_name); $i++) {
                $intd = explode(',', SET_IMGTYPE);
                $f_type = Func::get_filetype($ufile_name[$i]);

                for ($j = 0; $j <= count($intd) - 1; $j++) {
                    if ($f_type == trim($intd[$j])) {
                        $imgresize->set(
                            array(
                                'orgimg' => MOD_BOARD_DATA_PATH.'/'.$board_id.'/'.$ufile_name[$i],
                                'newimg' => $uploader->path.'/'.$ufile_name[$i],
                                'width' => 800
                            )
                        );
                        $imgresize->make();
                    }
                }
            }
        }

        //수정모드인 경우 기존 파일 & 썸네일 삭제
        if ($req['wrmode'] == 'modify') {
            for ($i = 1; $i <= 2; $i++) {

                //기존 파일을 삭제할 때
                if ($req['file'.$i.'_del'] == 'checked' || ($ufile[$i]['size'] > 0 && $org_arr['file'.$i] && $req['file'.$i.'_del'] != 'checked')) {
                    $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id;
                    $uploader->drop($org_arr['file'.$i]);
                    $uploader->path = MOD_BOARD_DATA_PATH.'/'.$board_id.'/thumb';
                    $uploader->drop($org_arr['file'.$i]);
                }

                //아무것도 하지 않았을 때
                if ($org_arr['file'.$i] != '' && !$ufile[$i]['tmp_name'] && $req['file'.$i.'_del'] != 'checked') {
                    $ufile[$i]['ufile_name'] = $org_arr['file'.$i];
                }
            }
        }

        //wrmode 별 처리
        switch ($req['wrmode']) {

            case 'reply' :
                $this->get_reply();
                break;

            case 'modify' :
                $this->get_modify();
                break;

            default :
                $this->get_write();
                break;
        }
    }

    ///
    // 새로운 글 작성
    ///
    private function get_write()
    {
        global $MODULE_BOARD_CONF, $MB, $req, $ufile, $wr_opt, $board_id;

        $sql = new Pdosql();

        //ln값 처리
        $sql->query(
            "
            SELECT MAX(ln)+1000 AS ln_max
            FROM {$sql->table("mod:board_data_".$board_id)}
            ", []
        );

        $ln_arr = array();
        $ln_arr['ln_max'] = $sql->fetch('ln_max');

        if (!$ln_arr['ln_max']) {
            $ln_arr['ln_max'] = 1000;
        }

        $ln_arr['ln_max'] = ceil($ln_arr['ln_max'] / 1000) * 1000;

        //회원인 경우 회원 정보를 필드에 입력
        if (IS_MEMBER) {
            $req['email'] = $MB['email'];
            $req['writer'] = $MB['name'];
        }

        //insert
        $sql->query(
            "
            INSERT INTO {$sql->table("mod:board_data_".$board_id)}
            (category,mb_idx,mb_id,writer,pwd,email,article,subject,file1,file2,use_secret,use_notice,use_html,use_email,ip,regdate,ln,rn,data_1,data_2,data_3,data_4,data_5,data_6,data_7,data_8,data_9,data_10)
            VALUES
            (:col1,:col2,:col3,:col4,:col5,:col6,:col7,:col8,:col9,:col10,:col11,:col12,'Y',:col13,'{$_SERVER['REMOTE_ADDR']}',now(),:col14,:col15,:col16,:col17,:col18,:col19,:col20,:col21,:col22,:col23,:col24,:col25)
            ",
            array(
                $req['category'], $MB['idx'], $MB['id'], $req['writer'], $req['password'], $req['email'], $req['article'], $req['subject'], $ufile[1]['ufile_name'], $ufile[2]['ufile_name'], $wr_opt['secret'], $wr_opt['notice'], $wr_opt['email'], $ln_arr['ln_max'], 0, $req['data_1'], $req['data_2'], $req['data_3'], $req['data_4'], $req['data_5'], $req['data_6'], $req['data_7'], $req['data_8'], $req['data_9'], $req['data_10']
            )
        );

        //작성된 글의 idx
        $sql->query(
            "
            SELECT idx
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE writer=:col1 AND subject=:col2 AND article=:col3
            ",
            array(
                $req['writer'], $req['subject'], $req['article']
            )
        );

        //관리자 Dashboard 소식 등록
        if (Write::$boardconf['use_mng_feed'] == 'Y') {
            Func::add_mng_feed(
                array(
                    'from' => $MODULE_BOARD_CONF['title'],
                    'msg' => '<strong>'.$req['writer'].'</strong>님이 <strong>'.Write::$boardconf['title'].'</strong> 게시판에 새로운 글을 등록했습니다.',
                    'link' => $req['thisuri'].'?mode=view&read='.$sql->fetch('idx')
                )
            );
        }

        //return
        Valid::set(
            array(
                'return' => 'alert->location',
                'location' => $req['thisuri'].'?mode=view&read='.$sql->fetch('idx').'&category='.urlencode($req['category_ed'])
            )
        );
        Valid::turn();
    }

    ///
    // 글 수정
    ///
    private function get_modify()
    {
        global $MB, $req, $org_arr, $ufile, $wr_opt, $board_id;

        $sql = new Pdosql();

        //공지사항 옵션 체크한 경우 답글이 있는지
        if ($req['use_notice'] == 'checked') {

            //최소/최대 ln값 구함
            $ln_min = (int)(ceil($org_arr['ln'] / 1000) * 1000) - 1000;
            $ln_max = (int)(ceil($org_arr['ln'] / 1000) * 1000);

            $sql->query(
                "
                SELECT *
                FROM {$sql->table("mod:board_data_".$board_id)}
                WHERE ln>:col1 AND ln<=:col2
                ",
                array(
                    $ln_min, $ln_max
                )
            );

            if ($sql->getCount() > 1) {
                $this->error('', '답글이 있는 게시글은 공지사항 옵션을 사용할 수 없습니다.');
            }
        }

        //Category 처리
        if ($org_arr['reply_cnt'] > 0) {
            $category = $org_arr['category'];

        } else {
            $category = $req['category'];
        }

        //writer 처리
        if ($org_arr['mb_idx'] == $MB['idx'] && IS_MEMBER) {
            $req['writer'] = $MB['name'];

        } else if ($org_arr['mb_idx'] != 0 && IS_MEMBER) {
            $req['writer'] = $org_arr['writer'];
        }

        //email & password 처리
        if (IS_MEMBER && $org_arr['mb_idx'] != 0) {
            $req['email'] = $org_arr['email'];
            $req['password'] = $org_arr['pwd'];
        }

        //update
        $sql->query(
            "
            UPDATE {$sql->table("mod:board_data_".$board_id)}
            SET category=:col1,writer=:col2,pwd=:col3,email=:col4,article=:col5,subject=:col6,file1=:col7,file2=:col8,use_secret=:col9,use_notice=:col10,use_html='Y',use_email=:col11,ip='{$_SERVER['REMOTE_ADDR']}',data_1=:col12,data_2=:col13,data_3=:col14,data_4=:col15,data_5=:col16,data_6=:col17,data_7=:col18,data_8=:col19,data_9=:col20,data_10=:col21
            WHERE idx=:col22
            ",
            array(
                $category, $req['writer'], $req['password'], $req['email'], $req['article'], $req['subject'], $ufile[1]['ufile_name'], $ufile[2]['ufile_name'], $wr_opt['secret'], $wr_opt['notice'], $wr_opt['email'], $req['data_1'], $req['data_2'], $req['data_3'], $req['data_4'], $req['data_5'], $req['data_6'], $req['data_7'], $req['data_8'], $req['data_9'], $req['data_10'],
                $req['read']
            )
        );

        //조회수 session
        Session::set_sess('BOARD_VIEW_'.$req['read'], $req['read']);

        //return
        Valid::set(
            array(
                'return' => 'alert->location',
                'location' => $req['thisuri'].'?mode=view&read='.$req['read'].'&page='.$req['page'].'&where='.$req['where'].'&keyword='.$req['keyword'].'&category='.urlencode($req['category_ed'])
            )
        );
        Valid::turn();
    }

    ///
    // 답글 작성
    ///
    private function get_reply()
    {
        global $MODULE_BOARD_CONF, $MB, $req, $org_arr, $ufile, $wr_opt, $board_id;

        $sql = new Pdosql();
        $mail = new Mail();
        $Alarm_Library = new Alarm_Library();

        //ln값 처리
        $ln_max = (int)$org_arr['ln'];
        $ln_min = (int)(ceil($org_arr['ln'] / 1000) * 1000) - 1000;
        $ln_me = (int)$org_arr['ln'] - 1;

        $sql->query(
            "
            UPDATE {$sql->table("mod:board_data_".$board_id)}
            SET ln=ln-1
            WHERE ln<:col1 AND ln>:col2 AND rn>0
            ",
            array(
                $ln_max, $ln_min
            )
        );

        //rn값 처리
        $sql->query(
            "
            SELECT rn+1 AS rn_max
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE idx=:col1
            ",
            array(
                $req['read']
            )
        );

        $rn_arr = array();
        $rn_arr['rn_max'] = $sql->fetch('rn_max');

        //회원인 경우 정보를 필드에 기록
        if (IS_MEMBER) {
            $req['email'] = $MB['email'];
            $req['writer'] = $MB['name'];
        }

        //비회원의 비밀글에 대한 답글인 경우 원본글의 비밀번호를 기록
        if ($org_arr['use_secret'] == 'Y' && $org_arr['mb_idx'] == 0) {
            $req['password'] = $org_arr['pwd'];
        }

        //insert
        $sql->query(
            "
            INSERT INTO {$sql->table("mod:board_data_".$board_id)}
            (category,mb_idx,mb_id,writer,pwd,email,article,subject,file1,file2,use_secret,use_notice,use_html,use_email,ip,regdate,ln,rn,data_1,data_2,data_3,data_4,data_5,data_6,data_7,data_8,data_9,data_10)
            VALUES
            (:col1,:col2,:col3,:col4,:col5,:col6,:col7,:col8,:col9,:col10,:col11,:col12,'Y',:col13,'{$_SERVER['REMOTE_ADDR']}',now(),:col14,:col15,:col16,:col17,:col18,:col19,:col20,:col21,:col22,:col23,:col24,:col25)
            ",
            array(
                $org_arr['category'], $MB['idx'], $MB['id'], $req['writer'], $req['password'], $req['email'], $req['article'], $req['subject'], $ufile[1]['ufile_name'], $ufile[2]['ufile_name'], $wr_opt['secret'], $wr_opt['notice'], $wr_opt['email'], $ln_me, $rn_arr['rn_max'], $req['data_1'], $req['data_2'], $req['data_3'], $req['data_4'], $req['data_5'], $req['data_6'], $req['data_7'], $req['data_8'], $req['data_9'], $req['data_10']
            )
        );

        //작성된 글의 idx
        $sql->query(
            "
            SELECT idx
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE writer=:col1 AND subject=:col2 AND article=:col3
            ",
            array(
                $req['writer'], $req['subject'], $req['article']
            )
        );

        //원본글이 답글 이메일 수신 옵션이 켜져 있는 경우 원본글 작성자에게 메일 발송
        if ($org_arr['use_email'] == 'Y') {
            $memo = '
                <strong>'.Write::$boardconf['title'].'</strong>에 게시한<br /><br />
                회원님의 게시글에 답글이 달렸습니다.<br />
                아래 주소를 클릭하여 확인 할 수 있습니다.<br /><br />

                <a href=\''.PH_DOMAIN.$req['thisuri'].'?mode=view&read='.$sql->fetch('idx').'&category='.urlencode($req['category_ed']).'\'>'.PH_DOMAIN.$req['thisuri'].'?mode=view&read='.$sql->fetch('idx').'&category='.urlencode($req['category_ed']).'</a>
            ';
            $mail->set(
                array(
                    'to' => array(
                        [
                            'email' => $org_arr['email']
                        ]
                    ),
                    'subject' => '회원님의 게시글에 답글이 달렸습니다.',
                    'memo' => str_replace('\"','"',stripslashes($memo))
                )
            );
            $mail->send();
        }

        //조회수 session
        Session::set_sess('BOARD_VIEW_'.$sql->fetch('idx'), $sql->fetch('idx'));

        //관리자 최근 피드에 등록
        if (Write::$boardconf['use_mng_feed'] == 'Y') {
            Func::add_mng_feed(
                array(
                    'from' => $MODULE_BOARD_CONF['title'],
                    'msg' => '<strong>'.$req['writer'].'</strong>님이 <strong>'.Write::$boardconf['title'].'</strong> 게시판에 새로운 답글을 등록했습니다.',
                    'link' => $req['thisuri'].'?mode=view&read='.$sql->fetch('idx')
                )
            );
        }

        //원글 작성자에게 알림 발송
        if ($req['wrmode'] == 'reply' && $org_arr['mb_idx'] > 0 && $org_arr['mb_idx'] != $MB['idx']) {
            $Alarm_Library->get_add_alarm(
                array(
                    'msg_from' => '게시판 ('.Write::$boardconf['title'].')',
                    'from_mb_idx' => $MB['idx'],
                    'to_mb_idx' => $org_arr['mb_idx'],
                    'memo' => '<strong>'.$req['writer'].'</strong>님이 회원님의 게시글에 답글을 작성했습니다.',
                    'link' => $req['thisuri'].'?mode=view&read='.$sql->fetch('idx')
                )
            );
        }

        //return
        Valid::set(
            array(
                'return' => 'alert->location',
                'location' => $req['thisuri'].'?mode=view&read='.$sql->fetch('idx').'&page='.$req['page'].'&where='.$req['where'].'&keyword='.$req['keyword'].'&category='.urlencode($req['category_ed'])
            )
        );
        Valid::turn();
    }

}
