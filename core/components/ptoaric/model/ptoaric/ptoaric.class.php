<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once('myfilehandler.class.php');

class ptoaric {
	/** @var modX $modx */
	public $modx;

	function __construct(modX $modx, $mode = 'snippet', $image = '', $_config = array(), $ptoptions = array()) {
		$this->modx				= &$modx;
		$this->baseUrl			= $this->modx->getOption('base_url', null, MODX_BASE_URL);
		$this->basePath			= $this->modx->getOption('base_path', null, MODX_BASE_PATH);
		$this->modx->loadClass('modPhpThumb', $this->modx->getOption('core_path').'model/phpthumb/', true, true);
		$this->phpThumb			= new modPhpThumb($this->modx);
		$this->fileHandler		= new myModFileHandler($this->modx);
		$this->modFileImage 	= $this->fileHandler->make($image);

		// die(print_r($this->modFileImage, true));

		switch ($mode) {
			case 'upload_plugin':
				$this->initUploadPlugin($_config);
				break;
			case 'snippet':
			default:
				$this->initSnippet($_config, $ptoptions);
				break;
		}
	}

	private function initSnippet ($_config = array(), $ptoptions) {
		$this->logMarker	= "[ptoaric][snippet] ";
		$this->ptoptions	= $this->parseOptions($ptoptions);
		$this->config		= array(
			/**
			 * path
			 * Имя директории, в которую будут складываться генерированные картинки.
			 * Может быть в виде директории, например "path/name/".
			 * Если начинается со слэша, то считается абсолютным адресом и
			 * должна располагаться в корне сайта.
			 */
			'path' => $this->modx->getOption('ptoaric.path', null, ''),
			
			/**
			 * add_image_folder_if_path_is_absolute
			 * Если папка будет создаваться в корне сайта,
			 * то надо ли в этой папке создавать директорию изображения?
			 * Например, если имя папки установлено в "/thumbs/",
			 * а исходный файл располагается в папке "/images/cool_photos/",
			 * то, в зависимости от данной настройки,
			 * картинка-превью будет лежать в "/thumbs/"
			 * или в "/thumbs/images/cool_photos/".
			 * 	0 - нет
			 * 	1 - да
			 */
			'add_image_folder_if_path_is_absolute' => $this->modx->getOption('ptoaric.add_image_folder_if_path_is_absolute', null, 0),

			/**
			 * salt_type
			 * Тип добавляемой соли:
			 * 	options	- склеенный в строку набор параметров из вызова сниппета
			 *	hash 	- md5 хэш от options
			 */
			'salt_type' => $this->modx->getOption('ptoaric.salt_type', null, 'options'),

			/**
			 * salt_in
			 * Куда добавлять соль:
			 * 	filename 	- к имени генерируемого файла
			 * 	inner_path 	- создавать вложенную в path папку
			 * 	pathname 	- добавлять соль к названию path
			 */
			'salt_in' => $this->modx->getOption('ptoaric.salt_in', null, 'inner_path'),
			
			/**
			 * salt_separator
			 * Каким символом отделять соль в названии файла?
			 * Проверяем этот символ на запрещённый.
			 */
			'salt_separator' => $this->modx->getOption('ptoaric.salt_separator', null, '')
		);
		
		// перезаписываем настройки, если при вызове сниппета они были указаны
		$this->config					= array_merge($this->config, $_config);
		$this->config['path']			= $this->fileHandler->postfixSlashRemove($this->config['path']);
		$this->config['path']			= $this->fileHandler->sanitizePath($this->config['path'], true);
		$this->config['salt_separator']	= $this->fileHandler->sanitizePath($this->config['salt_separator']);
		$this->salt						= $this->getSalt();

		if (substr($this->config['path'], 0, 1) == '/') {
			$this->config['path_at_root'] 	= true;
			$this->config['path']			= $this->fileHandler->prefixSlashRemove($this->config['path']);
		} else {
			$this->config['path_at_root'] = false;
		}
	}
	
	private function initUploadPlugin ($_config = array()) {
		$this->logMarker	= "[ptoaric][upload plugin] ";
		$system_config		= $this->modx->getOption('ptoaric.folders_settings', null, '');
		$system_config		= $this->modx->fromJSON($system_config);
		// $this->config	= $this->array_merge_recursive($system_config, $_config);
		$this->config		= (is_array($_config) && count($_config) > 0)
							? $_config
							: $system_config;
	}

