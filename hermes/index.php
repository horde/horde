<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('hermes');

/* Determine View */
switch ($registry->getView()) {
case Horde_Registry::VIEW_DYNAMIC:
    if ($prefs->getValue('dynamic_view')) {
        $menu = new Horde_Menu();
        $help_link = $GLOBALS['registry']->getServiceLink('help', 'hermes');
        if ($help_link) {
            $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
        }

        $today = new Horde_Date();
        $injector->getInstance('Hermes_Ajax')->init();
        require HERMES_TEMPLATES . '/index/index.inc';
        $page_output->includeScriptFiles();
        $page_output->outputInlineScript();
        echo "</body>\n</html>";
        exit;
    }
default:
    include HERMES_BASE . '/time.php';
    exit;
}
