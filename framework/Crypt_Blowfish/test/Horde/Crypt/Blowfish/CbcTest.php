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
        $data = file(dirname(__FILE__) . '/fixtures/vectors_cbc.txt');
        $vectors = array();

        foreach ($data as $val) {
            list($key, $iv, $plain) = explode(' ', trim($val));
            $vectors[] = array(
                array(
                    'key' => pack("H*", $key),
                    'iv' => pack("H*", $iv),
                    'plain' => pack("H*", $plain)
                )
            );
        }

        return $vectors;
    }

    protected function _doTest($v, $ignore)
    {
        $ob = new Horde_Crypt_Blowfish($v['key'], array(
            'cipher' => 'cbc',
            'ignore' => $ignore,
            'iv' => $v['iv']
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
