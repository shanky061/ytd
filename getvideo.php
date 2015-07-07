<?php

include_once('config.php');
ob_start();

function clean($string)
{
	$string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
	return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
}

function Size($size,$unit="")
{
	if((!$unit && $size >= 1<<30) || $unit == "GB")
		return number_format($size/(1<<30),2)."GB";
	if((!$unit && $size >= 1<<20) || $unit == "MB")
		return number_format($size/(1<<20),2)."MB";
	if((!$unit && $size >= 1<<10) || $unit == "KB")
		return number_format($size/(1<<10),2)."KB";
	return number_format($size)." bytes";
}

$v_id = $_REQUEST['videoid'];
if (isset($v_id) && !empty($v_id))
{
	if (strlen($v_id)>11)
	{
		$query = parse_url($v_id, PHP_URL_QUERY);
		if ($query)
		{
			parse_str($query);
			if (isset($v))
				$v_id = $v;
			else
			{
				echo '<p>No video id passed in</p>';
				exit;
			}
		}
		else
		{
			echo '<p>Invalid URL</p>';
			exit;
		}
	}
}
else
{
        echo '<p>No video id passed in</p>';
        exit;
}

if (isset($_REQUEST['type']))
	$v_type =  $_REQUEST['type'];
else
	$v_type = 'redirect';

if ($v_type == 'Download') {
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<title>YouTube Downloader</title>
		<link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
	</head>

	<body>
		<div class="download">
		<h1 class="download-heading">Youtube Downloader Results</h1>
<?php
} // if ($v_type == 'Download')

// Get the video info page
$video_info = 'http://www.youtube.com/get_video_info?&video_id='.$v_id.'&asv=3&el=detailpage&hl=en_US';
$video_info = curlGet($video_info);

//$thumbnail_url = $title = $url_encoded_fmt_stream_map = $type = $url = '';

parse_str($video_info);

echo '<div id="info">';
switch ($config['ThumbnailImageMode'])
{
	case 1:
		echo '<a href="getimage.php?videoid='.$v_id.'&sz=hd" target="_blank"><img src="'.$thumbnail_url.'" border="0" hspace="2" vspace="2"></a>'; break;
	case 2:
		echo '<a href="getimage.php?videoid='.$v_id.'&sz=hd" target="_blank"><img src="getimage.php?videoid='.$v_id.'" border="0" hspace="2" vspace="2"></a>'; break;
}

echo '<p>'.$title.'</p>';
echo '</div>';

$v_title = $title;
$cleanedtitle = clean($title);

if (isset($url_encoded_fmt_stream_map))
{
	/* Now get the url_encoded_fmt_stream_map, and explode on comma */
	$formats_array = explode(',',$url_encoded_fmt_stream_map);
	if ($debug)
	{
		echo '<pre>';
//		print_r($formats_array);
		echo $video_info;
		echo '</pre>';
	}
}
else
{
	echo '<p>No encoded format stream found.</p>';
	echo '<p>Here is what we got from YouTube:</p>';
	echo $video_info;
}
if (count($formats_array) == 0)
{
	echo '<p>No format stream map found - was the video id correct?</p>';
	exit;
}

/* create an array of available download formats */
$avail_formats[] = '';
$i = 0;
//$ipbits = $ip = $itag = $sig = $quality = '';
$expire = time();

foreach ($formats_array as $format)
{
	parse_str($format);
	$avail_formats[$i]['itag'] = $itag;
	$avail_formats[$i]['quality'] = $quality;
	$type = explode(';',$type);
	$avail_formats[$i]['type'] = $type[0];
	$avail_formats[$i]['url'] = urldecode($url) . '&signature=' . $sig;
	parse_str(urldecode($url));
	$avail_formats[$i]['expires'] = date("G:i:s T", $expire);
	$avail_formats[$i]['ipbits'] = $ipbits;
	$avail_formats[$i]['ip'] = $ip;
	$i++;
}

if ($debug)
{
	echo '<p>These links will expire at '. $avail_formats[0]['expires'] .'</p>';
	echo '<p>The server was at IP address '. $avail_formats[0]['ip'] .' which is an '. $avail_formats[0]['ipbits'] .' bit IP address. ';
	echo 'Note that when 8 bit IP addresses are used, the download links may fail.</p>';
}
if ($v_type == 'Download')
{
	echo '<p align="center">List of available formats for download:</p>';
	echo '<ul>';

/* now that we have the array, print the options */
for ($i = 0; $i < count($avail_formats); $i++)
{
	echo '<li>';
	echo '<span class="itag">'.$avail_formats[$i]['itag'].'</span> ';
	if ($config['VideoLinkMode'] == 'direct' || $config['VideoLinkMode'] == 'both')
		echo '<a href="' . $avail_formats[$i]['url'] . '&title='.$cleanedtitle.'" class="mime">' . $avail_formats[$i]['type'] . '</a> ';
	else
		echo '<span class="mime">' . $avail_formats[$i]['type'] . '</span> ';
	echo '<small>(' .  $avail_formats[$i]['quality'];
	if($config['VideoLinkMode']=='proxy'||$config['VideoLinkMode']=='both')
		echo ' / '.'<a href="download.php?mime='.$avail_formats[$i]['type'].'&title='.urlencode($v_title).
			'&token='.base64_encode($avail_formats[$i]['url']).'" class="dl">download</a>';
	echo ')</small> '.'<small><span class="size">'.Size(get_size($avail_formats[$i]['url'])).'</span></small>'.'</li>';
}
echo '</ul><small>Note that you initiate download either by clicking video format link or click "download" to use this server as proxy.</small>';
?>
	</body>
</html>

<?php
}
else
{
/* In this else, the request didn't come from a form but from something else
 * like an RSS feed.
 * As a result, we just want to return the best format, which depends on what
 * the user provided in the url.
 * If they provided "format=best" we just use the largest.
 * If they provided "format=free" we provide the best non-flash version
 * If they provided "format=ipad" we pull the best MP4 version
 */

	$format =  $_REQUEST['format'];
	$target_formats = '';
	switch ($format)
	{
		case "best": /* largest formats first */
						$target_formats = array('38', '37', '46', '22', '45', '35', '44', '34', '18', '43', '6', '5', '17', '13');
						break;
		case "free": /* Here we include WebM but prefer it over FLV */
						$target_formats = array('38', '46', '37', '45', '22', '44', '35', '43', '34', '18', '6', '5', '17', '13');
						break;
		case "ipad": /* here we leave out WebM video and FLV - looking for MP4 */
						$target_formats = array('37','22','18','17');
						break;
		default:	 /* If they passed in a number use it */
						if (is_numeric($format))
							$target_formats[] = $format;
						else
							$target_formats = array('38', '37', '46', '22', '45', '35', '44', '34', '18', '43', '6', '5', '17', '13');
						break;
	}
	/* Now we need to find our best format in the list of available formats */
//	$best_format = '';
	for ($i=0; $i < count($target_formats); $i++)
		for ($j=0; $j < count ($avail_formats); $j++)
			if($target_formats[$i] == $avail_formats[$j]['itag'])
			{
				$best_format = $j;
				break 2;
			}

	if ((isset($best_format)) && (isset($avail_formats[$best_format]['url'])) && (isset($avail_formats[$best_format]['type'])))
	{
		$redirect_url = $avail_formats[$best_format]['url'].'&title='.$cleanedtitle;
		$content_type = $avail_formats[$best_format]['type'];
	}
	if (isset($redirect_url))
		header("Location: $redirect_url");

}
?>
