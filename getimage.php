<?PHP
include_once('config.php');

$v_id = $_REQUEST['videoid'];
if (isset($v_id) && !empty($v_id))
{
	if (strlen($v_id) > 11)
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

$thumb = 'mqdefault';
/*
 *    Player Background Thumbnail	480x360px	:	http://i1.ytimg.com/vi/VIDEO_ID/0.jpg
 *    Normal Quality Thumbnail		120x90px	:	http://i1.ytimg.com/vi/VIDEO_ID/default.jpg
 *    Medium Quality Thumbnail		320x180px	:	http://i1.ytimg.com/vi/VIDEO_ID/mqdefault.jpg
 *    High Quality Thumbnail		480x360px	:	http://i1.ytimg.com/vi/VIDEO_ID/hqdefault.jpg
 *    Start Thumbnail				120x90px	:	http://i1.ytimg.com/vi/VIDEO_ID/1.jpg
 *    Middle Thumbnail				120x90px	:   http://i1.ytimg.com/vi/VIDEO_ID/2.jpg
 *    End Thumbnail					120x90px	:	http://i1.ytimg.com/vi/VIDEO_ID/3.jpg
 */
if(!empty($_GET['sz']))
{
        $arg = $_GET['sz'];
		switch ($arg)
		{
			case 'hd':
				$thumb = 'hqdefault';
				break;
			case 'sd':
				$thumb = 'default';
				break;
			default:
				$thumb = $arg;
				break;
		}
}

$thumbnail_url = "http://i1.ytimg.com/vi/$v_id/$thumb.jpg";
header("Content-Type: image/jpeg");
readfile($thumbnail_url);
?>
