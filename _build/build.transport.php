<?php
header('Content-Type: text/html; charset=UTF-8');

$mtime	= microtime();
$mtime	= explode(' ', $mtime);
$mtime	= $mtime[1] + $mtime[0];
$tstart	= $mtime;
set_time_limit(0);

/* define package */
define('PKG_NAME','ptoaric');
define('PKG_NAME_LOWER',strtolower(PKG_NAME));
define('PKG_VERSION','0.1.4');
define('PKG_RELEASE','beta');

/* define sources */
$root = dirname(dirname(__FILE__)).'/';
$sources = array(
	'root'			=> $root,
	'build'			=> $root . '_build/',
	'data'			=> $root . '_build/data/',
	'resolvers'		=> $root . '_build/resolvers/',
	'chunks'		=> $root . 'core/components/'.PKG_NAME_LOWER.'/elements/chunks/',
	'snippets'		=> $root . 'core/components/'.PKG_NAME_LOWER.'/elements/snippets/',
	'plugins'		=> $root . 'core/components/'.PKG_NAME_LOWER.'/elements/plugins/',
	'lexicon'		=> $root . 'core/components/'.PKG_NAME_LOWER.'/lexicon/',
	'docs'			=> $root . 'core/components/'.PKG_NAME_LOWER.'/docs/',
	'pages'			=> $root . 'core/components/'.PKG_NAME_LOWER.'/elements/pages/',
	'source_core'	=> $root . 'core/components/'.PKG_NAME_LOWER,
	'source_assets'	=> $root . 'assets/components/'.PKG_NAME_LOWER
);
unset($root);

/* override with your own defines here (see build.config.sample.php) */
require_once $sources['build'] . '/build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
require_once $sources['build'] . '/includes/functions.php';

$modx= new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME_LOWER,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER,false,true,'{core_path}components/'.PKG_NAME_LOWER.'/');
$modx->log(modX::LOG_LEVEL_INFO,'Created Transport Package and Namespace.<br>');

/* create category */
$category = $modx->newObject('modCategory');
$category->set('id',1);
$category->set('category',PKG_NAME);

/* add snippets */
$snippets = include $sources['data'].'transport.snippets.php';
if (!is_array($snippets)) {
	$modx->log(modX::LOG_LEVEL_ERROR,'Could not package in snippets.<br>');
} else {
	$category->addMany($snippets);
	$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($snippets).' snippets.<br>');
}
/* add plugins */
$plugins = include $sources['data'].'transport.plugins.php';
if (!is_array($plugins)) {
	$modx->log(modX::LOG_LEVEL_ERROR,'Could not package in plugins.<br>');
} else {
	$category->addMany($plugins);
	$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($plugins).' plugins.<br>');
}

/* create category vehicle */
$attr = array(
	xPDOTransport::UNIQUE_KEY => 'category',
	xPDOTransport::PRESERVE_KEYS => false,
	xPDOTransport::UPDATE_OBJECT => true,
	xPDOTransport::RELATED_OBJECTS => true,
	xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
		'Children' => array(
			xPDOTransport::PRESERVE_KEYS => false,
			xPDOTransport::UPDATE_OBJECT => true,
			xPDOTransport::UNIQUE_KEY => 'category',
			xPDOTransport::RELATED_OBJECTS => true,
			xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
				'Snippets' => array(
					xPDOTransport::PRESERVE_KEYS => false,
					xPDOTransport::UPDATE_OBJECT => true,
					xPDOTransport::UNIQUE_KEY => 'name',
				),
				'Chunks' => array(
					xPDOTransport::PRESERVE_KEYS => false,
					xPDOTransport::UPDATE_OBJECT => false,
					xPDOTransport::UNIQUE_KEY => 'name',
				)
			)
		),
		'Snippets' => array(
			xPDOTransport::PRESERVE_KEYS => false,
			xPDOTransport::UPDATE_OBJECT => true,
			xPDOTransport::UNIQUE_KEY => 'name',
		),
		'Chunks' => array (
			xPDOTransport::PRESERVE_KEYS => false,
			xPDOTransport::UPDATE_OBJECT => false,
			xPDOTransport::UNIQUE_KEY => 'name',
		)
	)
);
$vehicle = $builder->createVehicle($category,$attr);

$modx->log(modX::LOG_LEVEL_INFO,'Adding file resolvers to category...<br>');
$vehicle->resolve('file',array(
	'source' => $sources['source_core'],
	'target' => "return MODX_CORE_PATH . 'components/';",
));
// Add assets source
$vehicle->resolve('file',array(
    'source' => $sources['source_assets'],
    'target' => "return MODX_ASSETS_PATH . 'components/';",
));
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in resolvers.<br>'); flush();
$builder->putVehicle($vehicle);

/* load system settings */
$settings = include $sources['data'].'transport.settings.php';
if (!is_array($settings)) {
	$modx->log(modX::LOG_LEVEL_ERROR,'Could not package in settings.<br>');
} else {
	$attributes= array(
		xPDOTransport::UNIQUE_KEY => 'key',
		xPDOTransport::PRESERVE_KEYS => true,
		xPDOTransport::UPDATE_OBJECT => false,
	);
	foreach ($settings as $setting) {
		$vehicle = $builder->createVehicle($setting,$attributes);
		$builder->putVehicle($vehicle);
	}
	$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($settings).' System Settings.<br>');
}
unset($settings,$setting,$attributes);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
	'changelog'	=> file_get_contents($sources['docs'] . 'changelog.txt'),
	'license'	=> file_get_contents($sources['docs'] . 'license.txt'),
	'readme'	=> file_get_contents($sources['docs'] . 'readme.txt')
));
$modx->log(modX::LOG_LEVEL_INFO,'Added package attributes and setup options.<br>');

/* zip up package */
$modx->log(modX::LOG_LEVEL_INFO,'Packing up transport package zip...<br>');
$builder->pack();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\n<br>Package Built.<br>\nExecution time: {$totalTime}\n");

exit ();