<?php

  /**********|| Thumbnail Image Configuration ||***************/
  #$config['ThumbnailImageMode']=0;		// don't show thumbnail image
  $config['ThumbnailImageMode']=1;		// show thumbnail image directly from YouTube
  #$config['ThumbnailImageMode']=2;		// show thumbnail image by proxy from this server

  /**********|| Video Download Link Configuration ||***************/
  $config['VideoLinkMode']='direct';	// show direct download link
  #$config['VideoLinkMode']='proxy';	// show proxy download link

  /**********|| Other ||***************/
  // Set your default timezone
  // use this link: http://php.net/manual/en/timezones.php
  date_default_timezone_set("Asia/Kolkata");

  // Debug mode
  #$debug=true; // debug mode on
  $debug=false; // debug mode off

  /**********|| Don't edit below ||***************/
  include_once('curl.php');
?>
