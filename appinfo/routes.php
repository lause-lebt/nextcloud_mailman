<?php
return [
    'routes' => [
		[ 'name' => 'page#index', 'url' => '/', 'verb' => 'GET' ],
		[ 'name' => 'page#gotoList', 'url' => '/list/{id}', 'verb' => 'GET' ],
		// admin
    	[ 'name' => 'api#getServerConfig', 'url' => '/api/config', 'verb' => 'GET' ],
		[ 'name' => 'api#setServerConfig', 'url' => '/api/config', 'verb' => 'POST' ],
		[ 'name' => 'api#getServerStatus', 'url' => '/api/status', 'verb' => 'GET' ],
		[ 'name' => 'api#getListData', 'url' => '/api/listdata', 'verb' => 'GET' ],
		[ 'name' => 'api#setListData', 'url' => '/api/listdata', 'verb' => 'POST' ],
		[ 'name' => 'api#updateListData', 'url' => '/api/listdata/{listid}', 'verb' => 'PUT' ],
		[ 'name' => 'api#getPreview', 'url' => '/api/preview', 'verb' => 'POST' ],
		// user
		[ 'name' => 'api#getUserLists', 'url' => '/api/lists', 'verb' => 'GET' ],
		[ 'name' => 'api#subscribeUser', 'url' => '/api/subscribe', 'verb' => 'POST' ],
		[ 'name' => 'api#unsubscribeUser', 'url' => '/api/unsubscribe', 'verb' => 'POST' ],
		// archive
		[ 'name' => 'archive#getArchive', 'url' => '/api/archive/{id}', 'verb' => 'GET' ]
//	[ 'name' => 'mm#lists', 'url' => '/mm', 'verb' => 'GET' ],
//	[ 'name' => 'mm#members', 'url' => '/mm/{list}', 'verb' => 'GET' ],
//	[ 'name' => 'mm#subscribe', 'url' => '/mm/{list}/{email}', 'verb' => 'PUT' ],
//	[ 'name' => 'mm#unsubscribe', 'url' => '/mm/{list}/{email}', 'verb' => 'DELETE' ]
    ]
];
