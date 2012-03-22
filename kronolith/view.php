<?php
/**
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

if (Kronolith::showAjaxView()) {
    Horde::url('', true)->redirect();
}

$view = Kronolith::getView(Horde_Util::getFormData('view'));
if ($view) {
    Kronolith::tabs(strtolower(str_replace('kronolith_view_', '', Horde_String::lower(get_class($view)))));
    $view->html();
    echo '<div style="display:none" id="view_vars" view="' . htmlspecialchars(Horde_Util::getFormData('view')) . '" date="' . Kronolith::currentDate()->format(DATE_RFC2822) . '">';
}
