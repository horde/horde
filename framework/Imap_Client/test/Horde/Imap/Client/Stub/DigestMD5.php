<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Stub for testing the Digest MD5 library.
 * Needed because we need to overwrite a protected method.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2011-2016 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
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
