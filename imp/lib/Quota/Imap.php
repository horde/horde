<?php
/**
 * Implementation of the IMP_Quota API for IMAP servers.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Quota_Imap extends IMP_Quota_Base
{
    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     * 'imap_ob' - (Horde_Imap_Client_Base) IMAP client object.
     * 'mbox' - (string) IMAP mailbox to query.
     * </pre>
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
     *                'limit' = Maximum quota allowed
     *                'usage' = Currently used portion of quota (in bytes)
     * @throws IMP_Exception
     */
    public function getQuota()
    {
        try {
            $quota = $this->_params['imap_ob']->getQuotaRoot($this->_params['mbox']);
        } catch (Horde_Imap_Client_Exception $e) {
            throw new IMP_Exception(_("Unable to retrieve quota"));
        }

        if (empty($quota)) {
            return array();
        }

        $quota_val = reset($quota);
        return array(
            'limit' => $quota_val['storage']['limit'] * 1024,
            'usage' => $quota_val['storage']['usage'] * 1024
        );
    }

}
