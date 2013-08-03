<?php

$settings = array();

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.path', // set unique key
	'value' => 'thumbs',
	'xtype' => 'textfield', // textfield, numberfield, combo-boolean or other
	'namespace' => PKG_NAME_LOWER,
	'area' => 'snippet_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.add_image_folder_if_path_is_absolute',
	'value' => '0',
	'xtype' => 'combo-boolean',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'snippet_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.salt_type',
	'value' => 'options',
	'xtype' => 'textfield',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'snippet_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.salt_in',
	'value' => 'inner_path',
	'xtype' => 'textfield',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'snippet_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.salt_separator',
	'value' => '-',
	'xtype' => 'textfield',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'snippet_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.sourcefile_pt_options',
	'value' => '',
	'xtype' => 'textfield',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'upload_plugin_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.thumbsfile_pt_options',
	'value' => '',
	'xtype' => 'textfield',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'upload_plugin_settings',
),'',true,true);
$settings[] = $setting;

$setting = $modx->newObject('modSystemSetting');
$setting->fromArray(array(
	'key' => 'ptoaric.folders_settings',
	'value' => '',
	'xtype' => 'textfield',
	'namespace' => PKG_NAME_LOWER,
	'area' => 'upload_plugin_settings',
),'',true,true);
$settings[] = $setting;

unset($setting);

return $settings;