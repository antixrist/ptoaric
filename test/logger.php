<?php
class logger {

	public $log            = '';
	public $logFile        = '';
	public $time_start     = 0;
	public $time_current   = 0;
	public $time_last      = 0;
	public $time_all       = 0;
	public $memory_start   = 0;
	public $memory_current = 0;
	public $memory_last    = 0;
	public $memory_all     = 0;


	function __construct () {
		$this->logReset();
	}

	function setLogFile ($logFile = '') {
		$this->logFile = ($logFile) ? $logFile : false;
	}
	
	function logReset () {
		$this->time_last	= $this->time_start	= microtime(true);
		$this->memory_last	= $this->memory_start	= memory_get_usage();

		$this->time_all		= 0;
		$this->memory_all	= 0;
	}

	function log($text = '', $metrics = true, $echo = true) {
		if ($metrics) {
			$microtime = microtime(true);
			$this->time_all		= $microtime - $this->time_start;
			$this->time_current	= $microtime - $this->time_last;
			$this->time_last	= $microtime;

			$memory = memory_get_usage();
			$this->memory_all	= $memory - $this->memory_start;
			$this->memory_current	= $memory - $this->memory_last;
			$this->memory_last	= $memory;

			$text = $this->getFormattedText($text);

			$this->log .= strip_tags($text);
		} else {
			$_text		= $this->getFormattedText($text, false);
			$this->log	.= strip_tags($_text);
			$text		= "<br>". $text ."<br><br>";
		}
		if ($echo) {
			echo nl2br($text);
		}
	}

	function getFormattedText ($text, $metrics = true) {
		if ($metrics) {
			$text =  '<b>'. $text .'</b>
время: '. sprintf('%01.16f', $this->time_current) .' сек. (всего '. sprintf('%01.16f', $this->time_all) .' сек.)
память: '. number_format($this->memory_current / 1024, 2,","," ") .' Кб. (всего ' . number_format($this->memory_all / 1024, 2,","," ") .' Кб.)

';
			$text =  '<b>'. $text .'</b>
время: '. sprintf('%01.16f', $this->time_current) .' сек. (всего '. sprintf('%01.16f', $this->time_all) .' сек.)

';
		} else {
			$text = "
". $text ."

";
		}
		return $text;
	}

	function saveLog () {
		$result = false;
		if (!$this->logFile) return $result;
		$fp = @fopen($this->logFile, 'w+');
		if ($fp) {
			$result = @fwrite($fp, $this->log);
			@fclose($fp);
		}
		return $result;
	}
}
