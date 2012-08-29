<?php
/**
 * Implementation of the IMP_Quota API for IMAP servers.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Quota_Imap extends IMP_Quota
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     *   - imap_ob: (Horde_Imap_Client_Base) IMAP client object.
     *   - mbox: (string) IMAP mailbox to query.
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $params = array())
    {
        foreach (array('imap_ob', 'mbox') as $val) {
            if (!isset($params[$val])) {
                throw new InvalidArgumentException('Missing ' . $val . ' parameter');
            }
        }

        parent::__construct($params);
    }

    /**
     * Get quota information (used/allocated), in bytes.
     *
     * @return array  An array with the following keys:
     *   - limit: Maximum quota allowed
     *   - usage: Currently used portion of quota (in bytes)
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        try {
            $quota = $this->_params['imap_ob']->getQuotaRoot($this->_params['mbox']);
        } catch (IMP_Imap_Exception $e) {
            throw new IMP_Exception(_("Unable to retrieve quota"));
        }

        $quota_val = reset($quota);

        return isset($quota_val['storage'])
            ? array(
                  'limit' => $quota_val['storage']['limit'] * 1024,
                  'usage' => $quota_val['storage']['usage'] * 1024
              )
            : array(
                'limit' => 0,
                'usage' => 0
              );
    }

}
