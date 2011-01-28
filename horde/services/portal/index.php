<?php
/**
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 */

require_once dirname(__FILE__) . '/../../lib/Application.php';
Horde_Registry::appInit('horde');

// Get refresh interval.
if (($r_time = $prefs->getValue('summary_refresh_time'))
    && !$browser->hasFeature('xmlhttpreq')) {
    Horde::metaRefresh($r_time, Horde::url('services/portal/'));
}

// Load layout from preferences.
$layout_pref = @unserialize($prefs->getValue('portal_layout'));
if (!is_array($layout_pref)) {
    $layout_pref = array();
}
if (!count($layout_pref)) {
    $layout_pref = Horde_Block_Collection::getFixedBlocks();
}

// If we're serving a request to the JS update client, just return the blocks
// updated HTML content.
if (Horde_Util::getFormData('httpclient')) {
    header('Content-Type: text/html; charset=UTF-8');
    $row = Horde_Util::getFormData('row');
    $col = Horde_Util::getFormData('col');
    if (!is_null($row) && !is_null($col) && !empty($layout_pref[$row][$col])) {
        $item = $layout_pref[$row][$col];
        $block = Horde_Block_Collection::getBlock($item['app'], $item['params']['type'], $item['params']['params'], $row, $col);
        $content = $block->getContent();
        if ($content instanceof PEAR_Error) {
            $content = $content->getMessage();
        }
        echo $content;
    }
    exit;
}

// Render layout.
$view = new Horde_Block_Layout_View(
    $layout_pref,
    Horde::url('services/portal/edit.php'),
    Horde::url('services/portal/index.php', true));
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
