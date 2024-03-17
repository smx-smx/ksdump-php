<?php
namespace Smx\Kaitai;

use Exception;
use RuntimeException;

class KaitaiCompiler {
	private array $args;
	
	/** @var ?resource */
	private $hProc = null;
	/** @var ?resource */
	private $stdout = null;
	/** @var ?resource */
	private $stderr = null;

	public function __construct(array $args){
		if(Util::isWindows()){
			$this->args = ['cmd', '/C', ...$args];
		} else {
			$this->args = $args;
		}
	}

	public function stop(){
		$running = is_resource($this->hProc);
		if($running){
			proc_terminate($this->hProc, SIGKILL);
		}
		return $running;
	}
	
	public function start(){
		if(is_resource($this->hProc)){
			throw new RuntimeException('compiler already started');
		}
		
		if(Util::isWindows()){
			$cmd = implode(' ', array_map(function(string $arg){
				if($arg[0] == '-' || !str_contains($arg, ' ')) return $arg;
				return escapeshellarg($arg);
			}, $this->args));
		} else {
			$cmd = $this->args;
		}
		$this->hProc = proc_open($cmd, [
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w']
		], $p);
		$this->stdout = $p[1];
		$this->stderr = $p[2];
	}

	public function wait(){
		$stdout = stream_get_contents($this->stdout);
		$stderr = stream_get_contents($this->stderr);

		$exitCode = proc_close($this->hProc);
		if($exitCode == 127){
			throw new KaitaiException('unable to find and execute kaitai-struct-compiler in your PATH');
		} else if($exitCode !== 0){
			throw new Exception(''
				. "crashed (exit status = {$exitCode})\n"
				. "== STDOUT\n{$stdout}\n"
				. "== STDERR\n{$stderr}\n"
			);
		}
		$log = json_decode($stdout, true);
		return $log;
	}
}