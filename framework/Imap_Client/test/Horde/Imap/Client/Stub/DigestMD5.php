<?php
/**
 * Stub for testing the Digest MD5 library.
 * Needed because we need to overwrite a protected method.
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
 * Stub for testing the Digest MD5 library.
 * Needed because we need to overwrite a protected method.
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
class Horde_Imap_Client_Stub_Auth_DigestMD5 extends Horde_Imap_Client_Auth_DigestMD5
{
    /**
     * Cnonce to use.
     *
     * @var string
     */
    protected $_cnonce;

    /**
     */
    public function __construct($id, $pass, $challenge, $hostname, $service,
                                $cnonce)
    {
        $this->_cnonce = $cnonce;
        parent::__construct($id, $pass, $challenge, $hostname, $service);
    }

    /**
     */
    protected function _getCnonce()
    {
        return $this->_cnonce;
    }

}
