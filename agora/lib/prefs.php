<?php
/**
 * Copyright 2005-2007 Andrew Hosie <ahosie@gmail.com>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

function handle_avatarselect($updated)
{
    if ($GLOBALS['conf']['avatar']['allow_avatars']) {
        $avatar_path = Horde_Util::getFormData('avatar_path');
        $avatar_path = Agora::validateAvatar($avatar_path) ? $avatar_path : null;
        if ($avatar_path) {
            $GLOBALS['prefs']->setValue('avatar_path', $avatar_path);
            $updated = true;
        }
    }

    return $updated;
}
