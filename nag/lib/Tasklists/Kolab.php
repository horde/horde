<?php
/**
 * The Kolab specific tasklists handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Nag
 */
class Nag_Tasklists_Kolab
extends Nag_Tasklists_Base
{
    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    protected function getDefaultShareName()
    {
        return _("Tasks");
    }

    /**
     * Add any modifiers required to the share in order to mark it as default.
     *
     * @param Horde_Share_Object $share The new default share.
     */
    protected function prepareDefaultShare($share)
    {
        $share->set('default', true);
    }
}