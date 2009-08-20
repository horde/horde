<?php
/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
require_once dirname(__FILE__) . '/lib/base.php';

$title = _("Edit resources");
require KRONOLITH_TEMPLATES . '/common-header.inc';

/* Test creating a new resource */
//$new = array('name' => _("Big Meeting Room"),
//             'category' => 'conference rooms');
//
//$resource = new Kronolith_Resource_Single($new);
//$results = Kronolith_Resource::addResource($resource);
//var_dump($results);

/* Test adding resource to event */
$resource = Kronolith_Resource::getResource(9);
$driver = Kronolith::getDriver('Sql');
$event = $driver->getByUID('20090820100656.66097kphc3ecwf8k@localhost');
$event->addResource($resource, Kronolith::RESPONSE_NONE);
$event->save();

//var_dump($resource->getFreeBusy(null, null, true));

/* Test listing resources */
var_dump(Kronolith_Resource::listResources());