	/**
	 * Соберём соль из параметров и отдадим её, либо md5 от неё
	 */
	private function getSalt () {
		$salt = '';
		foreach ($this->ptoptions as $k => $v) {
			/**
			 * Если в параметрах указано, что соль должна быть в имени файла,
			 * то этот параметр в строку соли можно не добавлять.
			 * Ну и если параметр "качество" равен "100",
			 * то его тоже можно опустить (это последняя строка в условии,
			 * можно закомментировать при желании).
			 */
			if (
				($k == 'f' &&
					$this->config['salt_in'] 	== 'filename' &&
					$this->config['salt_type'] 	== 'options'
				)
				|| ($k == "q" && $v == 100)
			) {
				continue;
			}
			$salt .= $k.$v;
		}
		switch ($this->config['salt_type']) {
			case 'md5':
				return md5($salt);
				// break;
			case 'options':
			default:
				return $salt;
				// break;
		}
	}

	function getFilename () {
		$this->filename = $this->modFileImage->getBaseName();
		return $this->filename;
	}

	/**
	 * Обрабатываем урл нашего файла.
	 * Обратите внимание, что сюда может прийти полный _физический_ путь
	 * и он корректно обработается.
	 */
	function getFileUrl () {
		$fileUrl = $this->modFileImage->getPath();
		$fileUrl = $this->fileHandler->getDirectoryFromFile($fileUrl);
		// если в $input из сниппета пришла строка вида - "filename.ext", т.е. без слэшей и папок
		if ($fileUrl == './') {
			$fileUrl = '';
		}
		$inputUrlType = '';
		// если адрес относительный
		if (!$this->fileHandler->isAbsoluteUrl($fileUrl)) {
			// значит пришёл адрес вида "images/picture.jpg"
			$inputUrlType = 'relative';
		}
		// если абсолютный, но не существующий и не содержащий MODX_BASE_PATH в начале строки
		else if (!file_exists($fileUrl) && strpos($fileUrl, $this->basePath) !== 0) {
			// значит пришёл адрес вида "/images/picture.jpg"
			$inputUrlType = 'absolute';
		} else {
			// иначе пришёл полный физический путь.
			// так сделаем же из него абсолютный урл!
			$fileUrl		= '/'. substr($fileUrl, strlen($this->basePath));
			$inputUrlType	= 'absolute';
		}
		$fileUrl = $this->fileHandler->postfixSlash($fileUrl);
		$fileUrl = $this->fileHandler->sanitizePath($fileUrl);

		$this->inputUrlType	= $inputUrlType;
		$this->fileUrl		= $fileUrl;
		return $this->fileUrl;
	}

	/**
	 * Здесь мы собираем полный физический путь до файла.
	 */
	function getFilePath () {
		switch ($this->inputUrlType) {
			// если файл пришёл в виде относительного урл
			case 'relative':
				$filePath = $this->basePath .'/'. $this->baseUrl .'/'. $this->fileUrl;
				break;
			// если файл пришёл в виде абсолютного урла
			case 'absolute':
				$filePath = $this->basePath .'/'. $this->fileUrl;
				break;
		}
		$this->filePath	= $this->fileHandler->sanitizePath($filePath);
		return $this->filePath;
	}

	function getNewFilename () {
		$ptoptionsExt	= $this->ptoptions['f'];
		$filename		= $this->filename;
		$fileExt		= $this->modFileImage->getExtension();
		$filenameLength	= strlen($filename);
		$extLength		= strlen($fileExt);

		$cut = $filenameLength - $extLength - 1;
		if (strlen($filename) > $cut) {
			$filename = substr($filename, 0, $cut);
		}
		$salt = ($this->config['salt_in'] == 'filename')
					? $this->config['salt_separator'].$this->salt
					: '';
		$this->newFilename = $filename.$salt .".". $ptoptionsExt;
		return $this->newFilename;
	}

