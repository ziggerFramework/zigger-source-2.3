<?php
use Corelib\Method;
use Corelib\Func;
use Make\Database\Pdosql;

include_once '../lib/ph.core.php';

$sql = new Pdosql();

Method::security('request_get');
$req = Method::request('get', 'idx, key');

if (!$req['idx'] || !$req['key']) {
    Func::location(PH_DOMAIN);
}

$sql->query(
    "
    SELECT link
    FROM {$sql->table("banner")}
    WHERE idx=:col1 AND bn_key=:col2
    ",
    array(
        $req['idx'], $req['key']
    )
);

if ($sql->getcount() < 1) {
    Func::location(PH_DOMAIN);
}

$link = $sql->fetch('link');

$sql->query(
    "
    UPDATE
    {$sql->table("banner")}
    SET hit=hit+1
    WHERE idx=:col1 AND bn_key=:col2
    ",
    array(
        $req['idx'], $req['key']
    )
);

Func::location($link);
