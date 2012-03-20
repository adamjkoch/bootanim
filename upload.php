<?php
require_once(dirname(__FILE__) . '/db.php');
require_once(dirname(__FILE__) . '/functions.php');
require_once(dirname(__FILE__) . '/libs/phpqrcode.php');

// timestamp for uniqueness' sake
$stamp = mktime();

// directories to use
$basedir = dirname(__FILE__) . '/base/';
$tempdir = dirname(__FILE__) . '/temp/';
$zipdir = dirname(__FILE__) . '/zips/';

$errors = array();
$json = array();

// some error checking
if (!$_FILES['animation'] || $_FILES['animation']['name'] != 'bootanimation.zip')
{
	$errors[] = 'Boot animation was not provided.';
	addToLog('Boot animation name is invalid.', $stamp, $_POST['zipname']);
}	
if (!$_POST['zipname'])
{
	$errors[] = 'Invalid zip name provided.';
	addToLog('Invalid zip name provided.', $stamp, $_POST['zipname']);
}
if (filesize($_FILES['animation']['tmp_name']) > $_POST['MAX_FILE_SIZE'])
{
	$errors[] = 'Boot animation zip file was too large.';
	addToLog(sprintf('bootanimation.zip too large. Size: %s', formatsize(filesize($_FILES['animation']['tmp_name']))), $stamp);
}
if (sizeof($errors) > 0)
{
	$json['img'] = 'x.png';
	$json['error'] = buildErrors($errors);
	echo json_encode($json);
	recursive_remove_directory($workdir);
	exit;
}

// make the temporary working directory
$workdir = $tempdir . $stamp . '/';
$pkg = sprintf('%s-%s.zip', $_POST['zipname'], $stamp);
$zipurl = 'http://zips.bootanim.crackpot.co/';
if (!@mkdir($workdir, 0777))
{
	$errors[] = 'Failed to create the working directory.  Try again.';
	addToLog('Failed to create working directory.', $stamp, $_POST['zipname']);
}
elseif (!@move_uploaded_file($_FILES['animation']['tmp_name'], $workdir . $_FILES['animation']['name']))
{
	$errors[] = 'Failed to move uploaded <tt>bootanimation.zip</tt> file.';
	addToLog('Failed to move uploaded bootanimation.zip.', $stamp, $_POST['zipname']);
}
elseif (!@chmod($workdir . $_FILES['animation']['name'], 0777))
{
	$errors[] = 'Failed to CHMOD uploaded file.';
	addToLog('Failed to chmod uploaded file.', $stamp, $_POST['zipname']);
}
elseif (!@copy($basedir . 'base.zip', $workdir . $pkg))
{
	$errors[] = 'Failed to move base zip file.';
	addToLog('Failed to move base zip file.', $stamp, $_POST['zipname']);
}

// make sure there's no errors before we continue
if (sizeof($errors) > 0)
{
	$json['img'] = 'x.png';
	$json['error'] = buildErrors($errors);
	echo json_encode($json);
	recursive_remove_directory($workdir);
	exit;
}

// create a new ziparchive object instance
$zip = new ZipArchive();

// open the newly created base zip file
if ($zip->open($workdir . $pkg) !== true)
{
	$errors[] = 'Failed to read the newly created base zip file.';
	addToLog('Failed to read created zip file.', $stamp, $_POST['zipname']);
	$json['img'] = 'x.png';
	$json['error'] = buildErrors($errors);
	echo json_encode($json);
	recursive_remove_directory($workdir);
	exit;
}

// now add bootanimation.zip to the folder /system/media in the base zip file
if (!$zip->addFile($workdir . 'bootanimation.zip', 'system/media/bootanimation.zip'))
{
	$errors[] = 'Failed to add <tt>bootanimation.zip</tt> to the base zip file.';
	addToLog('Failed to add boot anmation to zip file.', $stamp, $_POST['zipname']);
}
elseif (!$zip->close())	// close() actually performs the changes
{
	$errors[] = 'Failed to commit the zip file changes to the base zip file.';
	addToLog('Zip closure failed.', $stamp, $_POST['zipname']);
}
elseif (!@copy($workdir . $pkg, $zipdir . $pkg))
{
	$errors[] = 'Failed to move finished zip file.';
	addToLog('Failed to move finished zip file.', $stamp, $_POST['zipname']);
}
	  
// make sure there's no errors before we continue
if (sizeof($errors) > 0)
{
	$json['img'] = 'x.png';
	$json['error'] = buildErrors($errors);
	echo json_encode($json);
	recursive_remove_directory($workdir);
	exit;
}
else 
{
	// add result to mysql
	$token = generateToken();
	$query_text = sprintf("INSERT INTO pkgs(token, name, url, filesize, stamp, count) VALUES ('%s', '%s', '%s', '%d', '%d', 0);", $token, $pkg, ($zipurl . $pkg), filesize($zipdir . $pkg), $stamp);
	$query = mysql_query($query_text, $db);
	
	QRcode::png('http://bootanim.crackpot.co/' . $token, dirname(__FILE__) . '/qrimg/' . $token . '.png', 'L', 6, 2);
	$json['img'] = $token . '.png';
	$json['url'] = 'http://bootanim.crackpot.co/' . $token;
	$errors[] = 'CWM package creation successful!';
	$errors[] = 'Package name is <tt>' . $pkg . '</tt>.';
	$errors[] = 'Package size is <tt>' . formatSize(filesize($zipdir . $pkg)) . '</tt>.';
	
	if (!$query)
		$errors[] = '<span color="red">Failed to add result to database.</span>';
	
	$json['error'] = buildErrors($errors, false);
	echo json_encode($json);
	
	// clean up after ourselves
	recursive_remove_directory($workdir);
	exit;
}
mysql_close($db); 
?>
