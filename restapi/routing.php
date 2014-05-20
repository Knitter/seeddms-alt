<?php

include 'DocumentAPI.php';

/** @var $app Slim */
$app->any('/', function() {

    echo json_encode(array(
        (object) array('resource' => '/login', 'method' => 'POST'),
        (object) array('resource' => '/logout', 'method' => 'GET'),
        (object) array('resource' => '/account', 'method' => 'GET'),
        (object) array('resource' => '/search', 'method' => 'GET'),
        (object) array('resource' => '/folder/:id', 'method' => 'GET'),
        (object) array('resource' => '/folder/:id/move', 'method' => 'POST'),
        (object) array('resource' => '/folder/:id', 'method' => 'DELETE'),
        (object) array('resource' => '/folder/:id/children', 'method' => 'GET'),
        (object) array('resource' => '/folder/:id/parent', 'method' => 'GET'),
        (object) array('resource' => '/folder/:id/path', 'method' => 'GET'),
        (object) array('resource' => '/folder/:id/createfolder', 'method' => 'POST'),
        (object) array('resource' => '/document', 'method' => 'POST'),
        (object) array('resource' => '/document/:id', 'method' => 'GET'),
        (object) array('resource' => '/document/:id', 'method' => 'delete'),
        (object) array('resource' => '/document/:id/move', 'method' => 'POST'),
        (object) array('resource' => '/document/:id/content', 'method' => 'GET'),
        (object) array('resource' => '/document/:id/versions', 'method' => 'GET'),
        (object) array('resource' => '/document/:id/version/:version', 'method' => 'GET'),
        (object) array('resource' => '/document/:id/files', 'method' => 'GET'),
        (object) array('resource' => '/document/:id/file/:fileid', 'method' => 'GET'),
        (object) array('resource' => '/document/:id/links', 'method' => 'GET'),
        (object) array('resource' => '/account/fullname', 'method' => 'PUT'),
        (object) array('resource' => '/account/email', 'method' => 'PUT'),
        (object) array('resource' => '/account/locked', 'method' => 'GET')
    ));
});

$app->post('/document', function() {
    global $dms, $userobj, $settings;

    DocumentAPI::add($dms, $userobj, $settings);
});
