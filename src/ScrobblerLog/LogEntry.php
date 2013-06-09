<?php
namespace ScrobblerLog;

class LogEntry
{
	protected $artistName;
	protected $albumName;
	protected $trackName;
	protected $trackAlbumPosition;
	protected $trackDuration;
	protected $skipped = false;
	protected $listenTime;
	protected $musicBrainzID;
}