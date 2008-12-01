<?php
/**
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 * @author Michael Slusarz <slusarz@horde.org>
 */

require_once dirname(__FILE__) . '/lib/base.php';

$scripts = array(
    array('DimpBase.js', 'imp', true),
    array('ContextSensitive.js', 'imp', true),
    array('ViewPort.js', 'imp', true),
    array('dragdrop.js', 'imp', true),
    array('dhtmlHistory.js', 'horde', true),
    array('redbox.js', 'horde', true),
    array('mailbox-dimp.js', 'imp'),
    array('DimpSlider.js', 'imp', true),
    array('unblockImages.js', 'imp', true)
);

/* Get site specific menu items. */
$js_code = $site_menu = array();
if (is_readable(IMP_BASE . '/config/menu.php')) {
    include IMP_BASE . '/config/menu.php';
}

/* Add the site specific javascript now. */
if (!empty($site_menu)) {
    foreach ($site_menu as $key => $menu_item) {
        if ($menu_item == 'separator') {
            continue;
        }
        $js_code[] = 'DimpCore.clickObserveHandler({ d: $(\'menu' . $key . '\'), f: function() { ' . $menu_item['action'] . ' } })';
        $js_code[] = 'DimpCore.clickObserveHandler({ d: $(\'tab' . $key . '\'), f: function() { ' . $menu_item['action'] . ' } })';
    }
}

IMP::addInlineScript($js_code, true);
DIMP::header('', $scripts);

/* Get application folders list. */
$application_folders = array();
foreach (DIMP::menuList() as $app) {
    if ($registry->get('status', $app) != 'inactive' &&
        $registry->hasPermission($app, PERMS_SHOW)) {
        $application_folders[] = array(
            'name' => htmlspecialchars($registry->get('name', $app)),
            'icon' => $registry->get('icon', $app),
            'app' => rawurlencode($app)
        );
    }
}

echo "<body>\n";
require IMP_TEMPLATES . '/index/index.inc';
IMP::includeScriptFiles();
IMP::outputInlineScript();
$notification->notify(array('listeners' => array('javascript')));
echo "</body>\n</html>";
