<?php
/**
 * $Horde: ansel/view.php,v 1.111 2009/06/10 00:33:01 mrubinsk Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

@define('ANSEL_BASE', dirname(__FILE__));
require_once ANSEL_BASE . '/lib/base.php';

$viewname = basename(Horde_Util::getFormData('view', 'Gallery'));
include_once ANSEL_BASE . '/lib/Views/' . $viewname . '.php';
$view = 'Ansel_View_' . $viewname;
if (!$view || !class_exists($view)) {
    header('HTTP/1.0 404 Not Found');
    echo 'Not Found';
    exit;
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

$view = call_user_func(array($view, 'makeView'), $params);
if (is_a($view, 'PEAR_Error')) {
    require ANSEL_TEMPLATES . '/common-header.inc';
    require ANSEL_TEMPLATES . '/menu.inc';
    echo '<br /><em>' . htmlspecialchars($view->getMessage()) . '</em>';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

$title = $view->getTitle();
$view_html = $view->html();
Horde::addScriptFile('popup.js', 'horde', true);
require ANSEL_TEMPLATES . '/common-header.inc';
require ANSEL_TEMPLATES . '/menu.inc';
echo $view_html;
require $registry->get('templates', 'horde') . '/common-footer.inc';
