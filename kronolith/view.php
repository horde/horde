<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/base.php';

$view = Kronolith::getView(Util::getFormData('view'));
if ($view) {
    $print_view = false;
    Kronolith::tabs(strtolower(str_replace('kronolith_view_', '', String::lower(get_class($view)))));
    $view->html();
    echo '<div style="display:none" id="view_vars" view="' . htmlspecialchars(Util::getFormData('view')) . '" date="' . Kronolith::currentDate()->format(DATE_RFC2822) . '" print="' . Util::addParameter($view->link(), 'print', 1) . '">';
}
