<?php
/**
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Max Kalika <max@horde.org>
 * @package Gollem
 */

function handle_columnselect($updated)
{
    $columns = Horde_Util::getFormData('columns');
    if (!empty($columns)) {
        $GLOBALS['prefs']->setValue('columns', $columns);
        return true;
    }
    return false;
}
