<?php
require_once(dirname(__FILE__) . '/db.php');
require_once(dirname(__FILE__) . '/functions.php');
 
$expl = explode("/",$HTTP_SERVER_VARS["REQUEST_URI"]);
$token = $expl[count($expl) - 1];

// remove day old zip files

$current = mktime() - 86400;
$query_text = "SELECT id, token, name FROM pkgs WHERE stamp < {$current} ORDER BY id ASC";
$query = mysql_query($query_text, $db);

if (mysql_num_rows($query) > 0)
{
	while ($result = mysql_fetch_array($query, MYSQL_ASSOC))
	{
		@unlink(dirname(__FILE__) . '/zips/' . $result['name']);	// delete the zip file
		@unlink(dirname(__FILE__) . '/qrimg/' . $token . '.png');	// delete the qr image
		$dummy = mysql_query(sprintf('DELETE FROM pkgs WHERE id = %d', (int)$result['id']));
	}
}

// clear everything
if ($token == 'resetdb')
{
	// first clear the sql tables
	$query_text = "TRUNCATE TABLE `%s`";
	$dummy = mysql_query(sprintf($query_text, 'logs'));
	$dummy = mysql_query(sprintf($query_text, 'pkgs'));
	die('Database was reset!');
}

if (isset($token) && !empty($token) && trim($token) != '')
{
	$query_text = sprintf("SELECT url FROM pkgs WHERE token='%s' LIMIT 1", $token);
	$query = mysql_query($query_text, $db);
	if (mysql_num_rows($query) > 0)
	{
		$result = mysql_fetch_array($query);
		$dummy = mysql_query("UPDATE pkgs SET count=count+1 where token='$token' LIMIT 1");
		header(sprintf('Location: %s', $result['url']));
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Samsung Galaxy S 4G - Create CWM Boot Animation Package</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" href="css/base.css" type="text/css" />
		<link href="favicon.ico" rel="icon" type="image/x-icon" />
		
		<!-- jQuery Goodness! -->
		<script type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="js/jquery.form.js"></script>
		
		<script type="text/javascript">

			$(document).ready(function(){
				var options = {
					beforeSubmit:	showRequest,
					success:		showResponse,
					url:			'upload.php',
					dataType:		'json',
					resetForm:		true,
				};
				
				$('#uploadform').ajaxForm(options);
			});
			
			function showRequest(formData, jqForm, options) {
				var zipName = $('input[name=zipname]').fieldValue();
				var animation = $('input[name=animation]').fieldValue();
	
				if (!zipName[0]) {
					$('#resultimg').html('<img src="images/x.png" alt="Error" />');
					$('#message').html('<span style="color:#f00;font-weight:bold;">Please provide a requested package name.</span>');
					return false;
				} else if (!animation[0]) {
					$('#resultimg').html('<img src="images/x.png" alt="Error" />');
					$('#message').html('<span style="color:#f00;font-weight:bold;">Please provide <tt>bootanimation.zip</tt>.</span>');
					return false;
				} else if (String(animation).search('bootanimation.zip') == -1) {
					$('#resultimg').html('<img src="images/x.png" alt="Error" />');
					$('#message').html('<span style="color:#f00;font-weight:bold;">Boot animation zip must be named <tt>bootanimation.zip</tt>.</span>');
					return false;
				}
				$('#loading').show();
				return true;
			}
			
			function showResponse(data, statusText) {
				$('#loading').hide();
				if (statusText == 'success') {
					if (data.url != '') {
						$('#result').html('<span style="color:#0f0;"><strong>Package Download:</strong> <a href="' + data.url + '">' + data.url + '</a></span>');
					}
					$('#resultimg').html('<img src="qrimg/' + data.img + '" alt="" />');
					$('#message').html(data.error);
				} else {
					$('#resultimg').html('<img src="images/x.png" alt="Error" />');
					$('#message').html('<span style="color:#f00;font-weight:bold;">Unknown error occured during file upload, please try again.</span>');
				}
			}
		</script>
		<script type="text/javascript">

		  var _gaq = _gaq || [];
		  _gaq.push(['_setAccount', 'UA-28927666-1']);
		  _gaq.push(['_trackPageview']);

		  (function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		  })();

		</script>
	</head>
	<body>
		<div align="center"><img src="images/android.png" alt="Android!" height="125" width="398" /></div>
		<h1>Create CWM Boot Animation Package</h1>
		<p class="headtext">
			Use this little tool to be able to create a CWM package to be able to flash a new boot animation on your <em>Samsung Galaxy S 4G</em>.
		</p>
		
		<div id="warning" align="center">
			<span id="warning">Warning:</span>
			This tool is designed specifically for the <em>Samsung Galaxy S 4G SGH-T959V</em>, and uses it's mount points for mounting the system partition.
		</div>
		<div id="note" align="center">
			<span id="note">Note:</span>
			The author, <a href="mailto:crackpot@sabr.es">Adam "crackpot" Koch</a>, is not responsible for any damage to your phone caused by using this tool.  <em>Use at your own risk!</em>
		</div>
		
		<br /><br />
		
		<!-- Upload Form -->
		<div align="center">
			<form id="uploadform" name="uploadform" enctype="multipart/form-data" method="post">
				<input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
				<table> 
					<tr>
						<td class="plain" align="left" style="border: 1px dotted #888;">
							<p id="result"></p>
							<table border="0" cellspacing="2" cellpadding="0">
								<tr>
									<td id="resultimg" class="plain" width="99" align="center" valign="middle"></td>
									<td id="message" class="plain" valign="top"></td>
								</tr>
							</table>
							<dl>
								<dt><label for="zipname">Zip File Name:</label></dt>
									<dd>
										<input type="text" id="zipname" name="zipname" maxlength="20"/>
									</dd>
								<dt><label for="animation">Boot Animation Zip:</label></dt>
									<dd>
										<input type="file" id="animation" name="animation" style="display: block;" />
										<img id="loading" src="images/loading.gif" style="display: none;" />
									</dd>
								<dt>Notes:</dt>
									<dd>
										<ul>
											<li>Maximum number of characters for the zip filename is 20.</li>
											<li>Uploaded file <span class="underline">must</span> be named <tt>bootanimation.zip</tt>.</li>
											<li>Maximum file size for <tt>bootanimation.zip</tt> is 10mb.</li>
											<li>Your chosen filename will have a Unix-style timestamp attached to prevent duplicate names.</li>
											<li>Your download URI will remain valid for one (1) day.</li>
										</ul>
										<div align="center">
											<input type="submit" id="createpkg" name="createpkg" value="Create Package" />&nbsp;<input type="reset" name="reset" value="Reset Fields" />
										</div>
									</dd>
							</dl>
						</td>
					</tr>
				</table>
			</form>
		</div>
		<!-- END Upload Form -->
		
		<br />
		<h2 id="recent">Recent Packages</h2>

		<table class="kver" align="left">
			<?php
				if ($db)
				{
					$table_row = '<tr align="left"><td>%s</td><td>%s</td><td>[<a href="%s">%s</a>]</td><td>[%s]</td><td>[Hits: %d]</td></tr>';
					$query_text = "SELECT token, name, url, filesize, stamp, count FROM pkgs ORDER by stamp DESC LIMIT 10";
					$query = mysql_query($query_text, $db);
					
					if (mysql_num_rows($query) == 0)
					{
						print '<tr align="left"><td><strong>No recent packages to display.</td></tr>';
					}
					else
					{
						while ($result = mysql_fetch_array($query))
						{
							print sprintf(
								$table_row,
								date('Y-m-d H:m:s', $result['stamp']),
								$result['name'],
								'http://bootanim.crackpot.co/' . $result['token'],
								'http://bootanim.crackpot.co/' . $result['token'],
								formatSize($result['filesize']),
								$result['count']
							);
						}
					}
				}
			?>
		</table>
		<div style="clear: both;"></div>
		<br />
		<h2 id="changes">Changelog</h2>
		<dl id="changelog"> 
			<dt>2012-03-18 2144 Version 2.1.2</dt>
				<dd>
					<ul>
						<li>Script home moved to <a href="http://bootanim.crackpot.co">http://bootanim.crackpot.co</a>, script updated accordingly.</li>
					</ul>
				</dd>
			<dt>2012-03-18 1029 Version 2.1.1</dt>
				<dd>
					<ul>
						<li>Fixed bug with automatic pruning of old boot animations.</li>
					</ul>
				</dd>
			<dt>2012-03-17 0103 Version 2.1</dt>
				<dd>
					<ul>
						<li>Added QR image generator to allow you to scan the image with the <a href="https://play.google.com/store/apps/details?id=com.google.zxing.client.android&hl=en">Barcode Scanner</a> app from Google Play (the market) and download directly to your phone.</li>
						<li>Script should now correctly delete packages after becoming one day old.</li>
					</ul>
				</dd>
			<dt>2012-02-06 0112 Version 2.0</dt>
				<dd>
					<ul>
						<li>With help from <a href="http://forum.xda-developers.com/member.php?u=3833556">FBis251</a> was able to diagnose and fix the issues with 1.1.</li>
						<li>Update script will now delete the previous <tt>bootanimation.zip</tt> before copying the new one over.</li>
					</ul>
				</dd>
			<dt>2012-02-04 0018 Version 1.1</dt>
				<dd>
					<ul>
						<li>Corrected issue with max file upload size in PHP's ini file.</li>
						<li>Added an error log so I can track what issues people are having.</li>
						<li>Added MySQL database tracking what packages have been created.</li>
						<li>Added "pretty urls" to handle downloads so I can track download hits.</li>
						<li>Added <a href="http://google.com/analytics">Google Analytics</a> tracking for shits and giggles.</li>
					</ul>
				</dd>
			<dt>2012-02-03 0457 Version 1.0</dt>
				<dd>
					<ul><li>Initial release.</li></ul>
				</dd>
		</dl>
		
		<br />
		<h2 id="faq">Frequently Asked Questions</h2>
		<dl>
			<dt>What is this for?</dt>
				<dd>
					<p>Basically, this little tool will take the <tt>bootanimation.zip</tt> that you upload, combine it with the necessary files required by <acronym title="ClockworkMod Recovery">CWM</acronym> for a successful flashing and then provide you a link.</p>
				</dd>
			<dt>How does it work?</dt>
				<dd>
					<p>This software uses <acronym title="PHP: Hypertext Preprocessor">PHP</acronym>, version <?php echo PHP_VERSION; ?>, and it's <a href="http://php.net/manual/en/book.zip.php">zip</a> functionality, along with a "skeleton" directory of the layout of a boot animation CWM package.  When your file is uploaded it simply adds your boot animation to the skeleton directory, zips it up, then automatically starts your download.</p>
				</dd>
			<dt>I have a question and/or comment.</dt>
				<dd>
					<p>Feel free to <a href="mailto:crackpot@sabr.es">e-mail me</a> or contact me on <a href="http://forum.xda-developers.com/member.php?u=2111162">XDA</a> or <a href="http://rootzwiki.com/user/8512-crackpot/">RootzWiki</a>.</p>
					<p>Support thread is available on <a href="http://forum.xda-developers.com/showthread.php?t=1478433">XDA Developers</a>.</p>
				</dd>
		</dl>
		<br />
		<h2 id="credits">Credits/Thanks</h2>
		<dl>
			<dt><a href="http://samsung.com">Samsung</a></dt>
				<dd>For creating an incredible phone!</dd>
			<dt><a href="http://android.com">Google</a></dt>
				<dd>For creating Android, obviously.  <em>SCREW YOU IPHONE/APPLE!</em></dt>
			<dt><a href="http://clockworkmod.com">Authors of ClockworkMod Recovery</a></dt>
				<dd>For giving Android users an amazingly robust recovery platform.</dd>
			<dt><a href="http://forum.xda-developers.com/forumdisplay.php?f=1065">SGS4G XDA Community</a></dt>
				<dd>For creating an amazing community of developers and users to really bring out the power of these Android devices!</dd>
			<dt><a href="http://forum.xda-developers.com/member.php?u=1949378">bhundven</a></dt>
				<dd>For giving SGS4G users a custom built Gingerbread kernel with support for boot animations!</dd>
			<dt><a href="http://forum.xda-developers.com/member.php?u=3833556">FBis251</a></dt>
				<dd>For helping me debug and getting this script on the right track!.</dd>
			<dt><a href="http://sourceforge.net/users/deltalab">Dominik Dzienia</a> aka deltalab</dt>
				<dd>For creating the <a href="http://phpqrcode.sourceforge.net/">PHP QR Code</a> library.</dd>
		</dl>
		
		<hr />
		<table width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td width="33%" align="left" style="border-bottom: 0px !important;">
			
					<a href="http://validator.w3.org/check?uri=referer"><img src="images/powered/xhtml.png" alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
					<a href="http://jigsaw.w3.org/css-validator/check/referer"><img src="images/powered/css.png" alt="Valid CSS" height="31" width="88" /></a>
				</td>
				<td width="33%" align="center" style="border-bottom: 0px !important;">
					<span style="font-size: 75%;">This page and supporting code was designed and written by <a href="mailto:crackpot@sabr.es">Adam "crackpot" Koch</a>.<br />Page design is modeled after <a href="http://kernel.org">The Linux Kernel Archive</a>.</span>
				</td>
				<td width="33%" align="right" style="border-bottom: 0px !important;">
					<a href="http://centos.org"><img src="images/powered/centos.png" alt="Powered by CentOS" height="31" width="88" /></a>
					<a href="http://apache.org"><img src="images/powered/apache.png" alt="Powered by Apache" height="31" width="88" /></a>
					<a href="http://php.net"><img src="images/powered/php.png" alt="Powered by PHP <?php echo PHP_VERSION; ?>" height="31" width="88" /></a>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php mysql_close($db); ?>
