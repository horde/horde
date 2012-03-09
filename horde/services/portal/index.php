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
if ($registry->getView() == Horde_Registry::VIEW_SMARTMOBILE) {
    Horde::getServiceLink('portal')->redirect();
    exit;
}

// Get refresh interval.
$page_output = $injector->getInstance('Horde_PageOutput');
if (($r_time = $prefs->getValue('summary_refresh_time'))
    && !$browser->hasFeature('xmlhttpreq')) {
    $page_output->metaRefresh($r_time, Horde::url('services/portal/'));
}

// Render layout.
$view = new Horde_Core_Block_Layout_View(
    $injector->getInstance('Horde_Core_Factory_BlockCollection')->create()->getLayout(),
    Horde::url('services/portal/edit.php'),
    Horde::url('services/portal/index.php', true)
);
$layout_html = $view->toHtml();

foreach ($view->getStylesheets() as $val) {
    $page_output->addStylesheet($val['fs'], $val['uri']);
}

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
