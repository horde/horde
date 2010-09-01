<?php
/**
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
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
