<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('hermes');

/* Determine View */
switch ($registry->getView()) {
case Horde_Registry::VIEW_DYNAMIC:
    if ($pref->getValue('dynamic_view')) {
        $help_link = Horde::getServiceLink('help', 'hermes');
        if ($help_link) {
            $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
        }

        $today = new Horde_Date();
        $injector->getInstance('Hermes_Ajax')->header();
        require HERMES_TEMPLATES . '/index/index.inc';
        Horde::includeScriptFiles();
        Horde::outputInlineScript();
        echo "</body>\n</html>";
        exit;
    }
default:
    include HERMES_BASE . '/time.php';
    exit;
}
