<?php
/**
 * The Kolab specific notepads handler.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Mnemo
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */

/**
 * The Kolab specific notepads handler.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category Horde
 * @package  Mnemo
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/apache
 * @link     http://www.horde.org/apps/mnemo
 */
class Mnemo_Notepads_Kolab extends Mnemo_Notepads_Base
{
    /**
     * Runs any actions after setting a new default notepad.
     *
     * @param string $share  The default share ID.
     */
    public function setDefaultShare($share)
    {
           $notepads = $this->_shares
               ->listShares(
                   $this->_user,
                   array('perm' => Horde_Perms::SHOW,
                         'attributes' => $this->_user));
           foreach ($notepads as $id => $notepad) {
               if ($id == $share) {
                   $notepad->set('default', true);
                   $notepad->save();
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
        return _("Notes");
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