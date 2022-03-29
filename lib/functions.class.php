<?php
namespace Corelib;

use Make\Database\Pdosql;
use Make\Library\Sms;

class Func {

    static public function chk_update_config_field($fields)
    {
        global $CONF;

        foreach ($fields as $key => $value) {

            $field_value = '';
            $field_key = $value;

            if (strstr($value, ':')) {
                $val_exp = explode(':', $value);
                $field_value = $val_exp[1];
                $field_key = $val_exp[0];
            }

            if (!isset($CONF[$field_key])) {

                $sql = new Pdosql();
                $sql->query(
                    "
                    INSERT INTO
                    {$sql->table("config")}
                    (cfg_type,cfg_key,cfg_value,cfg_regdate)
                    VALUES
                    ('engine','{$field_key}','{$field_value}',now())
                    ", []
                );
            }

        }
    }

    static public function add_stylesheet($file)
    {
        global $ob_src_css;

        if (!strstr($ob_src_css,$file)) {
            $date_cache = md5(date('Ymd'));
            $ob_src_css .= '<link rel="stylesheet" href="'.$file.SET_CACHE_HASH.'"/>'.PHP_EOL;
        }
    }

    static public function add_javascript($file)
    {
        global $ob_src_js;

        if (!strstr($ob_src_js,$file)) {
            $date_cache = md5(date('Ymd'));
            $ob_src_js .= '<script src="'.$file.SET_CACHE_HASH.'"></script>'.PHP_EOL;
        }
    }

    static public function define_javascript($name, $val)
    {
        global $ob_define_js;

        $ob_define_js .= PHP_EOL.'var '.$name.' = "'.$val.'";';
    }

    static public function print_javascript($source)
    {
        global $ob_src_js;

        $ob_src_js .= '<script type="text/javascript">'.PHP_EOL;
        $ob_src_js .= $source.PHP_EOL;
        $ob_src_js .= '</script>'.PHP_EOL;
    }

    static public function add_title($title)
    {
        global $CONF, $ob_title, $ob_ogtitle;

        $ob_title .= '<title>'.$CONF['title'].' - '.$title.'</title>'.PHP_EOL;
        $ob_ogtitle .= '<meta property="og:title" content="'.$CONF['og_title'].' - '.$title.'" />'.PHP_EOL;
    }

    //page key 셋팅
    static public function set_category_key($key)
    {
        define('SET_CATEGORY_KEY', $key);
    }

    //Date Format (날짜만)
    static public function date($str)
    {
        if ($str != '') {
            return date(SET_DATE,strtotime($str));
        } else {
            return '';
        }
    }

    //Date Format (날짜와 시간)
    static public function datetime($str)
    {
        if ($str != '') {
            return date(SET_DATETIME,strtotime($str));
        } else {
            return '';
        }
    }

    //Number로 치환
    static public function number($str)
    {
        return number_format((int)$str);
    }

    //파일 사이즈 단위 계산
    static public function getbyte($size, $byte)
    {
        $byte = strtolower($byte);

        if ($byte == 'k') {
            $size = number_format((int)$size / 1024, 0);

        } else if ($byte == 'm') {
            $size = number_format((int)$size / 1024 / 1024, 1);

        } else if ($byte == 'g') {
            $size = number_format((int)$size / 1024 / 1024 / 1024, 1);
        }
        return $size;
    }

    //file_get_contents 대체함수 (curl)
    static public function url_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    //파일 사이즈 표시
    static public function filesize($file, $byte)
    {
        $byte = strtolower($byte);

        if ($byte == 'k') {
            $size = number_format(filesize($file) / 1024, 0);

        } else if ($byte == 'm') {
            $size = number_format(filesize($file) / 1024 / 1024, 1);

        } else if ($byte == 'g') {
            $size = number_format(filesize($file) / 1024 / 1024 / 1024, 1);
        }
        return $size;
    }

