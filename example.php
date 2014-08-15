<?php

/**
 * Created by PhpStorm.
 * User: Emil Maran ( maran.emil[at]gmail[dot].com)
 * Date: 14.08.14
 * Time: 19:04
 */

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

include("libs/ftp_wrapper.php");
include("libs/zip_wrapper.php");

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
$jobName       = "Abc5Se";
$ftp_server 	= "ftp.servername";
$ftp_user    	= "username";
$ftp_pass     	= "password";

$dir_remote 	= "/dir/where/remote/folder/is"; // ftp remote folder
$dir_local 		= "/your/absolute/path/to/script/public_html/is";  // local folder
$dir_reset      = true;

$dir_exclude = array(
    "custom_files",
    "abc",
    "tmp",
    "updates",
    "files",
    "js"
);

if($dir_reset){
    // Remove Old content from Project if exists:: unlink/rmdir
    $ftpIns = new FTP();
    $ftpIns->rrmdir($dir_local.$dir_remote."/");
}
////////////////////////////////////////////////////////////////
//
// Prepare local folder for Download
//
////////////////////////////////////////////////////////////////

// Change directory to Project Path on your drive
chdir($dir_local);

// Start creating root folders
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
            }
            $iterator++;
        }
    }
}

////////////////////////////////////////////////////////////////
//
// Start to download from FTP
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

// get contents of the current ftp directory
$arrNList = ftp_nlist($ftp_conn, $dir_remote);

// output $contents
#var_dump($arrNList);
// Create array list with root files and folders
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

// Get root files from FTP
if(is_array($arFilesRootDir)){
    foreach($arFilesRootDir as $rootFile){
        @ftp_get($ftp_conn, $dir_local . $dir_remote . "/" . $rootFile, $rootFile, FTP_BINARY);
    }
}

// Get dirs & files from FTP
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
            $ftpIns->download($dir_local, $dir_remote."/".$elList, $ftp_conn);
        }
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

// Make a zip of local copy folder
$zipIns = new HZip();
$zipIns->zipDir($dir_local.$dir_remote, $dir_local.'/'.$jobName.'_'.date("YmdHis").'.zip'); // in - out
echo '<br/>'.$jobName.'.zip Ready!';

// Remove local copy of files
#$ftpIns = new FTP();
$ftpIns->rrmdir($dir_local.$dir_remote."/");

?>
