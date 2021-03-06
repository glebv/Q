<?php

function MyApp_before_Q_responseExtras()
{
	$app = Q_Config::expect('Q', 'app');	
	
	Q_Response::addStylesheet('plugins/Q/css/Ui.css');
	Q_Response::addStylesheet('css/html.css');

	if (Q_Config::get('Q', 'firebug', false)) {
		Q_Response::addScript("https://getfirebug.com/firebug-lite-debug.js");
	}
	Q_Response::addScript('js/MyApp.js');
	
	header('Vary: User-Agent');
	
	// running an event for loading action-specifig extras (if there are any)
	$uri = Q_Dispatcher::uri();
	$module = $uri->module;
	$action = $uri->action;
	$event = "$module/$action/response/responseExtras";
	if (Q::canHandle($event)) {
		Q::event($event);
	}
}