	function getNewFileUrl () {
		$salt		= $this->salt;
		$fileUrl	= $this->fileUrl;
		$path 		= $this->config['path'];
		/**
		 * Если соль необходимо добавлять к path, но path не указана,
		 * то добавляем соль, создавая внутреннюю папку. Ничего не поделать.
		 */
		if ($path == '' && $this->config['salt_in'] == 'pathname') {
			$this->config['salt_in'] = 'inner_path';
		}
		/**
		 * Определим, какой разделитель для соли использовать:
		 * 	1. $this->salt_separator - если соль должна быть в pathname,
		 * 	2. '/' - если соль должна быть папкой (inner_path).
		 * Таким образом соль автоматически будет:
		 * 	1. либо подставляться к имени папки,
		 * 	2. либо создаваться как вложенная папка
		 */
		switch ($this->config['salt_in']) {
			case 'pathname':
				$salt = $this->config['salt_separator'] . $salt;
				break;
			case 'filename':
				$salt = '';
				break;
			case 'inner_path':
			default:
				$salt = '/' . $salt;
				break;
		}
		// если надо папку разместить в корне
		if ($this->config['path_at_root']) {
			// а если в этой папке ещё и надо создавать папку изображения
			if ($this->config['add_image_folder_if_path_at_root_enabled']) {
				switch ($this->inputUrlType) {
					// если файл пришёл в виде относительного урл
					case 'relative':
						$newFileUrl = '/'. $path .'/'. $this->baseUrl .'/'. $fileUrl;
						break;
					// если файл пришёл в виде абсолютного урла
					case 'absolute':
						$newFileUrl = '/'. $path .'/'. $fileUrl;
						break;
				}
			} else {
				$newFileUrl = '/'. $path;
			}
		} else {
			// если нет, то создаём урл, исходя из папки изображения.
			switch ($this->inputUrlType) {
				// если файл пришёл в виде относительного урл
				case 'relative':
					$newFileUrl = ($fileUrl != '') ? $fileUrl .'/'. $path : $path;
					break;
				// если файл пришёл в виде абсолютного урла
				case 'absolute':
					$newFileUrl = '/'. $fileUrl .'/'. $path;
					break;
			}
		}
		$newFileUrl = $this->fileHandler->sanitizePath($newFileUrl);
		// и добавляем соль
		$newFileUrl = $this->fileHandler->postfixSlashRemove($newFileUrl) . $salt;
		$newFileUrl = $this->fileHandler->postfixSlash($newFileUrl);
		
		$this->newFileUrl = $newFileUrl;
		return $this->newFileUrl;
	}

	function getNewFilePath () {
		// если урл будущего файла получился абсолютным
		if ($this->fileHandler->isAbsoluteUrl($this->newFileUrl)) {
			$newFilePath = $this->basePath .'/'. $this->newFileUrl;
		}
		// если урл будущего файла получился относительным
		else {
			$newFilePath = $this->basePath .'/'. $this->baseUrl .'/'. $this->newFileUrl;
		}
		$this->newFilePath = $this->fileHandler->sanitizePath($newFilePath);
		return $this->newFilePath;
	}

	function uploadPluginAllowDirectory ($filePath, $path, $options) {
		$result = false;
		// если это нужная папка (точное совпадение)
		if ($filePath == $path) {
			// то будем работать
			$result = true;
		} else {
			/**
			 * Установим значение по умолчанию для подпапок.
			 * Если этот параметр не указан, то оставляем true.
			 */
			if (!isset($options['subfolders'])) {
				$options['subfolders'] = true;
			}
			$subfolders = $options['subfolders'];
			/** 
			 * Если subfolders является массивом,
			 * значит пришёл список из дочерних папок,
			 * в которых необходимо срабатывать ("в них и только в них").
			 */
			if (is_array($subfolders) && count($subfolders) > 0) {
				// пробегаемся по массиву подпапок
				foreach ($subfolders as $key => $value) {
					/**
					 * Здесь отступление.
					 * Элемент массива может быть вида:
					 * 	1) "path"
					 * 	2) "path" => (int) neededDepth
					 * И в зависимости от варианта написания,
					 * в $key и $value будут храниться разные значения:
					 * 	1) [0] => string(4) "path"
					 * 	2) ["path"] => int(1)
					 * Вот дальше как раз такие случаи и разбираются.
					 * Если параметр $neededDepth не установлен,
					 * то поиск будет идти на любую глубину.
					 */
					// если $key - это строка, значит у нас второй вариант
					if (is_string($key)) {
						// запоминаем имя папки
						$subfolder	= $key;
						/**
						 * Определяем глубину поиска.
						 * Если глубина задана неверно, или == 0,
						 * то папка будет искаться на любом уровне вложенности
						 */
						$neededDepth = ((bool)($value) === false)
										? false
										: (int) $value;
					} else
					// а если $key - число, значит первый вариант
					if (is_numeric($key)) {
						// запоминаем имя папки
						$subfolder		= $value;
						// ищем на любом уровне вложенности
						$neededDepth	= false;
					}
					// узнаём фактическую глубину дочерней папки.
					// если дочерней не является - вернётся false
					$actualDepth = $this->fileHandler->folderContainsFolder($path, $subfolder);
					if (
					 	// если глубина НЕ установлена и эта подпапка является дочерней
						(!$neededDepth &&  $actualDepth) ||
						// или глубина установлена и равна заданной
						/**
						 * Здесь нельзя сравнивать напрямую $actualDepth == $neededDepth,
						 * потому что $actualDepth м.б. равным false
						 * и $neededDepth м.б. равным false одновременно.
						 * Их сравнение напрямую даст положительный эффект,
						 * хотя значения этих параметров явно будут говорить об обратном.
						 */
						( $neededDepth && ($actualDepth == $neededDepth))
					) {
						$result = true;
						break;
					}
				}
			} else
			// а если если subfolders в виде булевой истины,
			// то включаем для всех подпапок
			if ((bool) $subfolders) {
				$result = true;
			}
			// если указаны папки, в которых срабатывать нельзя
			if (is_array($options['exclude_subfolders']) && count($options['exclude_subfolders']) > 0) {
				// пробегаемся по массиву подпапок
				foreach ($options['exclude_subfolders'] as $key => $value) {
					/**
					 * Здесь логика такая же, как и выше
					 */
					if (is_string($key)) {
						$exclude_subfolder	= $key;
						$neededDepth		= ((bool)($value) === false)
												? false
												: (int) $value;
					} else if (is_numeric($key)) {
						$exclude_subfolder	= $value;
						$neededDepth		= false;
					}
					$actualDepth = $this->fileHandler->folderContainsFolder($path, $exclude_subfolder);
					if (
						(!$neededDepth &&  $actualDepth) ||
						( $neededDepth && ($actualDepth == $neededDepth))
					) {
						// только здесь надо выключать
						$result = false;
						break;
					}
				}
			}
		}
		return $result;
	}


