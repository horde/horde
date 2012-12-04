<?php
/**
 * The Kolab specific calendars handler.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kronolith
 */
class Kronolith_Calendars_Kolab extends Kronolith_Calendars_Base
{
    /**
     * Runs any actions after setting a new default calendar.
     *
     * @param string $share  The default share ID.
     */
    public function setDefaultShare($share)
    {
           $calendars = $this->_shares
               ->listShares(
                   $this->_user,
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $this->_user));
           foreach ($calendars as $id => $calendar) {
               if ($id == $share) {
                   $calendar->set('default', true);
                   $calendar->save();
                   break;
               }
           }
    }

    /**
     * Return the name of the default share.
     *
     * @return string The name of a default share.
     */
    protected function _getDefaultShareName()
    {
        return _("Calendar");
    }

    /**
     * Add any modifiers required to the share in order to mark it as default.
     *
     * @param Horde_Share_Object $share The new default share.
     */
    protected function _prepareDefaultShare($share)
    {
        $share->set('default', true);
    }
}