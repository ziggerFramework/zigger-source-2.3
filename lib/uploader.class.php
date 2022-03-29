<?php
namespace Make\Library;

use Corelib\Func;
use Make\Database\Pdosql;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class Uploader {

    public $path;
    public $file;
    public $intdict = SET_INTDICT_FILE;
    public $file_idx = 0;

    //파일 유무 검사
    public function isfile($file)
    {
        if (@is_file($file)) {
            return true;
        } else {
            return false;
        }
    }

    //디렉토리 유무 검사
    public function isdir($dir)
    {
        if (@is_dir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    //파일 검사
    public function chkfile($type)
    {
        $intd = explode(',', $this->intdict);
        $f_type = Func::get_filetype($this->file['name']);
        $chk = true;

        for ($i=0; $i <= count($intd)-1; $i++) {
            if ($f_type == $intd[$i]) {
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
            if ($chk === false) {
                return true;
            }else{
                return false;
            }
        }

    }

    //첨부 파일명 변환
    public function replace_filename($file)
    {
        global $CONF;

        $lastChar = 'N';

        if (isset($CONF['use_s3']) && $CONF['use_s3'] == 'Y') {
            $lastChar = 'Y';
        }
        $tstamp = md5(rand(0,999999999).date('ymdhis', time()));
        $tstamp .= md5($file);
        $file_name = $tstamp.$this->file_idx.$lastChar.'.'.Func::get_filetype($file);

        $this->file_idx++;

        return $file_name;
    }

    //파일 byte 검사
    public function chkbyte($limit)
    {
        $chked = true;
        if ($this->file['size'] > $limit) {
            $chked = false;
        }

        return $chked;
    }

    //저장 위치 검사 및 생성
    public function chkpath()
    {
        if (!is_dir($this->path)) {
            @mkdir($this->path, 0707);
            @chmod($this->path, 0707);
        }
    }

    //DB 기록
    private function record_dataupload($replace_filename, $filename = '')
    {
        global $CONF;

        $storage = 'N';
        if (isset($CONF['use_s3']) && $CONF['use_s3'] == 'Y') {
            $storage = 'Y';
        }

        $path = str_replace(PH_DATA_PATH, '', $this->path);

        if (!$filename) {
            $filename = $this->file['name'];
        }
        $sql = new Pdosql();
        $sql->query(
            "
            INSERT INTO {$sql->table("dataupload")}
            (filepath,orgfile,repfile,storage,byte,regdate)
            VALUES
            ('{$path}','{$filename}','{$replace_filename}','{$storage}',{$this->file['size']},now())
            ", []
        );
    }

    private function record_datacopy($org_file, $replace_filename)
    {
        $sql = new Pdosql();

        $fileinfo = Func::get_fileinfo($org_file);

        $sql->query(
            "
            SELECT *
            FROM {$sql->table("dataupload")}
            WHERE orgfile='{$fileinfo['orgfile']}' AND repfile='{$replace_filename}'
            ", []
        );

        if ($sql->getcount() > 0) {
            return;
        }

        $sql->query(
            "
            INSERT INTO {$sql->table("dataupload")}
            (filepath,orgfile,repfile,storage,byte,regdate)
            VALUES
            ('{$fileinfo['filepath']}','{$fileinfo['orgfile']}','{$replace_filename}','{$fileinfo['storage']}',{$fileinfo['byte']},now())
            ", []
        );
    }

    private function record_datadrop($replace_filename)
    {
        $sql = new Pdosql();
        $sql->query(
            "
            DELETE
            FROM {$sql->table("dataupload")}
            WHERE repfile='{$replace_filename}'
            ", []
        );
    }

    //S3
    private function get_s3_action($type, $filename, $copy_filename = '')
    {
        global $CONF;

        $s3 = S3Client::factory(
            array(
                'endpoint' => $CONF['s3_key1'],
                'version' => 'latest',
                'region' => $CONF['s3_key5'],
                'credentials' => array(
                    'key' => $CONF['s3_key3'],
                    'secret'  => $CONF['s3_key4'],
                )
            )
        );

        //upload
        if ($type == 'upload') {

            $awsSource = fopen($this->file['tmp_name'],'rb');

            try {
                $s3->putObject([
                    'Bucket' => $CONF['s3_key2'],
                    'Key' => str_replace(PH_DATA_PATH.'/', '', $this->path).'/'.$filename,
                    'Body' => $awsSource,
                    'ACL' => 'public-read'
                ]);

            } catch (S3Exception $e) {
                if ($e->getMessage()) {
                    Func::err_print(ERR_MSG_13);
                    return false;
                }
            }
        }

        //delete
        if ($type == 'delete') {
            try {
                $s3->deleteObject([
                    'Bucket' => $CONF['s3_key2'],
                    'Key' => str_replace(PH_DATA_PATH.'/', '', $this->path).'/'.$filename
                ]);

            } catch (S3Exception $e) {
                if ($e->getMessage()) {
                    return false;
                }
            }
        }

        //copy
        if ($type == 'copy') {
            try {
                $s3->copyObject([
                    'Bucket' => $CONF['s3_key2'],
                    'CopySource' => $CONF['s3_key2'].'/'.str_replace(PH_DATA_PATH.'/', '', $this->path).'/'.basename($filename),
                    'Key' => str_replace(PH_DATA_PATH.'/', '', $this->path).'/'.basename($copy_filename),
                    'ACL' => 'public-read'
                ]);

            } catch (S3Exception $e) {
                if ($e->getMessage()) {
                    Func::err_print(ERR_MSG_13);
                    return false;
                }
            }
        }

    }

    //copy
    public function filecopy($old_file, $new_file)
    {
        $old_filename = basename($old_file);
        $new_filename = basename($new_file);
        $fileinfo = Func::get_fileinfo($old_filename);

        $this->path = str_replace('/'.basename($old_file), '', $old_file);

        //s3
        if ($fileinfo['storage'] == 'Y') {
            $orgfile = str_replace(PH_PATH.'/', '', $old_file);
            $repfile = str_replace(PH_PATH.'/', '', $new_file);

            $this->get_s3_action('copy', $old_file, $new_file);

        //local
        } else {

            if ($this->isfile($old_file)) {
                @copy($old_file, $new_file);
            }

        }

        $this->record_datacopy($old_filename, $new_filename);
    }

    //save
    public function upload($file)
    {
        global $CONF;

        $chked = true;

        //s3
        if (isset($CONF['use_s3']) && $CONF['use_s3'] == 'Y') {

            $this->get_s3_action('upload', $file);

        //local
        } else {

            if (!$this->file_upload = move_uploaded_file($this->file['tmp_name'], $this->path.'/'.$file)) {
                $chked = false;
            }

        }

        if ($chked === true) {
            $this->record_dataupload($file);
        }

        return $chked;
    }

    //delete
    public function drop($file)
    {
        global $CONF;

        $fileinfo = Func::get_fileinfo($file);

        //s3
        if ($fileinfo['storage'] == 'Y') {

            $this->get_s3_action('delete', $file);

        //local
        } else {

            if ($this->isfile($this->path.'/'.$file)) {
                unlink($this->path.'/'.$file);
            }

        }

        $this->record_datadrop($file);

    }

    //delete directory
    public function dropdir()
    {
        if ($this->isdir($this->path)) {
            $dir = dir($this->path);
            while (($entry=$dir->read()) !== false) {
                if ($entry != '.' && $entry != '..') {
                    @unlink($this->path.'/'.$entry);
                }
            }
            $dir->close();
            @rmdir($this->path);
        }
    }

    //에디터 사진 삭제
    public function edt_drop($article)
    {
        $this->path = PH_PATH.'/ckeditor/';
        preg_match_all("/ckeditor\/[a-zA-Z0-9-_\.]+.(jpg|gif|png|bmp)/i", $article,$sEditor_images_ex);

        for ($i=0; $i < count($sEditor_images_ex[0]); $i++) {
            $this->name = str_replace('ckeditor4/', '', $this->sEditor_images_ex[0][$i]);
            if ($this->isfile($this->name)) {
                $this->filedrop($this->name);
            }
        }
    }

}
