<?php
namespace ScrobblerLog;

use RuntimeException;
use DateTimeZone;

class Parser
{
	protected $logFile;
	
	//parsed header
	protected $logVersion;
	protected $timezone;
	protected $client;
	
	//stats
	protected $totalTracks;
	protected $tracksPlayed;
	protected $tracksSkipped;
	
	
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
		
		$this->parseHeader();
		var_dump($this);
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
	
	protected function parseTrack()
	{
		
	}
	
}