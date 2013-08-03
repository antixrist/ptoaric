<?php

$corePath = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
require_once($corePath.'/model/modx/modfilehandler.class.php');

class myModFileHandler extends modFileHandler {

	function __construct(modX &$modx, array $config = array()) {
		parent::__construct($modx, $config);
		$this->modx = $modx;
		$this->forbidden_character = array(
			'allow_slash' => explode(' ', '\\ : * ? " < > |')
		);
		$this->forbidden_character['disallow_slash'] 	= $this->forbidden_character['allow_slash'];
		$this->forbidden_character['disallow_slash'][] 	= '/';
	}

	public function make($path, array $options = array(), $overrideClass = '') {
		switch ($overrideClass) {
			case 'modFile':
				$overrideClass = 'myModFile';
				break;
			case 'modDirectory':
				$overrideClass = 'myModDirectory';
				break;
		}
		$class = 'myModFile';
		if (!empty($overrideClass)) {
			$class = $overrideClass;
		} else {
			if (is_dir($path)) {
				$path = $this->postfixSlash($path);
				$class = 'myModDirectory';
			} else {
				$class = 'myModFile';
			}
		}
		$path = $this->sanitizePath($path);
		return new $class($this->modx, $this, $path, $options);
	}

	public function sanitizePath ($path, $allow_slash = false){
		$this->clearForbittenCharacter($path, $allow_slash);
		return parent::sanitizePath($path);
	}

	public function clearForbittenCharacter ($path, $allow_slash = false) {
		$disallow_characters = ($allow_slash) 
								? $this->forbidden_character['allow_slash']
								: $this->forbidden_character['disallow_slash'];
		return str_replace($disallow_characters, "", $path);
	}
	
	public function isAbsoluteUrl ($path = '') {
		if (!empty($path)){
			$path = $this->sanitizePath($path);
		} else {
			$path = $this->path;
		}
		return (substr($path, 0, 1) == '/');
	}

	public function prefixSlashRemove ($path = '') {
		if (!empty($path)){
			$path = $this->sanitizePath($path);
		} else {
			$path = &$this->path;
		}
		$len = strlen($path);
		if (substr($path, 0, 1) == '/') {
			$path = substr($path, 1, $len);
		}
		return $path;
	}

	public function postfixSlashRemove ($path = '') {
		if (!empty($path)){
			$path = $this->sanitizePath($path);
		} else {
			$path = &$this->path;
		}
		$len = strlen($path);
		if (substr($path, $len - 1, $len) == '/') {
			$path = substr($path, 0, $len - 1);
		}
		return $path;
	}

	public function prepostfixSlashRemove ($path) {
		if (!empty($path)){
			$path = $this->prefixSlashRemove ($path);
			$path = $this->postfixSlashRemove($path);
			return $path;
		} else {
			$this->prefixSlashRemove ();
			$this->postfixSlashRemove();
			return $this->path;
		}
	}

	/**
	 * Определяет составная ли это папка,
	 * т.е. "this/is/inner/path" или просто "this_is_inner_path"
	 */
	function isCompositePath ($path) {
		$path = $this->prepostfixSlashRemove($path);
		return (bool) strpos($path, '/');
	}

	/**
	 * Ищет вложенную папку внутри другой папки.
	 * Возвращает массив из полного пути первой найденной папки
	 * и уровень вложенности
	 */
	function findChildFolder ($folder, $folderInner, $depth = 0) {
		// увеличиваем значение уровня вложенности
		$depth++;
		// приводим входные данные к нужному виду
		$folder = $this->postfixSlash($this->sanitizePath($folder));
		$folderInner = $this->prepostfixSlashRemove($this->sanitizePath($folderInner));
		
		$contains = false;
		// читаем содержимое папки
		if ($handle = opendir($folder)) {
			while (false !== ($_folder = readdir($handle))) {
				if ($_folder == "." || $_folder == "..") continue;
				// если внутренний файл является папкой
				if (is_dir($handleFolder = $folder.$_folder)) {
					// если имя внутренней папки совпадает с требуемым
					if ($_folder == $folderInner) {
						// ставим флаг
						$contains = true;
						break;
					} else
					// имя внутренней папки НЕ совпадает с требуемым
					if ($_folder != $folderInner) {
						// рекурсивно проходимся вглубь каждой папки
						return $this->findChildFolder($handleFolder, $folderInner, $depth);
					}
				}
			}
			closedir($handle);
		}
		// если нужная нам папка содержится в исходной,
		// то возвращаем глубину вложенности и полный путь найденной папки
		return ($contains) ? array('path' => $this->postfixSlash($handleFolder), 'depth' => $depth) : false;
	}

