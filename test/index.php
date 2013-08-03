<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
set_time_limit(0);
header('Content-Type: text/html; charset=UTF-8');

$current_dir = dirname(__FILE__) .'/';

include 'logger.php';
$logger = new logger($current_dir. 'log.txt');
$logger->log('Стартуем тестирование');

// Подключаем MODX
define('MODX_API_MODE', true);
require (dirname($current_dir). '/index.php');
$logger->log('MODX подключен');

// Включаем обработку ошибок
$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
// $modx->resource нужен для phpThumbsOf
$modx->resource = $modx->getObject('modResource', array(
	'id' => 1
));
$logger->log('MODX готов к работе');

$fileHandler = $modx->getService('fileHandler', 'modFileHandler');
$file = $fileHandler->make($current_dir .'images/picture.jpg');
if (!$file->exists()) {
	$logger->log('<b>Отсутствует файл "'. $file->getPath() .'"</b>', false);
	$logger->saveLog();
	exit;
}
$logger->log('Обрабатываем файл "'. $file->getPath() .'"', false);

// Создаём копии изображения
$filepath		= $fileHandler->getDirectoryFromFile($file->getPath());
$filename		= $file->getBaseName();
$fileExt		= $file->getExtension();
$filenameLength	= strlen($filename);
$extLength		= strlen($fileExt);
$cut = $filenameLength - $extLength - 1;
if (strlen($filename) > $cut) {
	$filename = substr($filename, 0, $cut);
}



// $logger->log($filepath . $filename .'.'. $fileExt, false);

$filesCount = 50;

// Делаем копии файлов
$i		= 1;
$count	= 0;
while ($i <= $filesCount) {
	$oldfile = $filepath . $filename .'.'. $fileExt;
	$newfile = $filepath . $filename .'-'. $i .'.'. $fileExt;
	if (file_exists($newfile)) {
		$i++;
		continue;	
	}
	if (copy($oldfile, $newfile)) {
		$i++;
		$count++;
	} else {
		$logger->log('<b>Ошибка копирования "'. $file->getPath() .'"</b>', false);
	}
}
$logger->log('Создано '. $count .' копий файла "'. $file->getPath() .'"');

$installed = $modx->getCount('modSnippet', array('name' => 'testClearSnippet'));
if (!$installed) {
	$clearSnippet = $modx->newObject('modSnippet', array(
		'name'		=> 'testClearSnippet',
		'snippet'	=> ''
	));
	$clearSnippet->save();
	$logger->log('Создали пустой сниппет');
}
// Запускаем пустой сниппет
$i = 1;
while ($i <= $filesCount) {
	$file = $filepath . $filename .'-'. $i++ .'.'. $fileExt;
	$modx->runSnippet('testClearSnippet');
}
$logger->log('Отработал чистый сниппет');

$installed = $modx->getCount('modSnippet', array('name' => 'phpThumbOf'));
if ($installed) {
	// Запускаем phpThumbOf
	$i = 1;
	while ($i <= $filesCount) {
		$file = $filepath . $filename .'-'. $i++ .'.'. $fileExt;
		$modx->runSnippet('phpThumbOf', array(
			'input' 	=> $file
			,'options' 	=> 'w=500&h=500&zc=1&aoe=0&far=0'
		));
	}
	$logger->log('Отработал phpThumbOf');
} else {
	$logger->log('<b>phpThumbOf не установлен</b>');
}

$installed = $modx->getCount('modSnippet', array('name' => 'ptoaric'));
if ($installed) {
	// Запускаем ptoaric
	$i = 1;
	while ($i <= $filesCount) {
		$file = $filepath . $filename .'-'. $i++ .'.'. $fileExt;
		$modx->runSnippet('ptoaric', array(
			'input' 	=> $file
			,'options' 	=> 'w=500&h=500&zc=1&aoe=0&far=0'
			,'config'	=> '{"path": "/assets/components/phpthumbof/cache_ptoaric/", "salt_type":"md5", "path_at_root": "1", "salt_in": "filename", "salt_separator":"."}'
		));
	}
	$logger->log('Отработал ptoaric');
} else {
	$logger->log('<b>ptoaric не установлен</b>');
}

$logger->saveLog();