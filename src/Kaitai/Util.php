<?php
namespace Smx\Kaitai;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Util {
	public static function isWindows(){
		static $res = (PHP_OS_FAMILY === 'Windows');
		return $res;
	}
	public static function path_combine(string ...$parts){
		return implode(DIRECTORY_SEPARATOR, $parts);
	}
	public static function rmrf(string $root){
		if(!file_exists($root)) return;
		if(is_file($root)){
			unlink($root);
			return;
		}

		$dit = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
		$it = new RecursiveIteratorIterator($dit, RecursiveIteratorIterator::CHILD_FIRST);
		foreach($it as $file){
			/** @var SplFileInfo $file */
			$path = $file->getPathname();
			if($file->isDir()){
				rmdir($path);
			} else {
				unlink($path);
			}
		}
		rmdir($root);
	}
}