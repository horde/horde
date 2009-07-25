<?php
/**
 * $Horde: nag/lib/prefs.php,v 1.12 2009/06/10 05:25:06 slusarz Exp $
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

function handle_tasklistselect($updated)
{
    $default_tasklist = Horde_Util::getFormData('default_tasklist');
    if (!is_null($default_tasklist)) {
        $tasklists = Nag::listTasklists();
        if (is_array($tasklists) && isset($tasklists[$default_tasklist])) {
            $GLOBALS['prefs']->setValue('default_tasklist', $default_tasklist);
            return true;
        }
    }

    return false;
}

function handle_showsummaryselect($updated)
{
    $GLOBAL['prefs']->setValue('summary_categories', Horde_Util::getFormData('summary_categories'));
    return true;
}

function handle_defaultduetimeselect($updated)
{
    $GLOBALS['prefs']->setValue('default_due_time', Horde_Util::getFormData('default_due_time'));
    return true;
}
