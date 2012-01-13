<?php
/**
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

// Make sure we don't need the mobile view.
if ($session->get('horde', 'mode') == 'smartmobile' && Horde::ajaxAvailable()) {
    Horde::getServiceLink('portal')->redirect();
    exit;
}

// Get refresh interval.
if (($r_time = $prefs->getValue('summary_refresh_time'))
    && !$browser->hasFeature('xmlhttpreq')) {
    Horde::metaRefresh($r_time, Horde::url('services/portal/'));
}

// Render layout.
$view = new Horde_Core_Block_Layout_View(
    $injector->getInstance('Horde_Core_Factory_BlockCollection')->create()->getLayout(),
    Horde::url('services/portal/edit.php'),
    Horde::url('services/portal/index.php', true)
);
$layout_html = $view->toHtml();

$css = $injector->getInstance('Horde_Themes_Css');
foreach ($view->getApplications() as $app) {
    foreach ($css->getStylesheets('', array('app' => $app, 'nohorde' => true)) as $val) {
        $css->addStylesheet($val['fs'], $val['uri']);
    }
}

$linkTags = $view->getLinkTags();

$title = _("My Portal");
require HORDE_TEMPLATES . '/common-header.inc';
echo Horde::menu();
echo '<div id="menuBottom">';
echo htmlspecialchars($injector->getInstance('Horde_Core_Factory_Identity')->create()->getName());
if (!$prefs->isLocked('portal_layout')) {
    echo ' | <a href="' . Horde::url('services/portal/edit.php') . '">' . _("Add Content") . '</a>';
}
echo '</div><br class="clear" />';
$notification->notify(array('listeners' => 'status'));
echo $layout_html;
require HORDE_TEMPLATES . '/common-footer.inc';
