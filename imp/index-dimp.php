<?php
/**
 * Dynamic display (DIMP) base page.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => true));

$scripts = array(
    array('ContextSensitive.js', 'imp'),
    array('DimpBase.js', 'imp'),
    array('DimpSlider.js', 'imp'),
    array('ViewPort.js', 'imp'),
    array('dialog.js', 'imp'),
    array('dragdrop2.js', 'horde'),
    array('imp.js', 'imp'),
    array('mailbox-dimp.js', 'imp'),
    array('popup.js', 'horde'),
    array('redbox.js', 'horde')
);

/* Get site specific menu items. */
$js_code = $site_menu = array();
if (is_readable(IMP_BASE . '/config/menu.php')) {
    include IMP_BASE . '/config/menu.php';
}

/* Add the site specific javascript now. */
if (!empty($site_menu)) {
    foreach ($site_menu as $key => $menu_item) {
        if ($menu_item != 'separator') {
            foreach (array('menu', 'tab') as $val) {
                $js_code[] = 'DimpCore.clickObserveHandler({ d: $(\'' . $val . $key . '\'), f: function() { ' . $menu_item['action'] . ' } })';
            }
        }
    }
}

Horde::addInlineScript($js_code, 'load');
IMP_Dimp::header('', $scripts);

/* Get application folders list. */
$application_folders = array();
foreach (IMP_Dimp::menuList() as $app) {
    if ($registry->get('status', $app) != 'inactive' &&
        $registry->hasPermission($app, Horde_Perms::SHOW)) {
        $application_folders[] = array(
            'name' => htmlspecialchars($registry->get('name', $app)),
            'icon' => $registry->get('icon', $app),
            'app' => rawurlencode($app)
        );
    }
}

echo "<body>\n";
require IMP_TEMPLATES . '/index/index-dimp.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";