	/**
	 * Ищет папку $folderInner внутри папки $folder.
	 * Искать можно так же вложенную структуру папок, т.е.
	 * $folderInner может быть "/this/is/inner/path/"
	 * 
	 * Возвращает уровень вложенности,
	 * либо false в случае, если таковой нету.
	 */
	function folderContainsFolder ($folder, $folderInner) {
		$folder			= $this->sanitizePath($folder);
		$folderInner	= $this->sanitizePath($folderInner);
		/**
		 * Если начало обоих путей совпадают,
		 * то да, папка вложена и уровень вложенности - 1.
		 * например:
		 * 		$folder 	 = '/path/to/site/domain.tld/images/';
		 * 		$folderInner = '/path/to/site/domain.tld/images/thumbnails/w100h100/';
		 */
		if (strpos($folder, $folderInner) === 0) {
			return $depth = 1;
		}
		$folderInner = $this->prepostfixSlashRemove($folderInner);
		$depth = false;
		// если вложенная папка - это просто имя,
		// т.е. "innerpath"
		if (!$this->isCompositePath($folderInner)) {
			$result	= $this->findChildFolder($folder, $folderInner);
			$depth	= $result['depth'];
		}
		// если вложенная папка "составная",
		// т.е. вида "inner/path"
		else {
			/**
			 * Допустим $folderInner имеет вид: "this/is/inner/path".
			 * Тогда разбиваем эту строку на части, получая массив: 
			 * 		array('this', 'is', 'inner', 'path')
			 */
			$pathInnerStructure = explode('/', $folderInner);
			/**
			 * Затем проходимся по всем элементам массива.
			 * На каждой итерации массива ищем вложенную папку.
			 * Из примера выше, при первом проходе мы ищем папку 'this' внутри $folder.
			 * Если нашли, то запоминаем глубину первой вложенной папки
			 * из структуры папкок $folderInner (папки 'this' из примера).
			 * Остальные итерации цикла нужны для того,
			 * чтобы убедиться в существовании остальной структуры $folderInner
			 */
			foreach ($pathInnerStructure as $folderInner) {
				// находим папку
				$result	= $this->findChildFolder($folder, $folderInner);
				if ($result) {
					/**
					 * если такая вложенная папка существует,
					 * то при каждой итерации ищем всё глубже и глубже..
					 */
					$folder = $result['path'];
					// запоминаем глубину при первой итерации
					$depth = (!$depth) ? $result['depth'] : $depth;
				}
				// Если такой вложенной папки не существует, обрываем цикл.
				else {
					$depth = false;
					break;
				}
			}
		}
		return $depth;
	}

}

class myModFile extends modFile {

	function __construct (modX &$modx, myModFileHandler &$fh, $path, array $options = array()) {
		$this->image_extentions	= explode(',', $modx->getOption('upload_images'));
		parent::__construct($fh, $path, $options = array());
	}

	/**
	 * Получает расширение файла на основании MIME-типа.
	 * Если получить MIME-тип не удалось (например файл не существует),
	 * то выполняем родительский метод, получая расширение из PATH_INFO.
	 */
	// public function getExtension() {
	// 	$sizes	= getimagesize($this->path);
	// 	if ($sizes) {
	// 		list($type, $ext) = explode('/', $sizes['mime']);
	// 		return $ext;
	// 	} else {
	// 		return strtolower(parent::getExtension());
	// 	}
	// }

