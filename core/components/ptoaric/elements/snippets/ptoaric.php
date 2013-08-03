<?php
if (!isset($input)) return '';
if (!isset($options)) return $input;

if (isset($config)) {
	$config = (!is_array($config))
				? $modx->fromJSON($config)
				: $config;
} else {
	$config = array();
}

if (!$modx->loadClass('ptoaric', $modx->getOption('core_path').'components/ptoaric/model/ptoaric/',true,true)) {
	$modx->log(modX::LOG_LEVEL_ERROR,'[ptoaric] Snippet: Could not load PTOARIC class.');
	return;
}
$ptoaric = new ptoaric($modx, 'snippet', $input, $config, $options);
if (!($ptoaric instanceof ptoaric)) return;

// Сперва получим все составляющие входящего файла
$filename	= $ptoaric->getFilename();
$fileUrl 	= $ptoaric->getFileUrl();
$filePath 	= $ptoaric->getFilePath();
$file 		= $ptoaric->fileHandler->make($filePath.$filename, array(), 'modFile');
// Если такой файл существует и он имеет разрешённый формат (jpeg/jpg/png/etc.. из настроек modx)
if ($file->exists() && $file->isImage()) {
	// Получаем все составляющие нового файла
	$newFilename = $ptoaric->getNewFilename();
	$newFileUrl	 = $ptoaric->getNewFileUrl();
	$newFilePath = $ptoaric->getNewFilePath();
	$newFile 	 = $ptoaric->fileHandler->make($newFilePath.$newFilename, array(), 'modFile');
	// Если новый файл ещё не существует
	if (!$newFile->exists()) {
		$FHnewFilePath = $ptoaric->fileHandler->make($newFilePath, array(), 'modDirectory');
		if(
			// если нужной директории не существует,
			!$FHnewFilePath->exists() &&
			// пробуем её создать и, если не получилось,
			!$FHnewFilePath->create()
		) {
			$modx->log(modX::LOG_LEVEL_ERROR,'[ptoaric] Snippet: Could not create folder "'. $newFileUrl .'".');
			// возвращаем исходник
			return $input;
		}
		// генерируем изображение.
		// и, если удачно,
		if ($ptoaric->render($file->getPath(), $newFile->getPath())) {
			// возвращаем годную uri-строку
			return $ptoaric->uriFormat($newFileUrl.$newFilename);
		}
		// иначе возвращаем исходник
		else {
			return $input;
		}
	}
	// новый файл существует - возвращаем годную uri-строку
	else {
		return $ptoaric->uriFormat($newFileUrl.$newFilename);
	}
}
// исходный файл не существует - возвращаем исходник
else {
	$modx->log(modX::LOG_LEVEL_ERROR,'[ptoaric] Snippet: Input file not exists: "'. $input .'".');
	return $input;
}
