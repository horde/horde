<?php
/**
 * Implementation of IMP_Quota API for a generic hook function.  This
 * requires the quota hook to be set in config/hooks.php.
 *
 * You must configure this driver in imp/config/backends.php.  The driver
 * supports the following parameters:
 *   - params: (array) Parameters to pass to the quota function.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Redinger <Michael.Redinger@uibk.ac.at>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Quota_Hook extends IMP_Quota
{
    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        try {
            $quota = Horde::callHook('quota', array($this->_params), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {
            throw new IMP_Exception($e->getMessage());
        }

        if (count($quota) != 2) {
            Horde::log('Incorrect number of return values from quota hook.', 'ERR');
            throw new IMP_Exception(_("Unable to retrieve quota"));
        }

        return array(
            'limit' => $quota[1],
            'usage' => $quota[0]
        );
    }

}
