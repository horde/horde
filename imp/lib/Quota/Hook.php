<?php
/**
 * Implementation of IMP_Quota API for a generic hook function.  This
 * requires hook_get_quota to be set in config/hooks.php .  The
 * function takes an array as argument and returns an array where the
 * first item is the disk space used in bytes and the second the
 * maximum diskspace in bytes.  See there for an example.
 *
 * You must configure this driver in horde/imp/config/servers.php.  The
 * driver supports the following parameters:
 *   'params' => Array of parameters to pass to the quota function.
 *
 * Copyright 2002-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Redinger <Michael.Redinger@uibk.ac.at>
 * @package IMP_Quota
 */
class IMP_Quota_Hook extends IMP_Quota
{
    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws Horde_Exception
     */
    public function getQuota()
    {
        try {
            $quota = Horde::callHook('quota', array($this->_params), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {
            throw new Horde_Exception($e->getMessage());
        }

        if (count($quota) != 2) {
            Horde::logMessage('Incorrect number of return values from quota hook.', __FILE__, __LINE__, PEAR_LOG_ERR);
            throw new Horde_Exception(_("Unable to retrieve quota"), 'horde.error');
        }

        return array('usage' => $quota[0], 'limit' => $quota[1]);
    }

}
