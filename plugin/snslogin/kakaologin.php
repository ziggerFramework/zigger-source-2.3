<?php
use Corelib\Func;
use Corelib\Method;
use Corelib\Session;
use Make\Database\Pdosql;

include_once '../../lib/ph.core.php';

$req = Method::request('get', 'code, state');

$req['redirect'] = $req['state'];

$sql = new Pdosql();

if (IS_MEMBER) {
    Func::err_location('이미 로그인 되어 있습니다.', PH_DOMAIN);
}

if (!$req['code'] || !$req['state']) {
    Func::location(PH_DOMAIN);
}

//카카오 로그인 콜백
$client_id = $CONF['sns_ka_key1'];
$client_secret = $CONF['sns_ka_key2'];
$state = $_GET['state'];
$redirectURI = urlencode(PH_DOMAIN.'/plugin/snslogin/kakaologin.php');
$url = 'https://kauth.kakao.com/oauth/token?client_id='.$client_id.'&grant_type=authorization_code&redirect_uri='.$redirectURI.'&code='.$req['code'].'&client_secret='.$client_secret.'&state='.$state;
$is_post = false;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, $is_post);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$headers = array();
$res = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$res = json_decode($res,true);

//성공한 경우 획득한 Token으로 계정 정보 불러옴
if ($status_code == 200 && isset($res['access_token'])) {
    $token = $res['access_token'];
    $header = 'Bearer '.$token; // Bearer 다음에 공백 추가
    $url = 'https://kapi.kakao.com/v2/user/me';
    $is_post = false;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, $is_post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $headers = array();
    $headers[] = 'Authorization: '.$header;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $res = json_decode($res,true);

    if ($status_code == 200) {

        //회원 정보를 받아 옴
        $kakao_arr = array();
        $kakao_arr['token'] = $token;
        @$kakao_arr['id'] = $res['id'];
        @$kakao_arr['name'] = $res['kakao_account']['profile']['nickname'];
        @$kakao_arr['email'] = $res['kakao_account']['email'];
        @$kakao_arr['gender'] = $res['kakao_account']['gender'];

    }

    if ($status_code != 200) {
        echo 'Error : Kakao 로그인 오류';
        exit;
    }
}

//실패한 경우 error 출력
else if ($status_code != 200 || !isset($res['access_token'])) {
    echo 'Error : Kakao 로그인 오류';
    exit;
}

//중복되는 이메일이 아닌 경우 그대로 회원가입에 활용, 중복되는 경우 비워둠
$kakao_inf = array();
$kakao_inf['email'] = '';
if ($kakao_arr['email']) {
    $sql->query(
        "
        SELECT *
        FROM {$sql->table("member")}
        WHERE mb_email=:col1 AND mb_dregdate IS NULL
        ",
        array(
            $kakao_arr['email']
        )
    );

    if ($sql->getcount() < 1) {
        $kakao_inf['email'] = $kakao_arr['email'];
    }
}

//이름 처리
$match = REGEXP_NICK;
$kakao_inf['name'] = '회원'.rand(1,999);
if ($kakao_arr['name']) {
    if (preg_match($match, $kakao_arr['name'])) {
        $kakao_inf['name'] = $kakao_arr['name'];
    }
}

//성별 처리
$kakao_inf['gender'] = '';
if ($kakao_arr['gender']) {
    $kakao_inf['gender'] = strtoupper(substr($kakao_arr['gender'], 0, 1));
}

//임의 회원 아이디 생성
$kakao_inf['usrid'] = 'kakao'.$kakao_arr['id'];

//임의 회원 비밀번호 생성
$kakao_inf['pwd'] = 'kakao'.$kakao_arr['id'].date('ymdhis',time()).rand(0, 9999);

//가입여부 확인
$sql->query(
    "
    SELECT *
    FROM {$sql->table("member")}
    WHERE mb_sns_ka=:col1 AND mb_dregdate IS NULL
    ",
    array(
        $kakao_arr['id']
    )
);
$mb_joined = true;
if ($sql->getcount() < 1) {
    $mb_joined = false;
}

if (!$kakao_inf['gender']) {
    $kakao_inf['gender'] = 'M';
}

//가입되지 않은 카카오 회원인 경우 가입 처리
if (!$mb_joined) {
    $sql->query(
        "
        INSERT INTO {$sql->table("member")}
        (mb_id,mb_email,mb_pwd,mb_name,mb_gender,mb_phone,mb_telephone,mb_email_chk,mb_regdate,mb_1,mb_2,mb_3,mb_4,mb_5,mb_6,mb_7,mb_8,mb_9,mb_10,mb_sns_ka,mb_sns_nv,mb_sns_ka_token,mb_sns_nv_token,mb_exp)
        VALUES
        (:col1,:col2,password(:col3),:col4,:col5,:col6,:col7,:col8,now(),:col9,:col10,:col11,:col12,:col13,:col14,:col15,:col16,:col17,:col18,:col19,:col20,:col21,:col22,:col23)
        ",
        array(
            $kakao_inf['usrid'], $kakao_inf['email'], $kakao_inf['pwd'], $kakao_inf['name'], $kakao_inf['gender'], '', '', 'Y', '', '', '', '', '', '', '', '', '', '', $kakao_arr['id'], '', $kakao_arr['token'], '', $sql->etcfd_exp('')
        )
    );
}

//가입되어있는 경우 Token키 업데이트
if ($mb_joined) {
    $sql->query(
        "
        UPDATE {$sql->table("member")}
        SET mb_sns_ka_token=:col1
        WHERE mb_sns_ka=:col2 AND mb_dregdate IS NULL
        ",
        array(
            $kakao_arr['token'], $kakao_arr['id']
        )
    );
}

//로그인 정보 로드
$sql->query(
    "
    SELECT *
    FROM {$sql->table("member")}
    WHERE mb_sns_ka=:col1 AND mb_dregdate IS NULL
    ",
    array(
        $kakao_arr['id']
    )
);

$mbinfo = array();
$mbinfo['id'] = $sql->fetch('mb_id');
$mbinfo['idx'] = $sql->fetch('mb_idx');
$mbinfo['name'] = $sql->fetch('mb_name');

//로그인 session 처리
Session::set_sess('MB_IDX', $mbinfo['idx']);

//최근 로그인 내역 기록
$sql->query(
    "
    UPDATE {$sql->table("member")}
    SET mb_lately_ip=:col1,mb_lately=now()
    WHERE mb_idx=:col2
    ",
    array(
        $_SERVER['REMOTE_ADDR'],
        $mbinfo['idx']
    )
);

//관리자 최근 피드에 등록
if (!$mb_joined) {
    Func::add_mng_feed(
        array(
            'from' => 'SNS 회원가입',
            'msg' => '<strong>'.$mbinfo['name'].'</strong>님이 SNS 회원가입 했습니다.',
            'link' => '/manage/member/modify?idx='.$mbinfo['idx']
        )
    );
}

//로그인 완료 후 페이지 이동
Func::location(PH_DOMAIN.urldecode($req['redirect']));
