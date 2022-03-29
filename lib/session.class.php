<?php
namespace Corelib;

class Session {

    static function set_sess($name,$val)
    {
        if ($name == 'MB_IDX') {
            SessionHandler::$dbinfo['mb_idx'] = $val;
        }
        $_SESSION[$name] = $val;
    }

    static function empty_sess($name)
    {
        global $_SESSION;

        if ($name == 'MB_IDX') {
            SessionHandler::$dbinfo['mb_idx'] = 0;
        }
        unset($_SESSION[$name]);
    }

    static function drop_sess()
    {
        session_destroy();
    }

    static function sess($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];

        } else {
            return null;
        }
    }

    static function is_sess($name)
    {
        if (isset($_SESSION[$name])) {
            return true;
        } else {

            return false;
        }
    }

}

class SessionHandler extends \Make\Database\Pdosql {

    private $value;
    private $sess_life = SET_SESS_LIFE;
    private $expiry;
    static public $dbinfo = array();

    public function open()
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($key)
    {
        $this->query(
            "
            SELECT *
            FROM {$this->table("session")}
            WHERE sesskey=:col1 AND expiry>
            ".time(),
            array(
                $key
            )
        );
        $this->specialchars = 0;
        $this->nl2br = 0;

        if ($this->getcount() > 0) {
            return $this->fetch('value');

        } else {
            $this->expiry = time() + $this->sess_life;
            $this->query(
                "
                INSERT INTO {$this->table("session")}
                (sesskey,expiry,value,mb_idx,ip,regdate)
                VALUES
                (:col1,:col2,0,0,:col3,now())
                ",
                array(
                    $key,
                    $this->expiry,
                    $_SERVER['REMOTE_ADDR']
                )
            );

            return $this->fetch('value');
        }
        return true;
    }

    public function write($key, $val)
    {
        $this->value = $val;
        $this->expiry = time() + $this->sess_life;

        if (isset(self::$dbinfo['mb_idx'])) {
            $this->query(
                "
                UPDATE {$this->table("session")}
                SET expiry=:col1,value=:col2,regdate=now(),mb_idx=:col3
                WHERE sesskey=:col4 AND expiry>
                ".time(),
                array(
                    $this->expiry,
                    $this->value,
                    self::$dbinfo['mb_idx'],
                    $key
                )
            );

        } else {
            $this->query(
                "
                UPDATE {$this->table("session")}
                SET expiry=:col1,value=:col2,regdate=now()
                WHERE sesskey=:col3 AND expiry>
                ".time(),
                array(
                    $this->expiry,
                    $this->value,
                    $key
                )
            );
        }
        return true;
    }

    public function destroy($key)
    {
        $this->query(
            "
            DELETE
            FROM {$this->table("session")}
            WHERE sesskey=:col1
            ",
            array(
                $key
            )
        );
        return true;
    }

    public function gc(){
        $this->query(
            "
            DELETE
            FROM {$this->table("session")}
            WHERE expiry<
            ".time(), ''
        );
        return true;
    }

}

$sess_init = new SessionHandler();
session_set_save_handler(
    array($sess_init, 'open'),
    array($sess_init, 'close'),
    array($sess_init, 'read'),
    array($sess_init, 'write'),
    array($sess_init, 'destroy'),
    array($sess_init, 'gc')
);

if (ini_get('session.auto_start') != 1) {
    session_start();
}
