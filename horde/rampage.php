<?php
/**
 * Rampage routing script
 *
 * Theory/notes:
 *
 *  - registry is already modular
 *  - can use webroot or a new slug param to match the beginning of URLs for routing
 *  - then pass to the app's route bundle
 *  - how to set right class names for apps (controllers, etc.)
 *  - registry could be (optionally) db-managed as a Horde_Policy
 *  - just array/hash structures
 *  - build a cached regex to match apps - or just slugs, but strip off horde webroot first
 *  - app directory structure - apps w/out images/css/etc. can be entirely out of the webroot. components! tagger is a good candidate for this
 *  - apps have their own default policies, but put overrides in global horde config dir?
 *  - special autoload for views + controllers
 *  - where to put public/ dirs?
 *
 * [app|component]/
 *   Views/
 *   Controllers/
 *     FooController.php -> class App_FooController
 * no nested components?
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('horde');

// Set up our request and routing objects
$request = new Horde_Controller_Request_Http();
$mapper = new Horde_Routes_Mapper();

$uri = substr($request->getUri(), strlen($registry->get('webroot', 'horde')));
if (strpos($uri, '/') === false) {
    $app = $uri;
    $path = '';
} else {
    list($app, $path) = explode('/', $uri, 2);
}

// Check for route definitions.
$fileroot = $registry->get('fileroot', $app);
$routeFile = $fileroot . '/config/routes.php';
if (!file_exists($routeFile)) {
    throw new Horde_Controller_Exception('Not routable: ' . $uri);
}

// @TODO Use the registry to check app permissions, etc.
// $registry->pushApp($app);

// Application routes are relative only to the application. Let the mapper know
// where they start.
$mapper->prefix = $registry->get('webroot', 'horde') . '/' . $app;

// @TODO ? $mapper->createRegs(array('blogs', 'comments', 'posts')) would avoid
// the directory scan entirely. The argument is the name of every controller in
// the system. Should also cache the controller scan.

// Load application routes.
include $routeFile;

// Set up application class and controller loading
// @TODO separate $app from class names so that there can be multiple instances
// of an app in the registry?
Horde_Autoloader::addClassPattern('/^' . $app . '_/i', $fileroot . '/lib/');
Horde_Autoloader::addClassPattern('/^' . $app . '_/i', $fileroot . '/app/controllers/');

// Create our controller context.
$context = array(
    'mapper' => $mapper,
    'controllerDir' => $fileroot . '/app/controllers',
    'viewsDir' => $fileroot . '/app/views',
    // 'logger' => '',
);

// Dispatch.
$dispatcher = Horde_Controller_Dispatcher::singleton($context);
$dispatcher->dispatch($request);
