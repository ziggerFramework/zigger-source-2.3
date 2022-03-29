<?php
use Corelib\Session;
use Corelib\SessionHandler;
use Corelib\Func;
use Corelib\Blocked;
use Make\Database\Pdosql;

$varsql = new Pdosql();

//Modules
$mpath = PH_MOD_PATH;
$mopen = opendir($mpath);
$midx = 0;

while ($dir = readdir($mopen)) {
    if ($dir != '.' && $dir != '..') {
        $MODULE[$midx] = $dir;
        $midx++;
    }
}

sort($MODULE);

//Themes
$tpath = PH_PATH.'/theme/';
$topen = opendir($tpath);
$tidx = 0;

while ($dir = readdir($topen)) {
    if ($dir != '.' && $dir != '..') {
        $THEME[$tidx] = $dir;
        $tidx++;
    }
}

//Default information
$CONF = array();

$varsql->query(
    "
    SELECT *
    FROM {$varsql->table("config")}
    WHERE cfg_type='engine'
    ", []
);
if ($varsql->getcount() > 0) {
    do {
        $cfg = $varsql->fetchs();
        $CONF[$cfg['cfg_key']] = $cfg['cfg_value'];

        if ($cfg['cfg_key'] == 'script' || $cfg['cfg_key'] == 'meta') {
            $varsql->specialchars = 0;
            $varsql->nl2br = 0;

            $CONF[$cfg['cfg_key']] = $varsql->fetch('cfg_value');
        }

    } while($varsql->nextRec());
}

//default Icons
$icons = array(
    'favicon', 'logo', 'og_image'
);
foreach ($icons as $key => $value) {
    if ($CONF[$value]) {
        $icon = Func::get_fileinfo($CONF[$value], false);
        if ($icon['storage'] == 'Y') {
            $CONF[$value] = $CONF['s3_key1'].'/'.$CONF['s3_key2'].'/manage/'.$icon['repfile'];

        } else {
            $CONF[$value] = PH_DATA_DIR.'/manage/'.$icon['repfile'];
        }
    }
}

//Theme constants
define('PH_THEME', $CONF['theme']); //Theme 경로
define('PH_THEME_DIR', PH_DIR.'/theme/'.$CONF['theme']); //Theme 경로
define('PH_THEME_PATH', PH_PATH.'/theme/'.$CONF['theme']); //Theme PHP 경로

//회원이라면, 회원의 기본 정보 가져옴
define('IS_MEMBER', Session::is_sess('MB_IDX'));

if (IS_MEMBER) {
    define('MB_IDX',Session::sess('MB_IDX'));

} else {
    define('MB_IDX',NULL);
}

$MB = array();

if (IS_MEMBER) {
    $varsql->query(
        "
        SELECT *
        FROM {$varsql->table("member")}
        WHERE mb_idx=:col1
        ",
        array(
            MB_IDX
        )
    );

    $mb_arr = $varsql->fetchs();

    foreach ($mb_arr as $key => $value) {
        $key = str_replace('mb_', '', $key);
        $MB[$key] = $value;
    }

    for ($i=1; $i <= 10; $i++) {
        $MB['mb_'.$i] = $MB[$i];
        unset($MB[$i]);
    }

} else {
    $MB = array(
        'level' => 10,
        'adm' => null,
        'idx' => 0,
        'id' => null,
        'pwd' => null,
        'email' => null,
        'name' => null,
        'phone' => null,
        'telephone' => null
    );
}

//회원 레벨별 명칭 배열화
$MB['type'] = array();
$vars = explode('|', $CONF['mb_division']);
for ($i=1; $i <= 10; $i++) {
    $MB['type'][$i] = $vars[$i - 1];
}

//업데이트 초기화 확인
Func::chk_update_config_field(
    array(
        'use_sms', 'use_feedsms', 'sms_toadm', 'sms_from', 'sms_key1', 'sms_key2', 'sms_key3', 'sms_key4', //ver 2.2.1
        'use_mb_phone:N', 'use_phonechk:N', 'use_mb_telephone:N', 'use_mb_address:N', 'use_mb_gender:N' //ver 2.2.2
    )
);
