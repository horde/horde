<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file COPYING for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 */

require_once __DIR__ . '/lib/Application.php';
$shout = Horde_Registry::appInit('shout');

require_once SHOUT_BASE . '/lib/Forms/ExtensionForm.php';

$RENDERER = new Horde_Form_Renderer();

$section = 'extensions';
$title = _("Extensions: ");

// Fetch the (possibly updated) list of extensions
try {
    $extensions = $shout->extensions->getExtensions($session->get('shout', 'curaccount_code'));
} catch (Exception $e) {
    $notification->push($e);
    $extensions = array();
}

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->addScriptFile('scriptaculous.js', 'horde');

$page_output->header(array(
    'title' => $title
));
require SHOUT_TEMPLATES . '/menu.inc';
$notification->notify();
require SHOUT_TEMPLATES . '/extensions.inc.php';
$page_output->footer();