	function uriFormat ($str) {
		return str_replace(' ', '%20', $str);
	}

	function array_merge_recursive ($dest, $new) {
		if (!is_array($dest) &&  is_array($new)) return $new;
		if ( is_array($dest) && !is_array($new)) return $dest;
		if (!is_array($dest) && !is_array($new)) return array();
		
		foreach ($new as $k => $v) {
			if (is_array($v) && isset($dest[$k]) && !is_numeric($k)) {
				$dest[$k] = $this->array_merge_recursive($dest[$k], $v);
			} else if (!is_numeric($k)) {
				$dest[$k] = $new[$k];
			} else {
				$dest[] = $new[$k];
			}
		}
		return $dest;
	}

	private function parseOptions($options) {
		$ptoptions = array();
		$eoptions = is_array($options) ? $options : explode('&',$options);
		foreach ($eoptions as $opt) {
			$opt = explode('=',$opt);
			$key = str_replace('[]','',$opt[0]);
			if (!empty($key)) {
				if (isset($ptoptions[$key])) {
					if (is_string($ptoptions[$key])) {
						$ptoptions[$key] = array($ptoptions[$key]);
					}
					$ptoptions[$key][] = $opt[1];
				} else {
					$ptoptions[$key] = $opt[1];
				}
			}
		}

		if (empty($ptoptions['f'])){
			$ext = $this->modFileImage->getExtension();
			switch ($ext) {
				case 'png':
				case 'gif':
				case 'bmp':
					$ptoptions['f'] = $ext;
					break;
				case 'jpeg':
				case 'jpg':
				default:
					$ptoptions['q'] = (isset($ptoptions['q'])) ? $ptoptions['q'] : 100;
					$ptoptions['f'] = 'jpg';
					break;
			}
		}
		ksort($ptoptions);
		return $ptoptions;
	}

	function render ($image, $newImage, $ptoptions = '') {
		if (!empty($ptoptions)) {
			if (is_array($ptoptions)) {
				$this->ptoptions = $ptoptions;
			} else {
				$this->ptoptions = $this->parseOptions($ptoptions);
			}
		}
		$this->phpThumb->setSourceFilename($image);
		// $this->phpThumb->set($image);
		foreach ($this->ptoptions as $k => $v) {
			$this->phpThumb->setParameter($k, $v);
		}
		if ($this->phpThumb->GenerateThumbnail()) {
			if ($this->phpThumb->RenderToFile($newImage)) {
				return true;
			} else {
				$this->modx->log(modX::LOG_LEVEL_ERROR, $this->logMarker .'Could not write thumbnail file "'.$newImage.'" of "'.$image.'". Debug: '.print_r($this->phpThumb->debugmessages,true));
			}
		} else {
			$this->modx->log(modX::LOG_LEVEL_ERROR, $this->logMarker .'Could not generate thubmnail for "'.$image.'". '.print_r($this->phpThumb->debugmessages,true));
		}
		return false;
	}
}
