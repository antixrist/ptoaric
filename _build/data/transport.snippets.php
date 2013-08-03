<?php 

$snippets = array();

$snippet_name = 'ptoaric';

$snippet= $modx->newObject('modSnippet');
$snippet->fromArray(array(
	'name'			=> $snippet_name,
	'description'	=> '',
	'snippet'		=> getSnippetContent($sources['snippets'].$snippet_name.'.php'),
),'',true,true);
$properties = include $sources['build'].'properties/'.$snippet_name.'.php';
$snippet->setProperties($properties);
$snippets[] = $snippet;

unset($snippet);
unset($snippet_name);
unset($properties);

return $snippets;