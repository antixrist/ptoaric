<?php
if ($modx->event->name != 'OnFileManagerUpload') {return;}

/**
 * Глобальная настройка "ptoaric.upload_config" - это JSON-строка массива ниже.
 * Настройки можно задать здесь, в коде плагина.
 * Тогда глобальная настройка будет игнорироваться.
 * 
 * Не удаляйте и не изменяйте массив, закоментированный ниже,
 * чтобы не потерять описание настроек.
 * Создайте новый с именем "config" сразу после закомментированного (или перед ним).
 */

/**	$config = array(
 * 		// директория от корня сайта (слэши в начале и конце значения не имеют)
 * 		'/blablabla/' => array(
 * 			// Настройки для phpThumb.
 * 			// Строка вида "w=150&h=200&q=80" (как для передачи в сниппет)
 * 			'pt_options'			=> "w=150&h=200&q=80",
 * 			// Если (bool) 'subfolders' == true,
 * 			// то плагин будет срабатывать во вложенных директориях.
 * 			// По умолчанию = true.
 * 			// Но можно указать список конкретных папок,
 * 			// формат которых описан ниже в секции "exclude_subfolders"
 * 			'subfolders' 			=> true,
 * 			// исключить срабатывание в следующих вложенных папках
 * 			'exclude_subfolders'	=> array(
 * 				// Элемент массива может быть вида:
 * 				// 	1) "path"
 * 				// 	2) "path" => (int) neededDepth
 * 				// Если параметр $neededDepth установлен, 
 * 				// то папка ищется на этом уровне вложенности.
 * 				// Если нет, то поиск будет идти на любую глубину.
 * 				'/inner/path/',
 * 				// если true, то будет проверять - 
 * 				// является ли эта папка прямым потомком или нет
 * 				'/other/inner/path/' => 3
 * 			),
 * 			'thumbs' => array(
 * 				'thumbs/' => array(
 * 					'pt_options'			=> "w=150&h=200&q=80",
 * 					// ...
 * 					'exclude_subfolders'	=> array(
 * 						'inner/path/',
 * 						'/other/inner/path'
 * 					),
 * 					// конфиг для сниппета (генерация превью остаётся за ним)
 * 					'ptoaric_config' => array(
 * 						'path'		=> '/thumbs/',
 * 						// ...
 * 						'salt_in' 	=> 'filename'
 * 					)
 * 				)	
 * 			)
 * 		)
 * 	);
 */

// параметры загружаемого файла
$file = $modx->event->params['files']['file'];
// смотрим, что при загрузке не возникло ошибок
if ($file['error'] != 0) {
	return;
}
$filename	= $file['name'];
$directory	= $modx->event->params['directory'];
// получаем media source
$ms = $modx->event->params['source'];
if($ms == null){
	return;
}
/**
 * Т.к. ptoaric'у без разницы - 
 * указан полный физический путь или урл к файлу,
 * получим от медиасоурс хоть что-нибудь.
 */
// Если у медиасоурс установлен base_url
if ($ms->getBaseUrl()) {
	$input = $ms->getBaseUrl() .'/'. $directory .'/'. $filename;
} else
// если base_url не задан, получаем base_path
if ($ms->getBasePath()) {
	$input = $ms->getBasePath() .'/'. $directory .'/'. $filename;
} else {
	// если не задано ни того, ни другого, то на выход
	return;
}

$modx->log(modX::LOG_LEVEL_ERROR,'[ptoaric][upload plugin] Input file: '. $input);
return;

// грузим ptoaric-класс
if (!$modx->loadClass('ptoaric', $modx->getOption('core_path').'components/ptoaric/model/ptoaric/',true,true)) {
	$modx->log(modX::LOG_LEVEL_ERROR,'[ptoaric][upload plugin] Could not load PTOARIC class.');
	return;
}
/**
 * Если задан конфиг выше (который закомментирован),
 * то он полностью перекрывает соответствующую системную настройку
 */
$config		= (isset($config)) ? $config : array();
$ptoaric	= new ptoaric($modx, 'upload_plugin', $input, $config);
if (!($ptoaric instanceof ptoaric)) return;

// Сперва получим все составляющие файла
$filename	= $ptoaric->getFilename();
$fileUrl 	= $ptoaric->getFileUrl();
$filePath 	= $ptoaric->getFilePath();
$file 		= $filePath.$filename;

$fileFH = $ptoaric->fileHandler->make($file, array(), 'modFile');
// если файл не существует или он не является разрешённой картинкой 
if (!$fileFH->exists() || !$fileFH->isImage()) {
	// то на выход
	return;
}

// переименуем расширение с "jpeg" на "jpg"
$ext = $fileFH->getExtension();
if ($ext == 'jpeg') {
	$filename	= substr($file, 0, (strlen($file) - 4)) .'jpg';
	$fileFH->rename($filename);
	$file		= $filePath.$fileFH->getBaseName();
}

// получим системные настройки
$sourcefile_pt_options = $modx->getOption('ptoaric.sourcefile_pt_options', null, '');
$thumbsfile_pt_options = $modx->getOption('ptoaric.thumbsfile_pt_options', null, '');

foreach ($ptoaric->config as $folder => $path_options) {
	$path = $ptoaric->fileHandler->sanitizePath($modx->getOption('base_path') .'/'. $folder);
	/**
	 * Если в опциях папки $folder есть строка для phpThumb, то хорошо.
	 * Если такой опции нет, то берётся глобальная настройка для плагина
	 */
	$path_options['pt_options'] = (isset($path_options['pt_options']))
								  	? $path_options['pt_options']
								  	: $sourcefile_pt_options;
	$path_options['pt_options'] = trim($path_options['pt_options']);
	if (
		// если заданы опции для phpThumb,
		// т.е. надо что-то сделать с изображением
		$path_options['pt_options'] &&
		// и в этой директории можно работать
		$ptoaric->uploadPluginAllowWork($filePath, $path, $path_options)
	) {
		// генерируем файл
		$ptoaric->render($file, $file, $path_options['pt_options']);
	}
	// если нужны превьюшки
	if (
		is_array($path_options['thumbs']) &&
		count($path_options['thumbs']) > 0 &&
		$modx->getCount('modSnippet', array('name' => 'ptoaric'))
	) {
		// пробегаемся по ним
		foreach ($path_options['thumbs'] as $thumb_folder => $thumb_options) {
			$path	= $ptoaric->fileHandler->sanitizePath($modx->getOption('base_path') .'/'. $thumb_folder);
			$run	= false;
			$thumb_options['pt_options'] = (isset($thumb_options['pt_options']))
										 		? $thumb_options['pt_options']
										  		: $thumbsfile_pt_options;
			$thumb_options['pt_options'] = trim($thumb_options['pt_options']);
			/**
			 * Логика срабатывания плагина для генерации превьюшек
			 * точно такая же, как и для изменения основного изображения
			 */
			if (
				$thumb_options['pt_options'] &&
				// сниппет установлен
				$modx->getCount('modSnippet', array('name' => 'ptoaric')) &&
				$ptoaric->uploadPluginAllowDirectory($filePath, $path, $thumb_options)
			) {
				/**
				 * За исключением того, что вызывается сниппет
				 * и в него можно передать массив конфигурации именования файлов-превью.
				 */
				$modx->runSnippet('ptoaric', array(
					'input'		=> $file,
					'options'	=> $thumb_options['pt_options'],
					'config'	=> $thumb_options['ptoaric_config']
				));
				break;
			}
		}
	}
}