<!DOCTYPE html>
<html lang="en">
	<head>
		<title>Youtube Downloader</title>
		<link href="css/bootstrap.min.css" rel="stylesheet" media="screen">
	</head>

	<body style="margin-top: 10%">
		<h1 class="col-md-offset-5">YTD<small>YouTube Downloader</small></h1>
		<div class="container col-md-6 col-md-offset-4" style="margin-top: 50px">
			<form class="form-inline" method="GET" id="download" action="getvideo.php">
				<div class="input-group">
					<span class="input-group-addon">youtube.com/watch?v=</span>
					<input class="form-control" type="text" name="videoid" id="videoid" size="20" placeholder="VideoID" />
				</div>
				<input class="btn btn-primary" type="submit" name="type" id="type" value="Download" />
			</form>
		</div>
	</body>
</html>
