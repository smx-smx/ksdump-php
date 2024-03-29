<?php
namespace Smx\Kaitai;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionObject;
use RuntimeException;
use Throwable;

class KaitaiDumper {
	private string $outputDir;
	private array $result;
	private KaitaiLogger $logger;
	private ?int $strLimit = null;
	private bool $useHex = false;
	private bool $keepGoing = false;

	public function __construct(ServiceContainer $ctx, string $outputDir, array $result){
		$this->logger = $ctx->getService(ServicesKey::LOGGER);
		$this->outputDir = $outputDir;
		$this->result = $result;
	}

	public function setKeepGoing(bool $keepGoing){
		$this->keepGoing = $keepGoing;
		return $this;
	}

	public function setStringLimit(?int $limit){
		$this->strLimit = $limit;
		return $this;
	}
	public function useHexFormat(bool $toggle){
		$this->useHex = $toggle;
		return $this;
	}

	private function getMainClass(string $input){
		$finfo = $this->result[$input] ?? null;
		if($finfo === null){
			throw new InvalidArgumentException("{$input} not found in result");
		}
		$errors = $finfo['errors'] ?? null;
		if($errors !== null){
			// $TODO: report_errors
			throw new RuntimeException('compilation gave errors');
		}

		$classes = $finfo['output']['php'];

		$main_class_name = null;
		$main = null;

		$idx = 0;
		foreach($classes as $k => $klass){
			$errors = $klass['errors'] ?? null;
			if($errors !== null){
				// $TODO: report_errors
				throw new RuntimeException('compilation gave errors');
			}
			$compiled_name = $klass['files'][0]['fileName'];
			$compiled_path = Util::path_combine($this->outputDir, $compiled_name);

			$this->logger->logInfo("...... loading {$compiled_path}");
			include($compiled_path);

			if($idx++ == 0){
				$main = $classes[$finfo['firstSpecName']];
				$main_class_name = $main['topLevelName'];
			}
		}
		return new ReflectionClass($main_class_name);
	}

	private array $objects = [];
	private array $jsonCache = [];

	private function formatObject($item){
		$hash = spl_object_hash($item);
		if(isset($this->objects[$hash])){
			return $this->jsonCache[$hash];
		}
		$this->objects[$hash] = $item;

		$ro = new ReflectionObject($item);
		$methods = $ro->getMethods();		
		
		$json = [];
		if(empty($methods)) return $json;
		foreach($methods as $meth){
			if($meth->isStatic()) continue;
			$name = $meth->getName();
			if(str_starts_with($name, '_')) continue;

			$klass = get_class($item);
			if($klass === 'Kaitai\Struct\Stream') continue;

			try {
				$value = $item->{$name}();
				$json[$name] = $this->formatThing($value);
			} catch(Throwable $e){
				$objName = $ro->getName();
				print(" ==== ERROR in {$objName}:{$name}" . (($this->keepGoing) ? ", ignoring...\n" : "\n"));
				if(!$this->keepGoing){
					throw $e;
				}
			}
			
		}

		$this->jsonCache[$hash] = $json;
		return $json;
	}

	private function formatArray($array){
		if(empty($array)) return;

		$json = [];
		foreach($array as $itm){
			$json[] = $this->formatThing($itm);
		}
		return $json;
	}

	private function formatPrimitive($thing){
		if(is_string($thing)){
			if($this->useHex && !ctype_print($thing) && strlen($thing) > 0){
				$thing = "0x" . bin2hex($thing);
			}
			if($this->strLimit !== null && strlen($thing) > $this->strLimit){
				$thing = substr($thing, 0, $this->strLimit) . "...";
			}
		}
		return $thing;
	}

	private function formatThing($thing){
		if(is_object($thing)){
			return $this->formatObject($thing);
		} else if(is_array($thing)){
			return $this->formatArray($thing);
		} else {
			return $this->formatPrimitive($thing);
		}
	}

	private function formatTree($obj, $out){
		return $this->formatThing($obj);
	}

	public function dumpKsy(string $input, string $binFile){
		$mainClass = $this->getMainClass($input);
		if($mainClass === null){
			throw new RuntimeException('failed to load main class');
		}
		$fromFile = $mainClass->getMethod('fromFile');
		$tree = $fromFile->invoke(null, $binFile);
		$json = $this->formatTree($tree, STDOUT);
		return $json;
	}
}
