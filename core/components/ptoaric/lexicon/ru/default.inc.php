<?php
/**
 * Настройки сниппета
 */
	// настройки
$_lang['setting_ptoaric.path']									= 'Имя папки';
$_lang['setting_ptoaric.add_image_folder_if_path_is_absolute']	= 'Если папка для превью<br>будет создаваться в корне сайта,<br>то надо ли в этой папке<br>создавать директорию изображения?';
$_lang['setting_ptoaric.salt_type']								= 'Тип соли';
$_lang['setting_ptoaric.salt_in']								= 'Куда добавлять соль?';
$_lang['setting_ptoaric.salt_separator']						= 'Разделитель соли от имени файла';

	// описания
$_lang['setting_ptoaric.path_desc']									= 'Имя папки, в которую будут складываться генерированные картинки.<br>Может быть в виде директории, например "<code>/path/name/</code>".<br>Если начинается со слэша, то считается абсолютным адресом и будет располагаться в корне сайта.<br>Можно оставить пустым, тогда папка создаваться не будет.';

$_lang['setting_ptoaric.add_image_folder_if_path_is_absolute_desc']	= 'Например, если имя папки установлено в "<code>/thumbs/</code>", а исходный файл располагается в папке "<code>/images/cool_photos/</code>",<br>то, в зависимости от данной настройки, картинка-превью будет лежать в "<code>/thumbs/</code>" или в "<code>/thumbs/images/cool_photos/</code>"';

$_lang['setting_ptoaric.salt_type_desc']							= 'а) "<code>options</code>" <code>(по умолчанию)</code> - строка опций для phpThumb сортируется и очищается от лишних символов;<br>
б) "<code>md5</code>" - md5-хэш от строки "<code>options</code>".';

$_lang['setting_ptoaric.salt_in_desc']								= 'а) "<code>filename</code>" - к имени генерируемого файла ("<code>/currentfolder/<b>path</b>/generatedfile<b>-w150h150zc1</b>.ext</code>");<br>
б) "<code>pathname</code>" - к названию папки PATH ("<code>/currentfolder/<b>path-w150h150zc1</b>/generatedfile.ext</code>");<br>
в) "<code>inner_path</code>" <code>(по умолчанию)</code> - создавать папку внутри PATH ("<code>/currentfolder/<b>path/w150h150zc1</b>/generatedfile.ext</code>").';

$_lang['setting_ptoaric.salt_separator_desc']						= 'Пример - "<code>/currentfolder/path<b>_this_is_separator_</b>w150h150zc1/generatedfile.ext</code>".<br>Может быть пустым.';


$_lang['area_snippet_settings']			= 'Сниппет';
$_lang['area_upload_plugin_settings']	= 'Плагин на загрузку';


/**
 * Настройки плагина на загрузку
 */
	// настройки
	$_lang['setting_ptoaric.sourcefile_pt_options']	= 'Глобальные опции phpThumb<br><b>для изменения загруженного файла</b>.';
	$_lang['setting_ptoaric.thumbsfile_pt_options']	= 'Глобальные опции phpThumb<br><b>для генерации превью</b>.';
	$_lang['setting_ptoaric.folders_settings']			= 'JSON-строка параметров для плагина.';

	// описания
	$_lang['setting_ptoaric.sourcefile_pt_options_desc']	= 'Строка вида "<code>w=800&h=600&f=jpg&q=100</code>" <i>(как для передачи в сниппет)</i>. Можно оставить пустым.';
	$_lang['setting_ptoaric.thumbsfile_pt_options_desc']	= 'Строка вида "<code>w=200&h=200&f=jpg&q=100</code>" <i>(как для передачи в сниппет)</i>. Можно оставить пустым.';
	$_lang['setting_ptoaric.folders_settings_desc']			= 'Конфигурация плагина - указать конкретные папки, указать для каждой из папок<br>свои опции <code>sourcefile_pt_options</code> и <code>thumbsfile_pt_options</code> и многое другое.<br>Полное описание смотрите в коде плагина <code>ptoaric_upload</code>.';

