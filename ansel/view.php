<?php
/**
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('ansel');

$viewname = basename(Horde_Util::getFormData('view', 'Gallery'));
$view = 'Ansel_View_' . $viewname;
if (!class_exists($view)) {
    throw new Horde_Exception(sprintf("Could not load class definition of %s", $view));
}

/*
 * All parameters get passed into the View via a $params array so we can
 * pass the same parameters easily via API calls.
 */
$params['page'] = Horde_Util::getFormData('page', 0);
$params['sort'] = Horde_Util::getFormData('sort');
$params['sort_dir'] = Horde_Util::getFormData('sort_dir', 0);
$params['year'] = Horde_Util::getFormData('year', 0);
$params['month'] = Horde_Util::getFormData('month', 0);
$params['day'] = Horde_Util::getFormData('day', 0);
$params['view'] = $viewname;
$params['gallery_view'] = Horde_Util::getFormData('gallery_view');
$params['gallery_id'] = Horde_Util::getFormData('gallery');
$params['gallery_slug'] = Horde_Util::getFormData('slug');
$params['force_grouping'] = Horde_Util::getFormData('force_grouping');
$params['image_id'] = Horde_Util::getFormData('image');

try {
    $view = new $view($params);
} catch (Horde_Exception $e) {
    require ANSEL_TEMPLATES . '/common-header.inc';
    echo Horde::menu();
    $notification->notify(array('listeners' => 'status'));
    echo '<br /><em>' . htmlspecialchars($e->getMessage()) . '</em>';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

$title = $view->getTitle();
require ANSEL_TEMPLATES . '/common-header.inc';
echo Horde::menu();
$notification->notify(array('listeners' => 'status'));
$view_html = $view->html();
echo $view_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
