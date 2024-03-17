<?php
namespace Smx\Kaitai;

use RuntimeException;

class KaitaiException extends RuntimeException {
	public function __construct(string $message){
		parent::__construct($message);
	}
}