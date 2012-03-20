<?php
// functions file
function generateToken()
{
	$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$string = '';	
	for ($i = 0; $i < 8; $i++)
		$string .= $characters[mt_rand(0, strlen($characters))];	
	return $string;
}

function addToLog($error, $stamp = 0, $zipname = '')
{
	global $db;
	
	if ($stamp == 0)
		$stamp = mktime();
		
	if ($zipname == '')
		$zipname = 'none';
	
	if (!empty($error))
	{
		$query_text = sprintf("INSERT INTO logs(stamp, zipname, details) VALUES (%d, '%s', '%s')", $stamp, mysql_real_escape_string($zipname), mysql_real_escape_string($error));
		return mysql_query($query_text, $db);
	}
}

function recursive_remove_directory($directory, $empty=false)
{

	if (substr($directory,-1) == '/')
	{
		$directory = substr($directory,0,-1);
	}

	if (!file_exists($directory) || !is_dir($directory))
	{
		return false;
	}
	elseif (!is_readable($directory))
	{
		return false;
	}
	else
	{
		$handle = opendir($directory);
		while (false !== ($item = readdir($handle)))
		{
			if ($item != '.' && $item != '..')
			{
				$path = $directory.'/'.$item;
				if (is_dir($path)) 
				{
					recursive_remove_directory($path);
				}
				else
				{
					unlink($path);
				}
			}
		}
		closedir($handle);
		if ($empty == false)
		{
			if (!rmdir($directory))
			{
				return false;
			}
		}
		return true;
	}
}

function directoryToArray($directory, $recursive = true)
{
	// remove trailing slash
	if (substr($directory, -1) == '/')
		$directory = substr($directory, 0, -1);
	$list = array();
	if ($handle = opendir($directory))
	{
		while (false !== ($file = readdir($handle)))
		{
			if ($file != '.' && $file != '..')
			{
				if (is_dir($directory . '/' . $file))
				{
					if ($recursive)
						$list = array_merge($list, directoryToArray($directory . '/' . $file, $recursive));
					
					$file = $directory . '/' . $file;
					$list[] = preg_replace('/\/\//si', '/', $file);
				}
				else
				{
					if (substr($file, -3) != 'php')
					{
						$file = $directory . '/' . $file;
						$list[] = preg_replace('/\/\//si', '/', $file);
					}
				}
			}
		}
		closedir($handle);
	}
	return $list;
}

// build errors array into an unordered html list
function buildErrors($errors, $error = true)
{
	$color = ($error == true) ? '#f00' : '#0F0';
	return '<ul style="color: ' . $color . ';"><li>' . implode('</li><li>', $errors) . '</li></ul>';
}

// format file size
function formatSize($bytes)
{
    $types = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
    for( $i = 0; $bytes >= 1024 && $i < (count($types) -1); $bytes /= 1024, $i++);
    return (round($bytes, 2) . " " . $types[$i]);
}
?>