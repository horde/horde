<?php
/**
 * Horde sidebar generation.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Pawlowsky <mikep@clearskymedia.ca>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Horde
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

function _renderSidebar()
{
    global $conf, $injector, $language, $page_output, $prefs, $registry;

    if ((!$registry->hasView($registry::VIEW_DYNAMIC) ||
        $registry->getView() != $registry::VIEW_DYNAMIC) &&
        ($conf['menu']['always'] ||
         ($registry->getAuth() && $prefs->getValue('show_sidebar')))) {
        $sidebar = $injector->getInstance('Horde_Core_Sidebar');
        $is_js = $sidebar->isJavascript();
        $tree = $is_js
            ? $sidebar->getBaseTree()
            : $sidebar->getTree();

        $page_output->addScriptFile('sidebar.js', 'horde');

        $ajax_url = $registry->getServiceLink('ajax', 'horde');
        $ajax_url->pathInfo = 'sidebarUpdate';

        $rtl = intval($registry->nlsconfig->curr_rtl);
        $show_sidebar = !isset($_COOKIE['horde_sidebar_expanded']) || $_COOKIE['horde_sidebar_expanded'];
        $width = intval($prefs->getValue('sidebar_width'));

        if ($is_js) {
            $page_output->addInlineJsVars(array(
                'HordeSidebar.domain' => $conf['cookie']['domain'],
                'HordeSidebar.path' => $conf['cookie']['path'],
                '-HordeSidebar.refresh' => intval($prefs->getValue('menu_refresh_time')),
                'HordeSidebar.url' => strval($ajax_url),
                '-HordeSidebar.width' => $width
            ));
        }

        require $registry->get('templates', 'horde') . '/sidebar/sidebar.inc';

        if ($show_sidebar) {
            $style = $rtl
                ? 'margin-right:' . $width . 'px'
                : 'margin-left:' . $width . 'px';
        } else {
            /* Default to 18px. If local theme changes alter this value, it will
             * automatically be determined by javascript at load time. */
            $style = $rtl
                ? 'margin-right:18px'
                : 'margin-left:18px';
        }

        echo '<div id="horde_body" class="body" style="' . $style . '">';
    } else {
        echo '<div class="body" id="horde_body">';
    }

    $page_output->sidebarLoaded = true;
}

_renderSidebar();
