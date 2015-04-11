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

    public function testGenerateKey()
    {
        $key = $this->_pgp->generateKey(
            'Foo',
            'foo@example.com',
            array(
                'comment' => 'Sample Comment',
                'expire' => time() + 60,
                'keylength' => 512
            )
        );

        $this->assertInstanceOf(
            'Horde_Pgp_Element_PrivateKey',
            $key
        );

        $this->assertTrue($key->containsEmail('foo@example.com'));
    }

    public function testEncrypt()
    {
    }

    /**
     * @depends testDecryptSymmetric
     */
    public function testEncryptSymmetric()
    {
        $result = $this->_pgp->encryptSymmetric(
            $this->_getFixture('clear.txt'),
            'Secret'
        );

        $this->assertInstanceOf('Horde_Pgp_Element_Message', $result);
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
        $encrypted = $this->_getFixture('pgp_encrypted_symmetric.txt');

        /* Invalid passphrase. */
        $result = $this->_pgp->decryptSymmetric(
            $encrypted,
            'Incorrect Secret'
        );

        $this->assertEmpty($result);

        /* Valid passphrase. */
        $result = $this->_pgp->decryptSymmetric(
            $encrypted,
            'Secret'
        );

        $this->assertEquals(
            1,
            count($result)
        );

        $text = $result[0][0];

        $this->assertEquals(
            $this->_getFixture('clear.txt'),
            $text->data
        );
        $this->assertEquals(
            'clear.txt',
            $text->filename
        );
        $this->assertEquals(
            1155564523,
            $text->timestamp
        );
        $this->assertEquals(
            657,
            $text->size
        );
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
