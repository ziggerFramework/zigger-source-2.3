<?php
namespace Module\Board;

use Corelib\Method;
use Corelib\Func;
use Make\Database\Pdosql;
use Make\Library\Uploader;

class Down extends \Controller\Make_Controller {

    public function init()
    {
        global $board_id;

        $sql = new Pdosql();

        $req = Method::request('get', 'board_id, file');

        $board_id = $req['board_id'];

        if (!$board_id) {
            Func::err('board_id 가 누락되었습니다.');
        }

        //게시글의 첨부파일 정보 불러옴
        $sql->query(
            "
            SELECT *
            FROM {$sql->table("mod:board_data_".$board_id)}
            WHERE file1=:col1 OR file2=:col1
            ",
            array(
                $req['file']
            )
        );

        //첨부파일이 확인되지 않는 경우
        if ($sql->getcount() < 1) {
            Func::err('첨부파일이 확인되지 않습니다.');
        }

        //파일 정보
        $fileinfo = Func::get_fileinfo($req['file']);

        //Object Storage에 저장된 파일인 경우
        if ($fileinfo['storage'] == 'Y') {

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $fileinfo['replink']);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec ($ch);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($http_code == 200) {

                header('Content-Disposition: attachment; filename='.$fileinfo['orgfile']);
                header('Content-type: application/octet-stream');
                header('Content-Transfer-Encoding: binary');

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_URL, $fileinfo['replink']);

                $file = curl_exec($ch);
                curl_close($ch);

            }

        }

        //Local에 저장된 파일인 경우
        else if ($fileinfo['storage'] == 'N') {

            $fileinfo = array();
            $fileinfo['path'] = MOD_BOARD_DATA_PATH.'/'.$board_id.'/'.$req['file'];
            $fileinfo['size'] = filesize($fileinfo['path']);
            $fileinfo['parts'] = pathinfo($fileinfo['path']);
            $fileinfo['name'] = $fileinfo['parts']['basename'];

            //파일 다운로드 스트림
            $file_datainfo = Func::get_fileinfo($fileinfo['name']);

            Header('Content-Type:application/octet-stream');
            Header('Content-Disposition:attachment; filename='.$file_datainfo['orgfile']);
            Header('Content-Transfer-Encoding:binary');
            Header('Content-Length:'.(string)$fileinfo['size']);
            Header('Cache-Control:Cache,must-revalidate');
            Header('Pragma:No-Cache');
            Header('Expires:0');
            ob_clean();
            flush();

            readfile($fileinfo['path']);

        }

        //파일 다운로드 횟수 증가
        $qry_file = array();
        $qry_file_cnt = array();

        for ($i = 1; $i <= 2; $i++){
            $downfile = urldecode($req['file']);
            $isfile = $sql->fetch('file'.$i);

            if ($isfile == $downfile) {
                $qry_file_cnt[$i] = 1;

            } else {
                $qry_file_cnt[$i] = 0;
            }
        }

        $sql->query(
            "
            UPDATE {$sql->table("mod:board_data_".$board_id)}
            SET file1_cnt=file1_cnt+:col1,file2_cnt=file2_cnt+:col2
            WHERE file1=:col3 OR file2=:col3
            ",
            array(
                $qry_file_cnt[1], $qry_file_cnt[2], $downfile
            )
        );


    }

}
