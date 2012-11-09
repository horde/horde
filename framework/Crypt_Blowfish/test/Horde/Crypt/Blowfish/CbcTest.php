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
class Horde_Crypt_Blowfish_CbcTest extends Horde_Test_Case
{
    private $vectors = array();

    public function setUp()
    {
        foreach (file(dirname(__FILE__) . '/fixtures/vectors_cbc.txt') as $val) {
            list($key, $iv, $plain) = explode(' ', trim($val));
            $this->vectors[] = array(
                'key' => pack("H*", $key),
                'iv' => pack("H*", $iv),
                'plain' => pack("H*", $plain)
            );
        }
    }

    public function testOpensslDriver()
    {
        if (!Horde_Crypt_Blowfish_Openssl::supported()) {
            $this->markTestSkipped();
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
        foreach ($this->vectors as $val) {
            $ob = new Horde_Crypt_Blowfish($val['key'], array(
                'cipher' => 'cbc',
                'ignore' => $ignore,
                'iv' => $val['iv']
            ));

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