	/**
	 * Проверяет - является ли этот файл изображением.
	 * Список допустимых расширений берётся из системной настройки
	 */
	public function isImage () {
		$ext = $this->getExtension();
		// $ext = strtolower($this->modFileImage->getExtension());
		return in_array($ext, $this->image_extentions);
	}

}

class myModDirectory extends modDirectory {
	function __construct (modX &$modx, myModFileHandler &$fh, $path, array $options = array()) {
		parent::__construct($fh, $path, $options = array());
	}
}









// $mime = array(
// 	'application' => array(
// 		'andrew-inset' => array('ez'),
// 		'annodex' => array('anx'),
// 		'atom+xml' => array('atom'),
// 		'atomcat+xml' => array('atomcat'),
// 		'atomserv+xml' => array('atomsrv'),
// 		'bbolin' => array('lin'),
// 		'cap' => array(
// 			'cap',
// 			'pcap'
// 		),
// 		'cu-seeme' => array('cu'),
// 		'davmount+xml' => array('davmount'),
// 		'dsptype' => array('tsp'),
// 		'ecmascript' => array('es'),
// 		'hta' => array('hta'),
// 		'java-archive' => array('jar'),
// 		'java-serialized-object' => array('ser'),
// 		'java-vm' => array('class'),
// 		'javascript' => array('js'),
// 		'm3g' => array('m3g'),
// 		'mac-binhex40' => array('hqx'),
// 		'mathematica' => array(
// 			'nb',
// 			'nbp'
// 		),
// 		'msaccess' => array('mdb'),
// 		'msword' => array(
// 			'doc',
// 			'dot',
// 			'wiz'
// 		),
// 		'octet-stream' => array(
// 			'a',
// 			'bin',
// 			'obj',
// 			'so'
// 		),
// 		'oda' => array('oda'),
// 		'ogg' => array('ogx'),
// 		'pdf' => array('pdf'),
// 		'pgp-keys' => array('key'),
// 		'pgp-signature' => array('pgp'),
// 		'pics-rules' => array('prf'),
// 		'pkcs7-mime' => array('p7c'),
// 		'postscript' => array(
// 			'ai',
// 			'eps',
// 			'eps2',
// 			'eps3',
// 			'epsf',
// 			'espi',
// 			'ps'
// 		),
// 		'rar' => array('rar'),
// 		'rdf+xml' => array('rdf'),
// 		'rss+xml' => array('rss'),
// 		'rtf' => array('rtf'),
// 		'smil' => array(
// 			'smi',
// 			'smil'
// 		),
// 		'vnd.android.package-archive' => array('apk'),
// 		'vnd.cinderella' => array('cdy'),
// 		'vnd.google-earth.kml+xml' => array('kml'),
// 		'vnd.google-earth.kmz' => array('kmz'),
// 		'vnd.mozilla.xul+xml' => array('xul'),
// 		'vnd.ms-excel' => array(
// 			'xlb',
// 			'xls',
// 			'xlt'
// 		),
// 		'vnd.ms-pki.seccat' => array('cat'),
// 		'vnd.ms-pki.stl' => array('stl'),
// 		'vnd.ms-powerpoint' => array(
// 			'ppa',
// 			'pps',
// 			'ppt',
// 			'pwz'
// 		),
// 		'vnd.oasis.opendocument.chart' => array('odc'),
// 		'vnd.oasis.opendocument.database' => array('odb'),
// 		'vnd.oasis.opendocument.formula' => array('odf'),
// 		'vnd.oasis.opendocument.graphics' => array('odg'),
// 		'vnd.oasis.opendocument.graphics-template' => array('otg'),
// 		'vnd.oasis.opendocument.image' => array('odi'),
// 		'vnd.oasis.opendocument.presentation' => array('odp'),
// 		'vnd.oasis.opendocument.presentation-template' => array('otp'),
// 		'vnd.oasis.opendocument.spreadsheet' => array('ods'),
// 		'vnd.oasis.opendocument.spreadsheet-template' => array('ots'),
// 		'vnd.oasis.opendocument.text' => array('odt'),
// 		'vnd.oasis.opendocument.text-master' => array('odm'),
// 		'vnd.oasis.opendocument.text-template' => array('ott'),
// 		'vnd.oasis.opendocument.text-web' => array('oth'),
// 		'vnd.openxmlformats-officedocument.presentationml.presentation' => array('pptx'),
// 		'vnd.openxmlformats-officedocument.presentationml.slideshow' => array('ppsx'),
// 		'vnd.openxmlformats-officedocument.presentationml.template' => array('potx'),
// 		'vnd.openxmlformats-officedocument.spreadsheetml.sheet' => array('xlsx'),
// 		'vnd.openxmlformats-officedocument.spreadsheetml.template' => array('xltx'),
// 		'vnd.openxmlformats-officedocument.wordprocessingml.document' => array('docx'),
// 		'vnd.openxmlformats-officedocument.wordprocessingml.template' => array('dotx'),
// 		'vnd.rim.cod' => array('cod'),
// 		'vnd.smaf' => array('mmf'),
// 		'vnd.stardivision.calc' => array('sdc'),
// 		'vnd.stardivision.chart' => array('sds'),
// 		'vnd.stardivision.draw' => array('sda'),
// 		'vnd.stardivision.impress' => array('sdd'),
// 		'vnd.stardivision.writer' => array('sdw'),
// 		'vnd.stardivision.writer-global' => array('sgl'),
// 		'vnd.sun.xml.calc' => array('sxc'),
// 		'vnd.sun.xml.calc.template' => array('stc'),
// 		'vnd.sun.xml.draw' => array('sxd'),
// 		'vnd.sun.xml.draw.template' => array('std'),
// 		'vnd.sun.xml.impress' => array('sxi'),
// 		'vnd.sun.xml.impress.template' => array('sti'),
// 		'vnd.sun.xml.math' => array('sxm'),
// 		'vnd.sun.xml.writer' => array('sxw'),
// 		'vnd.sun.xml.writer.global' => array('sxg'),
// 		'vnd.sun.xml.writer.template' => array('stw'),
// 		'vnd.symbian.install' => array('sis'),
// 		'vnd.visio' => array('vsd'),
// 		'vnd.wap.wbxml' => array('wbxml'),
// 		'vnd.wap.wmlc' => array('wmlc'),
// 		'vnd.wap.wmlscriptc' => array('wmlsc'),
// 		'vnd.wordperfect' => array('wpd'),
// 		'vnd.wordperfect5.1' => array('wp5'),
// 		'x-123' => array('wk'),
// 		'x-7z-compressed' => array('7z'),
// 		'x-abiword' => array('abw'),
// 		'x-apple-diskimage' => array('dmg'),
// 		'x-bcpio' => array('bcpio'),
// 		'x-bittorrent' => array('torrent'),
// 		'x-cab' => array('cab'),
// 		'x-cbr' => array('cbr'),
// 		'x-cbz' => array('cbz'),
// 		'x-cdf' => array(
// 			'cda',
// 			'cdf'
// 		),
// 		'x-cdlink' => array('vcd'),
// 		'x-chess-pgn' => array('pgn'),
// 		'x-cpio' => array('cpio'),
// 		'x-debian-package' => array(
// 			'deb',
// 			'udeb'
// 		),
// 		'x-director' => array(
// 			'dcr',
// 			'dir',
// 			'dxr'
// 		),
// 		'x-dms' => array('dms'),
// 		'x-doom' => array('wad'),
// 		'x-dvi' => array('dvi'),
// 		'x-font' => array(
// 			'gsf',
// 			'pcf',
// 			'pcf.Z',
// 			'pfa',
// 			'pfb'
// 		),
// 		'x-freemind' => array('mm'),
// 		'x-futuresplash' => array('spl'),
// 		'x-gnumeric' => array('gnumeric'),
// 		'x-go-sgf' => array('sgf'),
// 		'x-graphing-calculator' => array('gcf'),
// 		'x-gtar' => array(
// 			'gtar',
// 			'taz',
// 			'tgz'
// 		),
// 		'x-hdf' => array('hdf'),
// 		'x-httpd-eruby' => array('rhtml'),
// 		'x-httpd-php' => array(
// 			'php',
// 			'pht',
// 			'phtml'
// 		),
// 		'x-httpd-php-source' => array('phps'),
// 		'x-httpd-php3' => array('php3'),
// 		'x-httpd-php3-preprocessed' => array('php3p'),
// 		'x-httpd-php4' => array('php4'),
// 		'x-ica' => array('ica'),
// 		'x-info' => array('info'),
// 		'x-internet-signup' => array(
// 			'ins',
// 			'isp'
// 		),
// 		'x-iphone' => array('iii'),
// 		'x-iso9660-image' => array('iso'),
// 		'x-jam' => array('jam'),
// 		'x-java-jnlp-file' => array('jnlp'),
// 		'x-jmol' => array('jmz'),
// 		'x-kchart' => array('chrt'),
// 		'x-killustrator' => array('kil'),
// 		'x-koan' => array(
// 			'skd',
// 			'skm',
// 			'skp',
// 			'skt'
// 		),
// 		'x-kpresenter' => array(
// 			'kpr',
// 			'kpt'
// 		),
// 		'x-kspread' => array('ksp'),
// 		'x-kword' => array(
// 			'kwd',
// 			'kwt'
// 		),
// 		'x-latex' => array('latex'),
// 		'x-lha' => array('lha'),
// 		'x-lyx' => array('lyx'),
// 		'x-lzh' => array('lzh'),
// 		'x-lzx' => array('lzx'),
// 		'x-maker' => array(
// 			'book',
// 			'fb',
// 			'fbdoc',
// 			'fm',
// 			'frame',
// 			'frm',
// 			'maker'
// 		),
// 		'x-mif' => array('mif'),
// 		'x-ms-wmd' => array('wmd'),
// 		'x-ms-wmz' => array('wmz'),
// 		'x-msdos-program' => array(
// 			'bat',
// 			'com',
// 			'dll',
// 			'exe'
// 		),
// 		'x-msi' => array('msi'),
// 		'x-netcdf' => array('nc'),
// 		'x-ns-proxy-autoconfig' => array(
// 			'dat',
// 			'pac'
// 		),
// 		'x-nwc' => array('nwc'),
// 		'x-object' => array('o'),
// 		'x-oz-application' => array('oza'),
// 		'x-pkcs12' => array(
// 			'p12',
// 			'pfx'
// 		),
// 		'x-pkcs7-certreqresp' => array('p7r'),
// 		'x-pkcs7-crl' => array('crl'),
// 		'x-python-code' => array(
// 			'pyc',
// 			'pyo'
// 		),
// 		'x-qgis' => array(
// 			'qgs',
// 			'shp',
// 			'shx'
// 		),
// 		'x-quicktimeplayer' => array('qtl'),
// 		'x-redhat-package-manager' => array('rpm'),
// 		'x-ruby' => array('rb'),
// 		'x-shar' => array('shar'),
// 		'x-shockwave-flash' => array(
// 			'swf',
// 			'swfl'
// 		),
// 		'x-silverlight' => array('scr'),
// 		'x-stuffit' => array(
// 			'sit',
// 			'sitx'
// 		),
// 		'x-sv4cpio' => array('sv4cpio'),
// 		'x-sv4crc' => array('sv4crc'),
// 		'x-tar' => array('tar'),
// 		'x-tex-gf' => array('gf'),
// 		'x-tex-pk' => array('pk'),
// 		'x-texinfo' => array(
// 			'texi',
// 			'texinfo'
// 		),
// 		'x-trash' => array(
// 			'%',
// 			'bak',
// 			'old',
// 			'sik',
// 			'~'
// 		),
// 		'x-troff' => array(
// 			'roff',
// 			't',
// 			'tr'
// 		),
// 		'x-troff-man' => array('man'),
// 		'x-troff-me' => array('me'),
// 		'x-troff-ms' => array('ms'),
// 		'x-ustar' => array('ustar'),
// 		'x-wais-source' => array('src'),
// 		'x-wingz' => array('wz'),
// 		'x-x509-ca-cert' => array('crt'),
// 		'x-xcf' => array('xcf'),
// 		'x-xfig' => array('fig'),
// 		'x-xpinstall' => array('xpi'),
// 		'xhtml+xml' => array(
// 			'xht',
// 			'xhtml'
// 		),
// 		'xml' => array(
// 			'wsdl',
// 			'xml',
// 			'xpdl',
// 			'xsd',
// 			'xsl'
// 		),
// 		'xspf+xml' => array('xspf'),
// 		'zip' => array('zip')
// 	),
// 	'audio' => array(
// 		'amr' => array('amr'),
// 		'amr-wb' => array('awb'),
// 		'annodex' => array('axa'),
// 		'basic' => array(
// 			'au',
// 			'snd'
// 		),
// 		'flac' => array('flac'),
// 		'midi' => array(
// 			'kar',
// 			'mid',
// 			'midi'
// 		),
// 		'mpeg' => array(
// 			'm4a',
// 			'mp2',
// 			'mp3',
// 			'mpega',
// 			'mpga'
// 		),
// 		'ogg' => array(
// 			'oga',
// 			'ogg',
// 			'spx'
// 		),
// 		'prs.sid' => array('sid'),
// 		'x-aiff' => array(
// 			'aif',
// 			'aifc',
// 			'aiff'
// 		),
// 		'x-gsm' => array('gsm'),
// 		'x-mpegurl' => array('m3u'),
// 		'x-ms-wax' => array('wax'),
// 		'x-ms-wma' => array('wma'),
// 		'x-pn-realaudio' => array(
// 			'ram',
// 			'rm'
// 		),
// 		'x-realaudio' => array('ra'),
// 		'x-scpls' => array('pls'),
// 		'x-sd2' => array('sd2'),
// 		'x-wav' => array('wav')
// 	),
// 	'chemical' => array(
// 		'x-alchemy' => array('alc'),
// 		'x-cache' => array(
// 			'cac',
// 			'cache'
// 		),
// 		'x-cache-csf' => array('csf'),
// 		'x-cactvs-binary' => array(
// 			'cascii',
// 			'cbin',
// 			'ctab'
// 		),
// 		'x-cdx' => array('cdx'),
// 		'x-cerius' => array('cer'),
// 		'x-chem3d' => array('c3d'),
// 		'x-chemdraw' => array('chm'),
// 		'x-cif' => array('cif'),
// 		'x-cmdf' => array('cmdf'),
// 		'x-cml' => array('cml'),
// 		'x-compass' => array('cpa'),
// 		'x-crossfire' => array('bsd'),
// 		'x-csml' => array(
// 			'csm',
// 			'csml'
// 		),
// 		'x-ctx' => array('ctx'),
// 		'x-cxf' => array(
// 			'cef',
// 			'cxf'
// 		),
// 		'x-embl-dl-nucleotide' => array(
// 			'emb',
// 			'embl'
// 		),
// 		'x-galactic-spc' => array('spc'),
// 		'x-gamess-input' => array(
// 			'gam',
// 			'gamin',
// 			'inp'
// 		),
// 		'x-gaussian-checkpoint' => array(
// 			'fch',
// 			'fchk'
// 		),
// 		'x-gaussian-cube' => array('cub'),
// 		'x-gaussian-input' => array(
// 			'gau',
// 			'gjc',
// 			'gjf'
// 		),
// 		'x-gaussian-log' => array('gal'),
// 		'x-gcg8-sequence' => array('gcg'),
// 		'x-genbank' => array('gen'),
// 		'x-hin' => array('hin'),
// 		'x-isostar' => array(
// 			'ist',
// 			'istr'
// 		),
// 		'x-jcamp-dx' => array(
// 			'dx',
// 			'jdx'
// 		),
// 		'x-kinemage' => array('kin'),
// 		'x-macmolecule' => array('mcm'),
// 		'x-macromodel-input' => array(
// 			'mmd',
// 			'mmod'
// 		),
// 		'x-mdl-molfile' => array('mol'),
// 		'x-mdl-rdfile' => array('rd'),
// 		'x-mdl-rxnfile' => array('rxn'),
// 		'x-mdl-sdfile' => array(
// 			'sd',
// 			'sdf'
// 		),
// 		'x-mdl-tgf' => array('tgf'),
// 		'x-mmcif' => array('mcif'),
// 		'x-mol2' => array('mol2'),
// 		'x-molconn-Z' => array('b'),
// 		'x-mopac-graph' => array('gpt'),
// 		'x-mopac-input' => array(
// 			'mop',
// 			'mopcrt',
// 			'mpc',
// 			'zmt'
// 		),
// 		'x-mopac-out' => array('moo'),
// 		'x-mopac-vib' => array('mvb'),
// 		'x-ncbi-asn1-ascii' => array('prt'),
// 		'x-ncbi-asn1-binary' => array(
// 			'aso',
// 			'val'
// 		),
// 		'x-ncbi-asn1-spec' => array('asn'),
// 		'x-pdb' => array(
// 			'ent',
// 			'pdb'
// 		),
// 		'x-rosdal' => array('ros'),
// 		'x-swissprot' => array('sw'),
// 		'x-vamas-iso14976' => array('vms'),
// 		'x-vmd' => array('vmd'),
// 		'x-xtel' => array('xtel'),
// 		'x-xyz' => array('xyz')
// 	),
// 	'image' => array(
// 		'gif' => array('gif'),
// 		'ief' => array('ief'),
// 		'jpeg' => array(
// 			'jpe',
// 			'jpeg',
// 			'jpg'
// 		),
// 		'pcx' => array('pcx'),
// 		'png' => array('png'),
// 		'svg+xml' => array(
// 			'svg',
// 			'svgz'
// 		),
// 		'tiff' => array(
// 			'tif',
// 			'tiff'
// 		),
// 		'vnd.djvu' => array(
// 			'djv',
// 			'djvu'
// 		),
// 		'vnd.wap.wbmp' => array('wbmp'),
// 		'x-canon-cr2' => array('cr2'),
// 		'x-canon-crw' => array('crw'),
// 		'x-cmu-raster' => array('ras'),
// 		'x-coreldraw' => array('cdr'),
// 		'x-coreldrawpattern' => array('pat'),
// 		'x-coreldrawtemplate' => array('cdt'),
// 		'x-corelphotopaint' => array('cpt'),
// 		'x-epson-erf' => array('erf'),
// 		'x-icon' => array('ico'),
// 		'x-jg' => array('art'),
// 		'x-jng' => array('jng'),
// 		'x-ms-bmp' => array('bmp'),
// 		'x-nikon-nef' => array('nef'),
// 		'x-olympus-orf' => array('orf'),
// 		'x-photoshop' => array('psd'),
// 		'x-portable-anymap' => array('pnm'),
// 		'x-portable-bitmap' => array('pbm'),
// 		'x-portable-graymap' => array('pgm'),
// 		'x-portable-pixmap' => array('ppm'),
// 		'x-rgb' => array('rgb'),
// 		'x-xbitmap' => array('xbm'),
// 		'x-xpixmap' => array('xpm'),
// 		'x-xwindowdump' => array('xwd')
// 	),
// 	'message' => array(
// 		'rfc822' => array(
// 			'eml',
// 			'mht',
// 			'mhtml',
// 			'nws'
// 		)
// 	),
// 	'model' => array(
// 		'iges' => array(
// 			'iges',
// 			'igs'
// 		),
// 		'mesh' => array(
// 			'mesh',
// 			'msh',
// 			'silo'
// 		),
// 		'x3d+binary' => array('x3db'),
// 		'x3d+vrml' => array('x3dv'),
// 		'x3d+xml' => array('x3d')
// 	),
// 	'text' => array(
// 		'calendar' => array('ics',
// 			'icz'
// 		),
// 		'css' => array('css'),
// 		'csv' => array('csv'),
// 		'h323' => array('323'),
// 		'html' => array(
// 			'htm',
// 			'html',
// 			'shtml'
// 		),
// 		'iuls' => array('uls'),
// 		'mathml' => array('mml'),
// 		'plain' => array(
// 			'asc',
// 			'brf',
// 			'cfg',
// 			'conf',
// 			'irc',
// 			'ksh',
// 			'pot',
// 			'text',
// 			'txt'
// 		),
// 		'richtext' => array('rtx'),
// 		'scriptlet' => array(
// 			'sct',
// 			'wsc'
// 		),
// 		'tab-separated-values' => array('tsv'),
// 		'texmacs' => array(
// 			'tm',
// 			'ts'
// 		),
// 		'vnd.sun.j2me.app-descriptor' => array('jad'),
// 		'vnd.wap.wml' => array('wml'),
// 		'vnd.wap.wmlscript' => array('wmls'),
// 		'x-bibtex' => array('bib'),
// 		'x-boo' => array('boo'),
// 		'x-c++hdr' => array(
// 			'h++',
// 			'hh',
// 			'hpp',
// 			'hxx'
// 		),
// 		'x-c++src' => array(
// 			'c++',
// 			'cc',
// 			'cpp',
// 			'cxx'
// 		),
// 		'x-chdr' => array('h'),
// 		'x-component' => array('htc'),
// 		'x-csh' => array('csh'),
// 		'x-csrc' => array('c'),
// 		'x-diff' => array(
// 			'diff',
// 			'patch'
// 		),
// 		'x-dsrc' => array('d'),
// 		'x-haskell' => array('hs'),
// 		'x-java' => array('java'),
// 		'x-literate-haskell' => array('lhs'),
// 		'x-moc' => array('moc'),
// 		'x-pascal' => array(
// 			'p',
// 			'pas'
// 		),
// 		'x-pcs-gcd' => array('gcd'),
// 		'x-perl' => array(
// 			'pl',
// 			'pm'
// 		),
// 		'x-python' => array('py'),
// 		'x-scala' => array('scala'),
// 		'x-setext' => array('etx'),
// 		'x-sgml' => array(
// 			'sgm',
// 			'sgml'
// 		),
// 		'x-sh' => array('sh'),
// 		'x-tcl' => array(
// 			'tcl',
// 			'tk'
// 		),
// 		'x-tex' => array(
// 			'cls',
// 			'ltx',
// 			'sty',
// 			'tex'
// 		),
// 		'x-vcalendar' => array('vcs'),
// 		'x-vcard' => array('vcf')
// 	),
// 	'video' => array(
// 		'3gpp' => array('3gp'),
// 		'annodex' => array('axv'),
// 		'dl' => array('dl'),
// 		'dv' => array(
// 			'dif',
// 			'dv'
// 		),
// 		'fli' => array('fli'),
// 		'gl' => array('gl'),
// 		'mp4' => array('mp4'),
// 		'mpeg' => array(
// 			'm1v',
// 			'mpa',
// 			'mpe',
// 			'mpeg',
// 			'mpg'
// 		),
// 		'ogg' => array('ogv'),
// 		'quicktime' => array(
// 			'mov',
// 			'qt'
// 		),
// 		'vnd.mpegurl' => array('mxu'),
// 		'x-flv' => array('flv'),
// 		'x-la-asf' => array(
// 			'lsf',
// 			'lsx'
// 		),
// 		'x-matroska' => array(
// 			'mkv',
// 			'mpv'
// 		),
// 		'x-mng' => array('mng'),
// 		'x-ms-asf' => array(
// 			'asf',
// 			'asx'
// 		),
// 		'x-ms-wm' => array('wm'),
// 		'x-ms-wmv' => array('wmv'),
// 		'x-ms-wmx' => array('wmx'),
// 		'x-ms-wvx' => array('wvx'),
// 		'x-msvideo' => array('avi'),
// 		'x-sgi-movie' => array('movie')
// 	),
// 	'x-conference' => array(
// 		'x-cooltalk' => array('ice')
// 	),
// 	'x-epoc' => array(
// 		'x-sisx-app' => array('sisx')
// 	),
// 	'x-world' => array(
// 		'x-vrml' => array(
// 			'vrm',
// 			'vrml',
// 			'wrl'
// 		)
// 	)
// );