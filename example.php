<?php

ini_set('display_errors','On');
error_reporting(E_ALL);
set_time_limit(0);
ini_set('max_execution_time', 3000); //300 seconds = 5 minutes
#echo ini_get('max_execution_time'); die();

/*
max_execution_time = 360      ; Maximum execution time of each script, in seconds (I CHANGED THIS VALUE)
max_input_time = 120          ; Maximum amount of time each script may spend parsing request data
max_input_nesting_level = 64 ; Maximum input variable nesting level
memory_limit = 128M           ; Maximum amount of memory a script may consume (128MB by default)
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

}

/**
 * @param $dir
 *
 *  holger1 at NOSPAMzentralplan dot de ¶
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

?>
<?php

////////////////////////////////////////////////////////////////
//
// Settings & Init
//
////////////////////////////////////////////////////////////////

echo date('H:i:s') , " Load from  file" ;
$callStartTime = microtime(true);

#print "<pre>"; print_r($_SERVER); print "</pre>";
# Linear connection info for connecting to FTP
 
# set up basic connection
$ftp_server 	= "ftp.servername";
$ftp_user    	= "username";
$ftp_pass     	= "password";

$dir_remote 	= "/dir/where/remote/folder/is"; // ftp remote folder
$dir_local 		= "/your/absolute/path/to/script/public_html/is";  // local folder

$dir_exclude = array(
    "custom_files",
    "abc",
    "tmp",
    "updates",
    "files",
    "js"
);

// unlink - rmdir
rrmdir($dir_local.$dir_remote."/");

////////////////////////////////////////////////////////////////
//
// Prepare local folder for Download
//
////////////////////////////////////////////////////////////////

$local_dir_path = explode("/",$dir_remote);

if(is_array($local_dir_path)){
    $ckdir = $local_dir_path[1];
    $iterator = 0;
    $maxDir = count($local_dir_path)-1;
    if(!is_dir($ckdir) || !is_dir($dir_local.$dir_remote)){
        foreach($local_dir_path as $subFtpDir){

            if(!is_dir($subFtpDir) ){
                if($subFtpDir!="" && $iterator <= $maxDir ){
                    #echo $subFtpDir."<br>";
                    mkdir($subFtpDir);
                    chmod($subFtpDir,0777);
                    chdir($subFtpDir);
                }
            }
            else{
                chdir($subFtpDir);
                #echo "<br>".$subFtpDir."::: Exists!";
            }
            $iterator++;
        }
        #chdir($subFtpDir);
        #chdir("..");
    }
}

////////////////////////////////////////////////////////////////
//
// Start download
//
////////////////////////////////////////////////////////////////

$ftp_conn   	= ftp_connect($ftp_server); // connect
$ftp_login   	= ftp_login($ftp_conn, $ftp_user, $ftp_pass); // login
ftp_pasv($ftp_conn, true);

# check connection
if ((!$ftp_conn) || (!$ftp_login)) {
	echo "<br />FTP connection has failed!";
	echo "<br />Attempted to connect to $ftp_server for user $ftp_user";
} else {
	echo "<br />Connected to $ftp_server, for user $ftp_user";
}

// change to the parent directory
#ftp_cdup($ftp_conn);

// output current directory name
echo "<br />Currently in ".ftp_pwd($ftp_conn);
if (ftp_chdir($ftp_conn, $dir_remote)) {
	echo "<br />Current directory is now: " . ftp_pwd($ftp_conn) . "\n";
} else {
    echo "<br />Couldn't change directory\n";
}
 
# Dump the data to the screen
var_dump(ftp_rawlist($ftp_conn, $dir_remote));
#die();

// get contents of the current directory
$arrNList = ftp_nlist($ftp_conn, $dir_remote);

// output $contents
#var_dump($arrNList);
foreach($arrNList as $elList){
    if ($elList == '.' || $elList == '..' || in_array($elList, $dir_exclude))
        continue;

    if(stristr($elList,'.')){
        $arFilesRootDir[] = $elList;
    }
    else{
        $arDirsRootDir[] = $elList;
    }
}

// Get root files
if(is_array($arFilesRootDir)){
    foreach($arFilesRootDir as $rootFile){
        @ftp_get($ftp_conn, $dir_local . $dir_remote . "/" . $rootFile, $rootFile, FTP_BINARY);
    }
}

// Get dirs & files
if(is_array($arDirsRootDir)){
    foreach($arDirsRootDir as $elList){

        #ftp_chdir($ftp_conn, $dir_remote."/".$elList);
        if(is_dir($dir_local.$dir_remote."/".$elList)){
            echo "<br >".$dir_local.$dir_remote."/".$elList." already exists";
        }
        else{
            @mkdir($elList);
            @chmod($elList,0777);
            @chdir($elList);
            echo "<br >".$dir_local.$dir_remote."/".$elList." created now";
            FTP::download($dir_local, $dir_remote."/".$elList, $ftp_conn);
        }
        #
        #die();
    }
}

# For the actual ftp actions, I use a FTP class I wrote which you can grab below
#FTP::download($dir_local, $dir_remote, $ftp_conn);
ftp_close($ftp_conn);
echo "<br/>Transfer Done!";

$callEndTime = microtime(true);
$callTime = $callEndTime - $callStartTime;
echo '<br/>Call time to include file was ' , sprintf('%.4f',$callTime) , " seconds" ;
echo '<br/>Call time to include file was ' , round(sprintf('%.4f',$callTime)/60) , " min" ;


?>
