<?php
/**
 * Tests for the PGP PECL backend.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2015 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt
 * @subpackage UnitTests
 */
class Horde_Crypt_Pgp_PeclTest
extends Horde_Crypt_Pgp_TestBase
{
    protected function _setUp()
    {
        if (!Horde_Crypt_Pgp_Backend_Binary::supported()) {
            $this->markTestSkipped('gnupg PECL extension not available');
        }

        return array(new Horde_Crypt_Pgp_Backend_Pecl());
    }

    public function testBug6601()
    {
        $this->markTestIncomplete();
    }

    public function testPgpDecrypt()
    {
        $this->markTestIncomplete();
    }

    public function testPgpDecryptSymmetric()
    {
        $this->markTestIncomplete();
    }

    public function testPgpEncrypt()
    {
        $this->markTestIncomplete();
    }

    public function testPgpEncryptSymmetric()
    {
        $this->markTestIncomplete();
    }

    public function testPgpEncryptedSymmetrically()
    {
        $this->markTestIncomplete();
    }

    public function testParsePGPData()
    {
        $this->markTestIncomplete();
    }

    public function testPgpPacketInformation()
    {
        $this->markTestIncomplete();
    }

    public function testPgpPacketSignature()
    {
        $this->markTestIncomplete();
    }

    public function testPgpPacketSignatureByUidIndex()
    {
        $this->markTestIncomplete();
    }

    public function testPgpPrettyKey()
    {
        $this->markTestIncomplete();
    }

    public function pgpPublicKeyMIMEPart()
    {
        $this->markTestIncomplete();
    }

    public function testPgpSign()
    {
        $this->markTestIncomplete();
    }

    public function testDecryptSignature()
    {
        $this->markTestIncomplete();
    }

    public function testVerifyPassphrase()
    {
        $this->markTestIncomplete();
    }

    public function testGetPublicKeyFromPrivateKey()
    {
        $this->markTestIncomplete();
    }

}
