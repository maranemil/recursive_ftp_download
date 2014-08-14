<?php

ini_set('display_errors','On');
error_reporting(E_ALL);
set_time_limit(0);

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
            if (ftp_chdir($ftp_conn, $remote_dir) == false) {
                echo("Change Dir Failed: $remote_dir<br />");
                return;
            } else {
               
            }

            // create local dir and switch to dir path
            if (!is_dir($local_dir . $remote_dir)) {
                
                // Prepare folder
                $localSubDirPath    = explode("/", $remote_dir);
                $localSubDirPathStr = $localSubDirPath[count($localSubDirPath) - 1];

                if (!is_dir($localSubDirPathStr)) {
                    echo $local_dir . $remote_dir . "/  | " . $localSubDirPathStr . "<br />";
                    mkdir($localSubDirPathStr);
                    chmod($localSubDirPathStr, 0777);
                    chdir($localSubDirPathStr);
                }
            }
        }

        $contents = ftp_nlist($ftp_conn, ".");
        foreach ($contents as $file) {

            if ($file == '.' || $file == '..')
                continue;

            if (@ftp_chdir($ftp_conn, $file)) {
                ftp_chdir($ftp_conn, "..");
                FTP::download($local_dir . $remote_dir . "/", $file, $ftp_conn);
            } else {
               
                if (!is_file($local_dir . $remote_dir . "/" . $file)) {
                    // download server file // In der folgenden Zeile kommt die Fehlermeldung:
                    // FTP_ASCII oder FTP_BINARY
                    if (!ftp_get($ftp_conn, $local_dir . $remote_dir . "/" . $file, $file, FTP_BINARY)) {
                        
                    } else {
                        
                    }
                } else {

                }
            }
            #chmod ($local_dir/$dir/$file,0777);
        }

        ftp_chdir($ftp_conn, "..");
        chdir("..");
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

# For the actual ftp actions, I use a FTP class I wrote which you can grab below
FTP::download($dir_local, $dir_remote, $ftp_conn);
ftp_close($ftp_conn);
echo "<br/>Transfer Done!";

$callEndTime = microtime(true);
$callTime = $callEndTime - $callStartTime;
echo '<br/>Call time to include file was ' , sprintf('%.4f',$callTime) , " seconds" ;
echo '<br/>Call time to include file was ' , round(sprintf('%.4f',$callTime)/60) , " min" ;


?>
