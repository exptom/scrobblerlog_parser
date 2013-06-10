scrobblerlog_parser
===================

Audioscrobbler .scrobbler.log Parser, written in PHP and licensed under the MIT license (see LICENSE.txt)

A very simple parser that can handle v1.0 and v1.1 audioscrobbler log files as defined on the [audioscrobbler wiki](http://www.audioscrobbler.net/wiki/Portable_Player_Logging).

## Usage
```php
$parser = new ScrobblerLog\Parser('path/to/scrobbler.log');
$parsedData = $parser->parse();
```

If the device that has generated the log file is not timezone aware then the timezone must be specified before executing a parse:
```php
$parser = new ScrobblerLog\Parser('path/to/scrobbler.log');
$parsedData = $parser->setTimezone('Europe/London')->parse();
```
The timezone can be any of the timezone strings listed in the [PHP docs](http://php.net/manual/en/timezones.php).

The parsed data is returned as an array of `LoggedTrack` objects.

## Other Features
Some log file information is available after a parse has been executed:
```php
$parser->getClient(); //get the name of the device that generated the scrobbler.log
$parser->getVersion(); //get the version of the scrobbler.log format that has been parsed (1.0 or 1.1)
```

and also some very basic stats...
```php
$parser->getTotalTracksLogged(); //get total number of tracks logged
$parser->getTotalTracksPlayed(); //get total number of tracks listend to
$parser->getTotalTracksSkipped(); //get total number of tracks skipped
$parser->getTotalPlayTime(); //get total length (in seconds) or tracks played
```