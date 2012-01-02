<?php
/**
 * Stub for testing the IMAP Socket library.
 * Needed because we need to access protected methods.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */

/**
 * Stub for testing the IMAP Socket library.
 * Needed because we need to access protected methods.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Imap_Client
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Imap_Client
 */
class Horde_Imap_Client_Stub_Socket extends Horde_Imap_Client_Socket
{
    public function getClientSort($data, $sort)
    {
        $this->_temp['fetchresp'] = $this->_newFetchResult();

        $ids = array();

        foreach ($data as $val) {
            if (strlen($val)) {
                $this->_tokenizeData($val);
                $this->_parseFetch($this->_temp['token']->out[1], $this->_temp['token']->out[3]);
                $ids[] = $this->_temp['token']->out[1];
                unset($this->_temp['token']);
            }
        }

        return $this->_clientSort($ids, array(
            'fetch_res' => $this->_temp['fetchresp']->seq,
            'sort' => $sort
        ));
    }
}