    //로그인이 되어있지 않다면 로그인 화면으로 이동
    static public function getlogin($msg)
    {
        if (!IS_MEMBER) {
            if ($msg) {
                self::alert($msg);
            }
            $url = $_SERVER['REQUEST_URI'];
            self::location_parent(PH_DOMAIN.'/sign/signin?redirect='.urlencode($url));
        }
    }

    //회원 level 체크
    static public function chklevel($level)
    {
        global $MB;

        if ($MB['level'] > $level) {
            self::err_back(ERR_MSG_10);
        }
    }

    //Device 체크
    static public function chkdevice()
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        $mobile = explode(',', SET_MOBILE_DEVICE);
        $chk_count = 0;

        for ($i=0; $i < sizeof($mobile); $i++) {
            if (preg_match("/$mobile[$i]/", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                $chk_count++;
                break;
            }
        }
        if ($chk_count>0) {
            return 'mobile';
        } else {
            return 'pc';
        }
    }

    //문자열 유효성 검사
    static public function chkintd($type, $val, $intd)
    {
        $intd = explode(',', $intd);
        $chk = true;

        for ($i=0; $i<=sizeof($intd)-1; $i++) {
            if (strpos($val, trim($intd[$i])) !== false) {
                $chk = false;
            }
        }
        if ($type == 'notmatch') {
            if ($chk === false) {
                return false;
            } else {
                return true;
            }
        } else if ($type == 'match') {
            if ($chk === false){
                return true;
            } else {
                return false;
            }
        }
    }

    //문자열 자르기
    static public function strcut($str, $start, $end)
    {
        $cutstr = mb_substr($str, $start, $end, 'UTF-8');

        if (strlen($cutstr) < strlen($str)) {
            return $cutstr.'···';
        }else{
            return $cutstr;
        }
    }

