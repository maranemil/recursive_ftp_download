<?php

/**
 * Created by PhpStorm.
 * User: Emil Maran ( maran.emil[at]gmail[dot].com)
 * Date: 14.08.14
 * Time: 19:04
 */

class FTP
{

    /**
     * Download() performs an automatic syncing of files and folders from a remote location
     * preserving folder and file names and structure
     * https://gist.github.com/cukabeka/4651190 -  cukabeka / recursive_ftp_download.php
     *
     * @param $local_dir : The directory to put the files, must be in app path and be writeable
     * @param $remote_dir : The directory to start traversing from. Use "." for root dir
     *
     * @param $ftp_conn
     * @return null
     */

    public static function download($local_dir, $remote_dir, $ftp_conn)
    {
        if ($remote_dir != ".") {
            if (@ftp_chdir($ftp_conn, $remote_dir) == false) {
                #echo("Change Dir Failed: $remote_dir<br />");
                #return;
                // NOOP - No Operation
                // This command does not affect anything at all.
                // It performs no action other than having the server send an OK reply.
                // This command is used to keep connections with servers "alive" (connected) while nothing is being done.
                #ftp_exec($ftp_conn, 'NOOP');
                ftp_raw($ftp_conn, 'NOOP');
            } else {
                #ftp_chdir ($ftp_conn, $remote_dir);
            }

            // create local dir and switch to dir path
            if (!is_dir($local_dir . $remote_dir)) {
                #echo $local_dir . $remote_dir . "/  -|-  " . $remote_dir . "<br />";

                // Prepare folder
                $localSubDirPath = explode("/", $remote_dir);
                $localSubDirPathStr = $localSubDirPath[count($localSubDirPath) - 1];

                if (!is_dir($localSubDirPathStr)) {
                    #echo $local_dir . $remote_dir . "/  | " . $localSubDirPathStr . "<br />";
                    @mkdir($localSubDirPathStr);
                    @chmod($localSubDirPathStr, 0777);
                    @chdir($localSubDirPathStr);
                }
            }
        }

        $contents = ftp_nlist($ftp_conn, ".");
        if(is_array($contents) && count($contents)<40 ){
            foreach ($contents as $file) {

                if ($file == '.' || $file == '..')
                    continue;

                if (@ftp_chdir($ftp_conn, $file)) {
                    ftp_chdir($ftp_conn, "..");
                    FTP::download($local_dir . $remote_dir . "/", $file, $ftp_conn);
                } else {
                    #echo "Here is a file:". $local_dir.$remote_dir."/".$file. "<br >";
                    #if (!is_file($local_dir . $remote_dir . "/" . $file) && stristr($file,".php") ) {
                    if (!is_file($local_dir . $remote_dir . "/" . $file) ) {
                        // download server file // In der folgenden Zeile kommt die Fehlermeldung:
                        // FTP_ASCII oder FTP_BINARY
                        if (!@ftp_get($ftp_conn, $local_dir . $remote_dir . "/" . $file, $file, FTP_BINARY)) {
                            echo "error! Cannot copy:". $local_dir.$remote_dir."/".$file. "<br >";
                            continue;
                            #return;
                        } else {
                            #continue;
                            #echo "File to  copy:". $local_dir.$remote_dir."/".$file. "<br >";
                        }
                    } else {

                    }
                }
                #chmod ($local_dir/$dir/$file,0777);
            }
        }

        ftp_raw($ftp_conn, 'NOOP');
        @ftp_chdir($ftp_conn, "..");
        @chdir("..");
    }

    /**
     * @param $dir
     *
     *  holger1 at NOSPAM zentralplan dot de
     *  4 years ago
     *  Another simple way to recursively delete a directory that is not empty:
     */

    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }


}

