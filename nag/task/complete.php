<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('nag');

$request = $injector->getInstance('Horde_Controller_Request');
$controller = new Nag_CompleteTask_Controller();
$controller->setInjector($injector);
$response = $injector->createInstance('Horde_Controller_Response');
$controller->processRequest($request, $response);

$responseWriter = $injector->getInstance('Horde_Controller_ResponseWriter');
$responseWriter->writeResponse($response);
