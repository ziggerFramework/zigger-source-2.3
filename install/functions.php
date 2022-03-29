<?php
function permschk($dir)
{
    return is_writable($dir);
}

function phpversions()
{
    $version = (string)phpversion();
    if ($version > '5.5.0') {
        return true;
    } else {
        return false;
    }
}

function extschk($exts)
{
    $loaded = extension_loaded($exts);
    if ($loaded !== false) {
        return true;

    } else {
        return false;
    }
}

function step1_chk()
{
    if (
        permschk('../data/') !== false &&
        permschk('../robots.txt') !== false &&
        phpversions() !== false &&
        extschk('GD') !== false &&
        extschk('mbstring') !== false &&
        extschk('PDO') !== false &&
        extschk('curl') !== false
    ) {
        return true;

    } else {
        return false;
    }
}
