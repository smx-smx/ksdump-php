<?php
namespace Smx\Kaitai;

class KaitaiLogger {
	private $outStream;
	public function __construct($outStream){
		$this->outStream = $outStream;
	}
	public function logInfo(string $msg){
		fwrite($this->outStream, "[INFO] {$msg}\n");
	}
	public function logErr(string $msg){
		fwrite($this->outStream, "[ ERR] {$msg}\n");

	}
	public function logWarn(string $msg){
		fwrite($this->outStream, "[WARN] {$msg}\n");
	}
}