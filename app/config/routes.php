<?php
// Route mapping definitions
// Mapped as: 'path' => 'ControllerName@actionMethod'

return [
    'GET' => [
        '' => 'ViewerController@index',
        'admin' => 'AdminController@dashboard',
        'admin/login' => 'AuthController@showLogin',
        'admin/logout' => 'AuthController@logout',
        'admin/dashboard' => 'AdminController@dashboard',
        'admin/templates' => 'TemplateController@index',
        'admin/templates/create' => 'TemplateController@create',
        'admin/templates/edit' => 'TemplateController@edit',
        'admin/media' => 'MediaController@index',
        'admin/settings' => 'AdminController@settings',
        'admin/analytics' => 'AdminController@analytics',
        'admin/photos' => 'AdminController@photos',
    ],
    'POST' => [
        'admin/login' => 'AuthController@login',
        'admin/templates/create' => 'TemplateController@store',
        'admin/templates/edit' => 'TemplateController@update',
        'admin/templates/delete' => 'TemplateController@delete',
        'admin/media/upload' => 'MediaController@upload',
        'admin/media/delete' => 'MediaController@delete',
        'admin/settings' => 'AdminController@saveSettings',
        'admin/photos/delete' => 'AdminController@deletePhoto',
        'api/track-click' => 'ViewerController@trackClick',
        'api/submit-photo' => 'ViewerController@submitPhoto',
    ]
];
