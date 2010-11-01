<?php
/**
 * Dynamic display (DIMP) base page.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('impmode' => 'dimp'));

$scripts = array(
    array('dimpbase.js', 'imp'),
    array('viewport.js', 'imp'),
    array('dialog.js', 'imp'),
    array('mailbox-dimp.js', 'imp'),
    array('imp.js', 'imp'),
    array('contextsensitive.js', 'horde'),
    array('dragdrop2.js', 'horde'),
    array('popup.js', 'horde'),
    array('redbox.js', 'horde'),
    array('slider2.js', 'horde')
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
Horde::noDnsPrefetch();
IMP_Dimp::header('', $scripts);

echo "<body>\n";
require IMP_TEMPLATES . '/dimp/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();
echo "</body>\n</html>";
