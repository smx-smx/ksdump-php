<?php
ini_set('memory_limit', -1);
use Smx\Kaitai\KaitaiCompilerFactory;
use Smx\Kaitai\KaitaiDumper;
use Smx\Kaitai\KaitaiLogger;
use Smx\Kaitai\ServiceContainer;
use Smx\Kaitai\ServicesKey;

require_once __DIR__ . '/vendor/autoload.php';

if($argc < 4){
    fwrite(STDERR, "Usage: {$argv[0]} -r [require_file] <file.ksy> <binary> <output.json>");
    exit(1);
}
$args = getopt('r:', [], $optind);
$requireFile = $args['r'] ?? null;

$useRequireFile = false;
if($requireFile !== null && file_exists($requireFile)){
	include($requireFile);
	$useRequireFile = true;
}
$ksyFile = $argv[$optind++];
$binFile = $argv[$optind++];
$jsonFile = $argv[$optind++];

$logOut = STDOUT;
$ctx = new ServiceContainer();
$ctx->addService(ServicesKey::LOGGER, new KaitaiLogger($logOut));

$kcf = new KaitaiCompilerFactory($ksyFile);
if($useRequireFile){
	$kcf->setOpaqueTypes(true);
}
$kcf->setOutputDirectory('out');
$outDir = $kcf->getOutputDirectory();
$result = $kcf->run();

fwrite($logOut, "Compilation OK\n");
$kd = new KaitaiDumper($ctx, $outDir, $result);

$jsonTree = $kd->dumpKsy($ksyFile, $binFile);
// $FIXME: use streaming json encoder
$jsonHandle = new SplFileObject($jsonFile, 'wb');
$jsonHandle->fwrite(json_encode($jsonTree, JSON_PRETTY_PRINT));
$jsonHandle = null;