    //회원 포인트 적립 or 차감 처리
    static public function set_mbpoint($arr)
    {
        if (!$arr['mb_idx'] || $arr['mb_idx'] < 1 || $arr['point'] < 1) {
            return;
        }

        $sql = new Pdosql();

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("member")}
            WHERE mb_idx=:col1
            ",
            array(
                $arr['mb_idx']
            )
        );
        $mb_point = $sql->fetch('mb_point');

        if ($arr['mode'] == 'in') {
            $set_point = (int)$mb_point + (int)$arr['point'];
            $sql->query(
                "
                INSERT INTO {$sql->table("mbpoint")}
                (mb_idx,p_in,memo,regdate)
                VALUES
                (:col1,:col2,:col3,now())
                ",
                array(
                    $arr['mb_idx'],
                    $arr['point'],
                    $arr['msg']
                )
            );
        }

        if ($arr['mode'] == 'out') {

            if ($mb_point < $arr['point']) {
                $set_point = 0;
                $arr['msg'] .= ' (차감할 포인트 부족으로 0 처리)';

            } else {
                $set_point = $mb_point - $arr['point'];
            }

            $sql->query(
                "
                INSERT INTO {$sql->table("mbpoint")}
                (mb_idx,p_out,memo,regdate)
                VALUES
                (:col1,:col2,:col3,now())
                ",
                array(
                    $arr['mb_idx'],
                    $arr['point'],
                    $arr['msg']
                )
            );
        }

        $sql->query(
            "
            UPDATE {$sql->table("member")}
            SET mb_point=:col1
            WHERE mb_idx=:col2
            ",
            array(
                $set_point,
                $arr['mb_idx']
            )
        );
    }

    //관리자 최근 피드에 등록
    static public function add_mng_feed($arr)
    {
        global $CONF;

        $sql = new Pdosql();

        $sql->query(
            "
            INSERT into {$sql->table("mng_feeds")}
            (msg_from,memo,href,regdate)
            VALUES
            (:col1,:col2,:col3,now())
            ",
            array(
                $arr['from'],
                $arr['msg'],
                $arr['link']
            )
        );

        if ($CONF['use_feedsms'] == 'Y') {
            $sms = new Sms();

            $sms->set(
                array(
                    'memo' => '[zigger] '.strip_tags($arr['msg']),
                    'to' => [
                        $CONF['sms_toadm']
                    ]
                )
            );
            $sms->send();
        }
    }

    //Captcha 출력 및 검증
    static public function get_captcha($id = 'captcha', $type = 1)
    {
        global $CONF, $PLUGIN_CAPTCHA_CONF;

        if ($id == '') {
            $id = 'captcha';
        }

        $PLUGIN_CAPTCHA_CONF['id'] = $id;

        if ($CONF['use_recaptcha'] == 'Y') {
            self::add_javascript('https://www.google.com/recaptcha/api.js?onload=g_recaptcha_'.$id.'&render=explicit');
            self::print_javascript('var g_recaptcha_'.$id.'_act = 0;var g_recaptcha_'.$id.'_aload = '.$type.';var g_recaptcha_'.$id.'_rend;var g_recaptcha_'.$id.' = function(aload){if((g_recaptcha_'.$id.'_aload==1 || aload==1) && document.getElementById(\'g-recaptcha-'.$id.'\')){g_recaptcha_'.$id.'_rend = grecaptcha.render(\'g-recaptcha-'.$id.'\',{\'sitekey\' : \''.$CONF['recaptcha_key1'].'\'});}g_recaptcha_'.$id.'_act++;}');
            $html = '<div class="g-recaptcha" id="g-recaptcha-'.$id.'" data-name="'.$id.'"></div>';
            $html .= '<textarea name="'.$id.'" id="'.$id.'" style="display: none;"></textarea>';

        } else {

            require_once PH_PLUGIN_PATH.'/'.PH_PLUGIN_CAPTCHA.'/securimage.php';
            $opt = array(
                'input_name' => $id,
                'disable_flash_fallback' => true
            );

            $html = '<div id="zigger-captcha">';
            $html .= \Securimage::getCaptchaHtml($opt);
            $html .= '<input type="text" name="'.$id.'" id="'.$id.'" class="inp" value="" />';
            $html .= '</div>';
        }
        return $html;
    }

    static public function chk_captcha($val)
    {
        global $CONF;

        if ($CONF['use_recaptcha'] == 'Y') {
            $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.$CONF['recaptcha_key2'].'&response='.$val.'&remoteip='.$_SERVER['REMOTE_ADDR'];
            $req = json_decode(self::url_get_contents($url), true);

            if ($req['success']) {
                return true;
            } else {
                return false;
            }

        } else {
            require_once PH_PLUGIN_PATH.'/'.PH_PLUGIN_CAPTCHA.'/securimage.php';
            $securimage = new \Securimage();

            if ($securimage->check($val) === true) {
                return true;
            } else {
                return false;
            }
        }
    }

    //파일 확장명 추출
    static public function get_filetype($file)
    {
        $fn = explode('.', $file);
        $fn = array_pop($fn);
        return strtolower($fn);
    }

    //파일 Upload data 정보
    static public function get_fileinfo($file, $detail = true)
    {
        global $CONF;

        $return = '';

        //detail
        if ($detail === true) {

            $sql = new Pdosql();
            $sql->query(
                "
                SELECT *
                FROM {$sql->table("dataupload")}
                WHERE repfile='{$file}'
                ", []
            );
            $arr = $sql->fetchs();

            if ($sql->getcount() < 1) {
                return false;
            }

            $orglink = PH_DATA_DIR.$arr['filepath'].'/'.$arr['orgfile'];
            $replink = PH_DATA_DIR.$arr['filepath'].'/'.$arr['repfile'];

            if ($arr['storage'] == 'Y') {
                $orglink = $CONF['s3_key1'].'/'.$CONF['s3_key2'].$arr['filepath'].'/'.$arr['orgfile'];
                $replink = $CONF['s3_key1'].'/'.$CONF['s3_key2'].$arr['filepath'].'/'.$arr['repfile'];
            }

            $data = array(
                'filepath' => $arr['filepath'],
                'orgfile' => $arr['orgfile'],
                'repfile' => $arr['repfile'],
                'replink' => $replink,
                'storage' => $arr['storage'],
                'byte' => $arr['byte'],
                'regdate' => $arr['regdate']
            );

            $return = $data;

        }

        //simple
        else {

            $storage = 'N';

            $fileType = Func::get_filetype($file);

            if (substr(str_replace('.'.$fileType, '', $file), -1, 1) == 'Y') {
                $storage = 'Y';
            }

            $data = array(
                'storage' => $storage,
                'repfile' => $file
            );

            $return = $data;

        }

        return $data;
    }

    //현재 PHP 파일명 반환
    static public function thispage()
    {
        return basename($_SERVER['PHP_SELF']);
    }

    //현재 PHP 경로(Directory) 반환
    static public function thisdir()
    {
        return str_replace('/'.basename(self::thisuri()), '', self::thisuri());
    }

    //현재 URI 반환
    static public function thisuri()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $qry = substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'],'&')+1);
        $uri = str_replace('?'.$qry, '', $uri);
        return $uri;
    }

    //현재 URI 반환 (쿼리 포함)
    static public function thisuriqry()
    {
        $uri = $_SERVER['REQUEST_URI'];
        return $uri;
    }

    //현재 Controller명 반환
    static public function thisctrlr()
    {
        global $REL_PATH;
        return $REL_PATH['page_name'];
    }

    //현재 Class명 반환
    static public function thisclass()
    {
        global $REL_PATH;
        return $REL_PATH['class_name'];
    }

    //htmlspecialchars_decode 리턴 함수
    //(mysql에서 Array된 변수값은 htmlspecialchars 기본 적용됨)
    static public function htmldecode($val)
    {
        return self::deHtmlspecialchars($val);
    }

    //deHtmlspecialchars 함수
    static public function deHtmlspecialchars($val)
    {
        return htmlspecialchars_decode($val);
    }

    //br2nl 함수
    static public function br2nl($val)
    {
        return preg_replace("/\<br(\s*)?\/?\>/i", '\n', $val);
    }

    //error : core error
    static public function core_err($msg)
    {
        echo '<div style="border-left: 4px solid #b82e24;background: #e54d42;padding: 3px 15px;margin:15px;">';
        echo '<p style="display: block;font-size: 13px;line-height:18px;color: #fff;letter-spacing: -1px;">Core error : '.$msg.'</p>';
        echo '</div>';
        exit;
    }

    //error : 오류메시지 화면에 출력
    static public function err_print($msg)
    {
        echo $msg;
        exit;
    }

    //error : alert만 띄움
    static public function err($msg)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">alert(\''.$msg.'\');</script>';
        exit;
    }

    //error : alert 띄운 뒤 뒤로 이동
    static public function err_back($msg)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">alert(\''.$msg.'\');history.back();</script>';
        exit;
    }

    //error : alert 띄운 뒤 설정한 페이지로 이동
    static public function err_location($msg,$url)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">alert(\''.$msg.'\');location.href=\''.$url.'\';</script>';
        exit;
    }

    //error : alert 띄운 뒤 윈도우 창 닫음
    static public function err_close($msg)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">alert(\''.$msg.'\');self.close();</script>';
        exit;
    }

    //exit 없는 alert 띄움
    static public function alert($msg)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">alert(\''.$msg.'\');</script>';
    }

    //페이지 이동
    static public function location($url)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">location.href=\''.$url.'\';</script>';
        exit;
    }

    //페이지 이동(_parent)
    static public function location_parent($url)
    {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script type="text/javascript">parent.location.href=\''.$url.'\';</script>';
        exit;
    }

}
