<?php
/**
 * The Hylax script to show a fax view.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
$hylax = Horde_Registry::appInit('hylax', array('authentication' => 'none'));

$fax_id = Horde_Util::getFormData('fax_id');
$page = Horde_Util::getFormData('page');
$preview = Horde_Util::getFormData('preview');

/* Set up the cache object. */
$cache = $injector->getInstance('Horde_Cache');

/* Call the get the image data using cache. */
$id = $fax_id . '_' . $page . ($preview ? '_p' : '');
$image = $cache->getOutput($id, "Hylax::getImage('$fax_id', '$page', '$preview');", 86400);

header('Content-type: image/png');
echo $image;
