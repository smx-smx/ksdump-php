<?php
namespace Smx\Kaitai;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class KaitaiCompilerFactory {
	private array $args = [
		'--ksc-json-output',
		//'--debug',
		'--read-pos',
		'-t', 'php'
	];

	private string $compiler;
	private string $ksyFile;
	private string $tmpDir;
	private ?string $importPath = null;
	private ?bool $opaqueTypes = null;
	private bool $deleteOnClose = true;

	public function __construct(string $ksyFile){
		$compiler = getenv('KAITAI_COMPILER');
		if($compiler === false){
			$compiler = 'kaitai-struct-compiler';
		}
		$this->compiler = $compiler;
		$this->args = [$compiler, ...$this->args];

		$this->ksyFile = $ksyFile;
		$this->tmpDir = Util::path_combine(sys_get_temp_dir(), 'ksy-' . uniqid());
	}

	public function setOutputDirectory(string $dir){
		$this->tmpDir = $dir;
		$this->deleteOnClose = false;
		return $this;
	}
	public function getOutputDirectory(){
		return $this->tmpDir;
	}
	public function setImportPath(?string $importPath){
		$this->importPath = $importPath;
		return $this;
	}
	public function setOpaqueTypes(?bool $opaqueTypes){
		$this->opaqueTypes = $opaqueTypes;
		return $this;
	}

	private function appendArguments(string ...$args){
		$this->args = [...$this->args, ...$args];
		return $this;
	}

	public function run(){
		if($this->importPath !== null){
			$this->appendArguments('--import-path', $this->importPath);
		}
		if($this->opaqueTypes !== null){
			$this->appendArguments('--opaque-types', 
				$this->opaqueTypes === true ? 'true' : 'false');
		}

		if(!Util::isWindows()) {
			$this->appendArguments('--');
		}
		$this->appendArguments('-d', $this->tmpDir);
		$this->appendArguments($this->ksyFile);
		

		if(!file_exists($this->tmpDir)){
			mkdir($this->tmpDir);
		}

		if($this->deleteOnClose){
			register_shutdown_function(function(){
				Util::rmrf($this->tmpDir);
			});
		}
		$compiler = new KaitaiCompiler($this->args);
		$compiler->start();
		return $compiler->wait();
	}
}
