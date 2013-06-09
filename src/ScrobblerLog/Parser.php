<?php
/**
 * Scrobbler.log Parser
 * 
 * Library to pass the audioscrobbler log file produce by Rockbox and other software
 * Implementation based on log file format defined here: http://www.audioscrobbler.net/wiki/Portable_Player_Logging
 * 
 * @license Released under the MIT licence, Copyright (c) 2013 Tom Ford <code@bitvark.com>
 * @author Tom Ford <code@bitvark.com>
 *
 */

namespace ScrobblerLog;

use DateTime;
use DateTimeZone;
use Exception;
use RuntimeException;
use InvalidArgumentException;

class Parser
{
	protected $logFormatVersions = array('1.0','1.1');
	
	protected $logFile;
	
	//parsed data
	protected $logVersion;
	protected $timezone;
	protected $client;
	protected $tracks = array();
	
	//parser stats
	protected $totalTracks = 0;
	protected $tracksPlayed = 0;
	protected $tracksSkipped = 0;
	protected $totalTrackLength = 0;
	
	/**
	 * Create a new instance of .scrobbler.log file parser
	 * @param string $logFile Absolute path to .scrobbler.log file
	 * @throws RuntimeException
	 */
	public function __construct($logFile)
	{
		if(file_exists($logFile) === false)
			throw new RuntimeException('scrobbler log file `'.$logFile.'` can not be found');
		
		if(is_readable($logFile) === false)
			throw new RuntimeException('scrobbler log file `'.$logFile.'` can not be read');
		
		$this->logFile = fopen($logFile,'r');
		
		if($this->logFile === false)
			throw new RuntimeException('scrobbler log file `'.$logFile.'` can not be opened for reading');
	}
	
	/**
	 * If a log file's timezone is unknown then the user must specify the timezone that their device clock is set to so that scrobbler play times can be converted to UTC
	 * @param string $timezoneName A valid timezone name as defined in the PHP manual: http://php.net/manual/en/timezones.php
	 * @throws InvalidArgumentException
	 */
	public function setTimezone($timezoneName)
	{
		try
		{
			$this->timezone = new DateTimeZone($timezoneName);
		}
		catch(Exception $e)
		{
			throw new InvalidArgumentException('The provided timezone `'.$timezoneName.'` is not recognised');
		}
		
		return $this;
	}
	
	/**
	 * Parse the log file contents
	 * @return array Contains a LoggedTrack class instance for each track within parsed log file
	 * @throws ParserException
	 */
	public function parse()
	{
		$this->parseHeader();
		
		//timezone must have been set by this point
		if($this->timezone === null)
			throw new ParserException('The log file does not specify a timezone. You must specify one using setTimezone()');
		
		//parse each track in log file
		while($track = fgets($this->logFile))
			$this->parseTrack($track);
		
		if(sizeof($this->tracks) === 0)
			throw new ParserException('The log file contains no track information.');
		
		return $this->tracks;
	}
	
	/**
	 * Get the name of the client that generated this log file
	 * @return string
	 */
	public function getClient()
	{
		return $this->client;
	}
	
	/**
	 * Get the log format version number
	 * @return string
	 */
	public function getVersion()
	{
		return $this->logVersion;
	}
	
	/**
	 * Parse the header part of the scrobbler log file.
	 * The header part is the first 3 lines containing log format version, log timezone and name of the client that generated the log file.
	 */
	protected function parseHeader()
	{
		//line 1 is version header
		$this->parseVersion(fgets($this->logFile));
		
		//line 2 is timezone header
		$this->parseTimezone(fgets($this->logFile));
		
		//line 3 is client header
		$this->parseClient(fgets($this->logFile));
	}
	
	/**
	 * Extract the log format version number from the version header 
	 * @param string $versionHeader
	 * @throws ParserException
	 */
	protected function parseVersion($versionHeader)
	{
		//version header format: #AUDIOSCROBBLER/X.X
		if($versionHeader === false)
			throw new ParserException('Version header does not exist in log file');
		
		$matches = array();
		$numMatches = preg_match('/#AUDIOSCROBBLER\/([0-9]+\.[0-9]+)/',$versionHeader,$matches);
		
		if($numMatches === 0 || $numMatches === false || isset($matches[1]) === false)
			throw new ParserException('Version header does not contain a valid version number');
		
		if(in_array($matches[1],$this->logFormatVersions) === false)
			throw new ParserException('Log format version `'.$matches[1].'` is not in list of known versions. Must be one of: '.implode(', ',$this->logFormatVersions));
		
		$this->logVersion = $matches[1];
	}
	
