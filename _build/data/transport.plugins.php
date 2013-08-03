<?php 

$plugins = array();

$plugin_name = 'ptoaric_upload';

$plugin = $modx->newObject('modPlugin');
$plugin->fromArray(array(
	'name'			=> $plugin_name,
	'description'	=> '',
	'plugincode'	=> getSnippetContent($sources['plugins'].$plugin_name.'.php'),
),'',true,true);
$plugins[] = $plugin;

/* add plugin events */
$events = array();
$events['OnFileManagerUpload'] = $modx->newObject('modPluginEvent');
$events['OnFileManagerUpload']->fromArray(array(
    'event' => 'OnFileManagerUpload',
    'priority' => 0,
    'propertyset' => 0,
),'',true,true);
$plugin->addMany($events, 'PluginEvents');

unset($plugin);
unset($events);
unset($plugin_name);

return $plugins;