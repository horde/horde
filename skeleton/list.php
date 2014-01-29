<?php
/**
 * Example list script.
 *
 * Copyright 2007-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author    Your Name <you@example.com>
 * @category  Horde
 * @copyright 2007-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Skeleton
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('skeleton');

/* Example of how to use Horde_View. If getting a Horde_View instance via
 * createInstance() from the injector, the template path already defaults to
 * the application's templates/ folder. */
$view = $injector->createInstance('Horde_View');
$view->header = _("Header");
$view->content = _("Some Content");
$view->list = array(
    array('One', 'Foo'),
    array('Two', 'Bar'),
);

/* Load JavaScript for sortable table. */
$page_output->addScriptFile('tables.js', 'horde');

/* Here starts the actual page output. First we output the complete HTML
 * header, CSS files, the topbar menu, and the sidebar menu. */
$page_output->header(array(
    'title' => _("List")
));

/* Next we output any notification messages. This is not done automatically
 * because on some pages you might not want to have notifications. */
$notification->notify(array('listeners' => 'status'));

/* Here goes the actual content of your application's page. This could be
 * Horde_View output, a rendered Horde_Form, or any other arbitrary HTML
 * output. */
echo $view->render('list');

/* Finally the HTML content is closed and JavaScript files are loaded. */
$page_output->footer();
