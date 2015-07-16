<?php
include_once('config.php');
ob_start();

// Functions  {{{

/*
 * Convert time in H:M:S format
 */
function len($seconds)
{
	$hr = intval($seconds/3600);
	$min = intval(($seconds%3600)/60);
	$seconds %= 60;

	if ($hr != 0)
		$duration = $hr.':';
	else
		$duration = '';
	$duration .= $min.':'.$seconds;

	return $duration;
}

/*
 *  Convert size in human readable format
 */
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
//}}}

$v_id = $_REQUEST['videoid'];
if (isset($v_id) && !empty($v_id))
{
	// If just videoid isn't passed, extract it from url given (if valid)
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

// Get the video info page
$info_link = 'http://www.youtube.com/get_video_info?&video_id='.$v_id.'&asv=3&el=detailpage&hl=en_US';
$video_info = curlGet($info_link);
if (empty($video_info))
{
	echo '<p>No data received from YouTube.</p>';
	if ($debug)
		echo "<a href=$info_link>Data_Link</a>";
	exit;
}
parse_str($video_info);

if (isset($url_encoded_fmt_stream_map))
	$formats_array = explode(',', $url_encoded_fmt_stream_map);
else
{
	echo '<p>No encoded format stream found.</p>';
	echo '<p>Here is what we got from YouTube:</p>';
	echo '<p>';
	echo $video_info;
	echo '</p>';
	exit;
}

switch ($config['ThumbnailImageMode'])
{
	case 1: $v_image = $iurlmq;
			break;
	case 2: $v_image = "getimage.php?videoid=$v_id";
			break;
}

$v_title = $title;
$v_length = len($length_seconds);

/* create an array of available download formats */
$i = 0;
foreach ($formats_array as $format)
{
	parse_str($format, $avail_formats[$i]);
	$avail_formats[$i]['type'] = explode(';', $avail_formats[$i]['type'])[0];
	$avail_formats[$i]['url'] = urldecode($avail_formats[$i]['url']).'&title='.urlencode($v_title);
	$avail_formats[$i]['proxy'] = 'download.php?title='.urlencode($v_title).'&mime='.urlencode($avail_formats[$i]['type']).
									'&token='.base64_encode($avail_formats[$i]['url']);
	$avail_formats[$i]['res'] = explode('/', explode(',', $fmt_list)[$i])[1];
	$i++;
}

if (isset($_REQUEST['type']) && $_REQUEST['type'] == 'direct')
{
/*
 * Just redirect to file in given format.
 */

	$format =  $_REQUEST['format'];
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
	}

	// Find best available format
	for ($i=0; $i < count($target_formats); $i++)
		for ($j=0; $j < count ($avail_formats); $j++)
			if($target_formats[$i] == $avail_formats[$j]['itag'])
			{
				$best_format = $j;
				break 2;
			}

	// Redirect to file.
	if ((isset($best_format)) && (isset($avail_formats[$best_format]['url'])) && (isset($avail_formats[$best_format]['type'])))
		header('Location: '.$avail_formats[$best_format]['url']);
	exit;
}
elseif (isset($_REQUEST['type']) && $_REQUEST['type'] == 'json')
{
	// Create JSON response
	class json
	{
		public $Status = "";
		public $Data = array();
	}
	$j = new json();
	$j->Status = "OK";
	// TODO : Add Fail Status.

	$i = 0;
	foreach ($avail_formats as $vid)
	{
		$D[$i]['itag'] = $vid['itag'];
		$D[$i]['URL'] = $vid['url'];
		$D[$i]['format'] = $vid['type'];
		$D[$i]['quality'] = $vid['res'];
		$i++;
	}
	$j->Data = $D;
	// Convert it to sting and echo it.
	echo json_encode($j, JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
	exit;
}
?>

<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?=$v_title?></title>
		<link href="css/bootstrap.min.css" rel="stylesheet">
	</head>

	<body>
		<div class="container col-md-offset-2 col-md-9">
			<div class="header">
				<img src="<?=$v_image?>" class="img-responsive img-rounded" align="left" style="box-shadow: 0 0 10px 1px #666; margin: 30px 30px 70px 0px">
				<h1 style="padding-top: 110px; word-break: break-all"><small><?=$v_title?></small></h1>
				<blockquote style="float: left">
					<p class="text-primary"><?=$v_length?></p>
				</blockquote>
			</div>
			<div class="main">
				<table class="table table-striped table-condensed">
					<thead>
						<tr class="text-info">
							<th>itag</th>
							<th>Format</th>
							<th>Resolution</th>
							<th>Quality</th>
							<th>Size</th>
							<th>Download</th>
						</tr>
					</thead>
					<tbody>
<?php
foreach ($avail_formats as $vid)
{
	echo '						<tr>';
	echo '							<td><strong>'.$vid['itag'].'</strong></td>';
	echo '							<td>'.$vid['type'].'</td>';
	echo '							<td>'.$vid['res'].'</td>';
	echo '							<td>'.$vid['quality'].'</td>';
	echo '							<td>'.Size(get_size($vid['url'])).'</td>';
	if ($config['VideoLinkMode'] == 'direct')
		echo '							<td><a href="'.$vid['url'].'" class="btn btn-primary" role="button">Download</a></td>';
	else
		echo '							<td><a href="'.$vid['proxy'].'" class="btn btn-primary" role="button">Download</a></td>';
	echo '						</tr>';
}
?>
					</tbody>
				</table>
			</div>
<?php
if ($debug)
		echo "<pre>$video_info</pre>";
?>
		</div>
	</body>
</html>
