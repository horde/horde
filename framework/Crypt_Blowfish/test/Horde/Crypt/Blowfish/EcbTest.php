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
    /**
     * @dataProvider vectorProvider
     */
    public function testOpensslDriver($vector)
    {
        if (!Horde_Crypt_Blowfish_Openssl::supported()) {
            $this->markTestSkipped();
        }

        $this->_doTest($vector, 0);
    }

    /**
     * @dataProvider vectorProvider
     */
    public function testMcryptDriver($vector)
    {
        if (!Horde_Crypt_Blowfish_Mcrypt::supported()) {
            $this->markTestSkipped();
        }

        $this->_doTest($vector, Horde_Crypt_Blowfish::IGNORE_OPENSSL);
    }

    /**
     * @dataProvider vectorProvider
     */
    public function testPhpDriver($vector)
    {
        $this->_doTest(
            $vector,
            Horde_Crypt_Blowfish::IGNORE_OPENSSL |
            Horde_Crypt_Blowfish::IGNORE_MCRYPT
        );
    }

    public function vectorProvider()
    {
        $data = file(dirname(__FILE__) . '/fixtures/vectors.txt');
        $vectors = array();

        foreach ($data as $val) {
            list($key, $plain) = explode(' ', trim($val));
            $vectors[] = array(
                array(
                    'key' => pack("H*", $key),
                    'plain' => pack("H*", $plain)
                )
            );
        }

        return $vectors;
    }

    protected function _doTest($v, $ignore)
    {
        $ob = new Horde_Crypt_Blowfish($v['key'], array(
            'cipher' => 'ecb',
            'ignore' => $ignore
        ));

        $encrypt = $ob->encrypt($v['plain']);

        // Let's verify some sort of obfuscation occurred.
        $this->assertNotEquals(
            $v['plain'],
            $encrypt
        );

        $this->assertEquals(
            $v['plain'],
            $ob->decrypt($encrypt)
        );
    }

}
