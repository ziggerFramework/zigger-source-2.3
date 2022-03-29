<?php
use Corelib\Func;
use Corelib\Method;

require_once './lib/ph.core.php';

$REQUEST = Method::request('get', 'rewritepage, rewritetype');

if (!isset($rewritepage) && isset($REQUEST['rewritepage'])) {
    $rewritepage = $REQUEST['rewritepage'];

} else if (!isset($rewritepage) || !$rewritepage) {
    $rewritepage = "index";
}

$REL_HREF = explode('/', $rewritepage);

$REL_PATH = array(
    'page_name' => 'index',
    'class_name' => 'index',
    'full_path' => '',
    'first_path' => '',
    'namespace' => ''
);

if (count($REL_HREF) > 1) {
    $REL_PATH['page_name'] = $REL_HREF[count($REL_HREF) - 2];
    $REL_PATH['class_name'] = $REL_HREF[count($REL_HREF) - 1];
    $REL_PATH['full_path'] = str_replace('/'.$REL_PATH['page_name'].'/'.$REL_PATH['class_name'], '', '/'.$rewritepage);
    $REL_PATH['first_path'] = $REL_HREF[0];
    $REL_PATH['namespace'] = '';

} else if ($rewritepage != 'index') {
    $REL_PATH['page_name'] = $REL_HREF[0];
    $REL_PATH['class_name'] = 'Index';
}

$root = PH_PATH;
$root_dir = opendir($root);
$root_index = [];

while ($dir = readdir($root_dir)) {
    if ($dir != '.' && $dir != '..' && $dir != 'app') {
        $root_index[$dir] = $dir;
    }
}

$includeFile = PH_PATH.$REL_PATH['full_path'].'/'.$REL_PATH['page_name'].'.php';

if (in_array($REL_PATH['first_path'], $root_index) === false) {
    $includeFile = PH_PATH.'/app'.$REL_PATH['full_path'].'/'.$REL_PATH['page_name'].'.php';

}

if (strpos($rewritepage, 'manage/mod/') !== false) {
    $relEx = explode('/', $REL_PATH['full_path']);
    $includeFile = PH_MOD_PATH.'/'.$relEx[count($relEx) - 1].'/manage.set/'.$REL_PATH['page_name'].'.php';
}

if ($REL_PATH['first_path'] == 'mod') {
    $moduleNameEx = explode('/', str_replace(PH_PATH.'/mod/', '', $includeFile));

    $REL_PATH['namespace'] = 'Module\\'.$moduleNameEx[0].'\\';
}

$class_name = ucfirst($REL_PATH['class_name']);
$class_name = str_replace('-', '_', $class_name);
$class_name = str_replace('.', '_', $class_name);
$class_name = $REL_PATH['namespace'].$class_name;

if (file_exists($includeFile)) {
    require_once $includeFile;

} else {

    if (isset($REQUEST['rewritetype']) && $REQUEST['rewritetype'] == 'submit') {
        Func::core_err('Submit 파일 경로가 올바르지 않습니다.');
        exit;

    } else {
        Func::location(PH_DIR.'/error/code404');
        exit;
    }

}

if (class_exists($class_name) === false) {

    if (isset($REQUEST['rewritetype']) && $REQUEST['rewritetype'] == 'submit') {
        Func::core_err('Submit Class 가 올바르지 않습니다.');
        exit;

    } else {
        Func::location(PH_DIR.'/error/code404');
        exit;
    }
}

$$class_name = new $class_name();

if (method_exists($$class_name, 'func') !== false) {
    $$class_name->func();
}

$$class_name->init();
