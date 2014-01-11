#!/usr/bin/env php
<?php
/**
 * Droplr export script
 *
 * If invoking from the command line, use 
 *   `php droplr-export.php PATH_TO_JSON [PATH_TO_SAVE]`
 * where PATH_TO_JSON is the path to a JSON file from Droplr's web interface, 
 * and PATH_TO_SAVE is an optional parameter for the destination to save all 
 * exported drops.
 *
 * @author	Frederick Ding
 * @version	20140110
 */

/*
 * DEFAULTS
 */
$filename = dirname(__FILE__) . '/droplr.json';
$destination = dirname(__FILE__);

/*
 * CONFIGURATION/CLI LOGIC
 */
if(php_sapi_name() == 'cli') {
 	if($argc < 2 || $argv[1] == '--help') {
		die("Invoke this script with\n"
			. "    php droplr-export.php PATH_TO_JSON [PATH_TO_SAVE]\n"
			. "or change the variables in the script and run from a web server.\n");
	} elseif(!is_file($argv[1])) {
		die("Invalid JSON file specified.\n");
	} else {
		$filename = $argv[1];
	}
	if(isset($argv[2])) {
		if(is_dir($argv[2]) && !is_writable($argv[2])) {
			die("Can't write to the destination path you specified.\n");
		} elseif(!is_dir($argv[2]) && !is_writable(dirname($argv[2]))) {
			die("Couldn't use the destination path you specified.\n");
		} else {
			$destination = realpath($argv[2]);
		}
	}
}

/*
 * LOAD DROPS
 */
$json = json_decode(@file_get_contents($filename));
if(is_null($json)) {
	die("Something went wrong when we tried to read the JSON file.\n");
}
$total = count($json);
$successful = 0;

echo "Read $total items... \n";
echo "Now fetching files... \n\n";

/*
 * THE FETCH LOOP
 */
$curl = curl_init();
foreach($json as $drop) {
	$_destination = $destination;
	$_filename = sprintf('%s_%s', $drop->code, $drop->title);
	$_download_result = false;
	switch($drop->type) {
		case 'note':
			$_destination .= '/notes';

			$_ext = ($drop->variant == 'plain') ? 'txt' : $drop->variant;
			$_filename = sprintf('%s.%s', $drop->code, $_ext);

			try {
				$_download_result = download_file("http://d.pr/{$drop->code}+", 
					$_filename, $_destination, $curl);
			} catch(Exception $e) {
				echo $e->getMessage() . "\n";
			}
			break;
		case 'image':
		case 'audio':
		case 'video':
		case 'file':
			$_destination .= "/{$drop->type}s";

			try {
				$_download_result = download_file("http://d.pr/{$drop->code}+", 
					$_filename, $_destination, $curl);
			} catch(Exception $e) {
				echo $e->getMessage() . "\n";
			}
			break;
		case 'link':
			$_destination .= '/links';
			$_filename = 'debug.tmp';

			try {
				$_download_result = download_file("http://d.pr/{$drop->code}+", 
					$_filename, $_destination, $curl);
			} catch(Exception $e) {
				echo $e->getMessage() . "\n";
			}
			if($_download_result) {
				$_real_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
				file_put_contents(realpath($_destination) . '/links.txt', 
					"{$drop->code} => {$_real_url}\n", FILE_APPEND);
			}
			break;
	}
	if($_download_result) {
		echo "{$drop->code} saved in {$_destination}/{$_filename} \n";
		$successful++;
	} else {
		echo "{$drop->code} encountered a problem \n";
	}
}
curl_close($curl);

echo "\n";
echo "{$successful} of ${total} retrieved.\n";

function download_file($_url, $_filename, $_path, $_handle = null) {
	set_time_limit(0);
	
	if(!realpath($_path) && !is_dir(dirname($_path))) {
		throw new Exception('Destination path and its parent directory do not exist');
	}
	$_filename = basename($_filename);
	if((!is_dir($_path) && !@mkdir($_path)) || !is_writable($_path)) {
		throw new Exception('Destination path not existent or writable');
	}
	$_path = realpath($_path);

	$file = @fopen($_path . '/' . $_filename, 'w');

	$options = array(
		CURLOPT_FILE	=>	$file,
		CURLOPT_TIMEOUT	=>	3600,
		CURLOPT_URL		=>	$_url,
		CURLOPT_FOLLOWLOCATION	=>	true
		);

	if(is_null($_handle)) {
		$curl = curl_init();
	} else
		$curl = $_handle;

	curl_setopt_array($curl, $options);
	$result = curl_exec($curl);

	if(!$result)
		throw new Exception('Curl error: ' . curl_error($curl));

	if(is_null($_handle))
		curl_close($curl);

	fclose($file);
	return $result;
}