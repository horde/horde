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

request processing steps:

- bootstrap
- injector bindings
* customization point
- create request
- create request mapper
- $config = $mapper->getRquestConfiguration($request)
- create runner
- execute runner
  - get settings exporter
  - export bindings
  - get controller name
  - create response
  - create controller builder
  - create filter runner
  - export filters
  - handle internal redirects
  - return response
- write response

add filtered requests/blue_filter port to Horde?
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('horde');

$request = $injector->getInstance('Horde_Controller_Request');

$runner = $injector->getInstance('Horde_Controller_Runner');
$config = $injector->getInstance('Horde_Controller_RequestConfiguration');
$response = $runner->execute($injector, $request, $config);

$responseWriter = $injector->getInstance('Horde_Controller_ResponseWriter');
$responseWriter->writeResponse($response);
