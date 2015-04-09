<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */

/**
 * Horde_Pgp backend tests.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Pgp
 * @subpackage UnitTests
 */
abstract class Horde_Pgp_Backend_TestBase
extends Horde_Test_Case
{
    private $_pgp;

    /* Returns the list of backends to test. */
    abstract protected function _setUp();

    protected function setUp()
    {
        $this->_pgp = new Horde_Pgp(array(
            'backends' => $this->_setUp()
        ));
    }

    public function testEncrypt()
    {
    }

    public function testEncryptSymmetric()
    {
    }

    public function testSign()
    {
    }

    public function testSignCleartext()
    {
    }

    public function testSignDetached()
    {
    }

    public function testDecrypt()
    {
    }

    public function testDecryptSymmetric()
    {
    }

    public function testVerify()
    {
    }

    public function testVerifyDetached()
    {
    }

    /* Helper methods. */

    protected function _getFixture($file)
    {
        return file_get_contents(dirname(__DIR__) . '/fixtures/' . $file);
    }

    protected function _getPrivateKey()
    {
        return $this->_getFixture('pgp_private.asc');
    }

    protected function _getPublicKey()
    {
        return $this->_getFixture('pgp_public.asc');
    }

}
