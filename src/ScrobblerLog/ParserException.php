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

use Exception;

class ParserException extends Exception
{
} 