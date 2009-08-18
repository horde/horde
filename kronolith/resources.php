<?php
/**
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */
require_once dirname(__FILE__) . '/lib/base.php';

$title = _("Edit resources");
require KRONOLITH_TEMPLATES . '/common-header.inc';

/* Test some resource crap */
$new = array('name' => _("N329SP"),
             'category' => 'test');

//$resource = new Kronolith_Resource_Single($new);
//$results = Kronolith_Resource::addResource($resource);
//var_dump($results);

/* Test adding resource to event */
var_dump(Kronolith_Resource::getResource(5));