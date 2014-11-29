<?php
/**
 * This script should be executed by a cronjob.
 *
 * It unrars an configured rar archive only if the creation time of it changed.
 *
 *
 * Author: Sascha Feldmann <sascha.feldmann@gmx.de>
 * Date: 13.07.14
 */

##############################
#
# settings
#
##############################

ini_set('memory_limit', '1024M');

define('RAR_FILE', __DIR__ . '/../../httpdocs/files/Index.rar');
define('TARGET_DIR', __DIR__ . '/../../httpdocs/files');
define('DATA_FILE', __DIR__ . '/data/archive_changed.txt');

##############################
#
# functions
#
##############################

function removeExtractedFiles($dir)
{
    $handle = opendir($dir 	);
 
    if (false === $handle) {
      echo "Could not remove previous files\n";
      return;
    }

    try {
        while (false !== ($file = readdir($handle))) {
	    if ($file == '.' || $file == '..'
		|| $file == '/' || $file == ''
		|| $file == 'Index.rar') {
		continue;
	    }
           if (is_file($dir . '/' . $file)
                && $file != '.htaccess'
                && $file != '.htpasswd' ) {		
               unlink($dir . '/' . $file);
           }

           if (is_dir( $dir . '/' . $file)) {
               removeExtractedFiles($dir . '/' . $file);
			   rmdir($dir . '/' . $file);
           }
        }

	closedir($handle);
    } catch (Exception $e) {
        try { closedir($handle); } catch (Exception $e) {}
        echo "An exception occured while removing extracted files: " . $e . "\n";
    }
}

function unrarFile()
{
    $rar_file = rar_open(RAR_FILE);

    try {
        $list = rar_list($rar_file);

        foreach ($list as $file) {
            $file->extract(TARGET_DIR);
        }
    } catch (Exception $e) {
        try { rar_close($rar_file); } catch (Exception $e) {}
        echo "An exception occured while extracting archive: " . $e . "\n";
    }

}

function saveModificationTime()
{
    file_put_contents(DATA_FILE, filemtime(RAR_FILE));
}

##############################
#
# processing
#
##############################

$created = file_get_contents(DATA_FILE);


echo "Starting cronjob...";

if (false === $created
    || $created != filemtime(RAR_FILE)) {
    // the index.rar is new, so extract it
    echo "Removing previously extracted files...\n";
    removeExtractedFiles(TARGET_DIR);
    echo "Unraring new file...\n";
    unrarFile();

    saveModificationTime();
} else {
   echo "No changes detected. Did not extract the archive.\n";
}

echo "Setting rights...\n";
system('chown gammag:psacln ' . TARGET_DIR . ' -R');

