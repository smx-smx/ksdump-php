<?php
namespace Smx\Kaitai;

class ServiceContainer {
	private array $services = [];
	public function addService(ServicesKey $key, $svc){
		$this->services[$key->value] = $svc;
		return $this;
	}
	public function getService(ServicesKey $key){
		return $this->services[$key->value] ?? null;
	}
}