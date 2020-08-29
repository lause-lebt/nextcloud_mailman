<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Mailman\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
	[ 'name' => 'page#index', 'url' => '/', 'verb' => 'GET' ],
        [ 'name' => 'config#getConfig', 'url' => '/config', 'verb' => 'GET' ],
	[ 'name' => 'config#setConfig', 'url' => '/config', 'verb' => 'POST' ],
	[ 'name' => 'mm#lists', 'url' => '/mm', 'verb' => 'GET' ],
	[ 'name' => 'mm#members', 'url' => '/mm/{list}', 'verb' => 'GET' ],
	[ 'name' => 'mm#subscribe', 'url' => '/mm/{list}/{email}', 'verb' => 'PUT' ],
	[ 'name' => 'mm#unsubscribe', 'url' => '/mm/{list}/{email}', 'verb' => 'DELETE' ]
    ]
];