	/**
	 * Extract the log timezone from the timezone header
	 * @param string $timezoneHeader
	 * @throws ParserException
	 */
	protected function parseTimezone($timezoneHeader)
	{
		//timezone header format: #TZ/[UNKNOWN|UTC]
		if($timezoneHeader === false)
			throw new ParserException('Timezone header does not exist in log file');
		
		$matches = array();
		$numMatches = preg_match('/#TZ\/(UNKNOWN|UTC)/',$timezoneHeader,$matches);
		
		if($numMatches === 0 || $numMatches === false || isset($matches[1]) === false)
			throw new ParserException('Timezone header does not contain a valid timezone identifier - must be UNKNOWN or UTC');
		
		//if timezone is unknown we leave $this->timezone set to null. A call to $this->setTimezone() is then required before parsing the log file
		if($matches[1] === 'UTC')
			//we must blindly set the timezone to UTC. if a user has called setTimezone() but the log file says the timezone is UTC we must trust the log file timezone
			$this->timezone = new DateTimeZone('UTC');
	}
	
	/**
	 * Extract the name of the client that generated the log file from the client header
	 * @param string $clientHeader
	 * @throws ParserException
	 */
	protected function parseClient($clientHeader)
	{
		//client header format: #CLIENT/<Client Name>
		if($clientHeader === false)
			throw new ParserException('Client header does not exist in log file');
		
		$matches = array();
		$numMatches = preg_match('/#CLIENT\/(.*)/',$clientHeader,$matches);
		
		if($numMatches === 0 || $numMatches === false || isset($matches[1]) === false)
			throw new ParserException('Client header does not contain a valid client name');
		
		$this->client = $matches[1];
	}
	
	/**
	 * Parse a logged track
	 * @param string $track
	 * @throws ParserException
	 */
	protected function parseTrack($track)
	{
		$trackParts = explode("\t",$track);
	
		$track = new LoggedTrack();
		
		if($this->logVersion === '1.0' && count($trackParts) !== 7)
			throw new ParserException('Each logged track must contain 7 fields');
		if($this->logVersion === '1.1' && count($trackParts) !== 8)
			throw new ParserException('Each logged track must contain 8 fields');
			
		//artist name - required
		if(strlen($trackParts[0]) === 0)
			throw new ParserException('Log entry must contain an artist name');
		$track->artistName = $trackParts[0];
		
		//album name - optional
		if(strlen($trackParts[1]) > 0)
			$track->albumName = $trackParts[1];
		
		//track name - required
		if(strlen($trackParts[2]) === 0)
			throw new ParserException('Log entry must contain a track name');
		$track->trackName = $trackParts[2];
		
		//track position in album - optional
		if(strlen($trackParts[3]) > 0 && is_numeric($trackParts[3]))
			$track->trackAlbumPosition = (int)$trackParts[3];
		
		//song duration in seconds - required
		if(strlen($trackParts[4]) === 0)
			throw new ParserException('Log entry must contain track duration');
		$track->trackDuration = (int)$trackParts[4];
		
		//track skipped? - required
		if(strlen($trackParts[5]) !== 1 || ($trackParts[5] !== 'S' && $trackParts[5] !== 'L'))
			throw new ParserException('Log entry must specify if the track has been skipped');
		if($trackParts[5] === 'S')
			$track->skipped = true;
		
		//song start time - required
		if(strlen($trackParts[6]) === 0 || is_numeric($trackParts[6]) === false)
			throw new ParserException('Log entry must specify the time that the track started playing');
		
		$time = new DateTime('@'.$trackParts[6],$this->timezone);
		$time->setTimezone(new DateTimeZone('UTC'));
		$track->listenTime = $time;
		
		//music brainz id - optional
		if($this->logVersion === '1.1' && strlen(rtrim($trackParts[7])) > 0)
			$track->musicBrainzID = $trackParts[7];
		
		$this->tracks[] = $track;
		$this->updateParserStats($track);
	}
	
	/**
	 * Update parser statistics for track played
	 * @param LoggedTrack $track
	 */
	protected function updateParserStats(LoggedTrack $track)
	{
		$this->totalTracks++;
		
		if($track->skipped === true)
			$this->tracksSkipped++;
		else
			$this->tracksPlayed++;
		
		$this->totalTrackLength += $track->trackDuration;
	}
	
}