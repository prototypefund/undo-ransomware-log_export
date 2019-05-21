<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\LogExport\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
	   ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
       ['name' => 'page#download', 'url' => '/sync/download', 'verb' => 'GET'],
       ['name' => 'scan#download', 'url' => '/scan/download', 'verb' => 'GET'],
       ['name' => 'scan#filesToScan', 'url' => '/api/{apiVersion}/files-to-scan', 'verb' => 'GET', 'requirements' => ['apiVersion' => 'v1']],
       ['name' => 'scan#scan', 'url' => '/api/{apiVersion}/scan', 'verb' => 'POST', 'requirements' => ['apiVersion' => 'v1']],
    ]
];
