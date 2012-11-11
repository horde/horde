<?php
/**
 * @category   Horde
 * @package    Crypt_Blowfish
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Crypt_Blowfish
 * @subpackage UnitTests
 */
class Horde_Crypt_Blowfish_EcbTest extends Horde_Test_Case
{
    private $vectors = array();

    public function setUp()
    {
        foreach (file(dirname(__FILE__) . '/fixtures/vectors.txt') as $val) {
            list($key, $plain) = explode(' ', trim($val));
            $this->vectors[] = array(
                'key' => pack("H*", $key),
                'plain' => pack("H*", $plain)
            );
        }
    }

    public function testOpensslDriver()
    {
        if (!Horde_Crypt_Blowfish_Openssl::supported()) {
            $this->markTestSkipped('OpenSSL not installed.');
        } else {
            $this->_doTest(0);
        }
    }

    public function testMcryptDriver()
    {
        if (!Horde_Crypt_Blowfish_Mcrypt::supported()) {
            $this->markTestSkipped();
        } else {
            $this->_doTest(Horde_Crypt_Blowfish::IGNORE_OPENSSL);
        }
    }

    public function testPhpDriver()
    {
        $this->_doTest(Horde_Crypt_Blowfish::IGNORE_OPENSSL | Horde_Crypt_Blowfish::IGNORE_MCRYPT);
    }

    protected function _doTest($ignore)
    {
        $ob = new Horde_Crypt_Blowfish('test', array(
            'cipher' => 'ecb',
            'ignore' => $ignore
        ));

        foreach ($this->vectors as $val) {
            $ob->setKey($val['key']);

            $encrypt = $ob->encrypt($val['plain']);

            // Let's verify some sort of obfuscation occurred.
            $this->assertNotEquals(
                $val['plain'],
                $encrypt
            );

            $this->assertEquals(
                $val['plain'],
                $ob->decrypt($encrypt)
            );
        }
    }

}
