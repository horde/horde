<?php
/**
 * Tests for PGP armor parsing.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Crypt
 * @subpackage UnitTests
 */
class Horde_Crypt_PgpParseTest extends Horde_Test_Case
{
    protected $_pgp;

    protected function setUp()
    {
        $this->_pgp = new Horde_Crypt_Pgp();
    }

    /**
     * @dataProvider parsePgpDataProvider
     */
    public function testParsePgpData($data)
    {
        $out = $this->_pgp->parsePGPData($data);

        $this->assertEquals(
            2,
            count($out)
        );

        $this->assertEquals(
            Horde_Crypt_Pgp::ARMOR_SIGNED_MESSAGE,
            $out[0]['type']
        );
        $this->assertEquals(
            17,
            count($out[0]['data'])
        );

        $this->assertEquals(
            Horde_Crypt_Pgp::ARMOR_SIGNATURE,
            $out[1]['type']
        );
        $this->assertEquals(
            7,
            count($out[1]['data'])
        );
    }

    public function parsePgpDataProvider()
    {
        $data = file_get_contents(__DIR__ . '/fixtures/pgp_signed.txt');

        $stream = new Horde_Stream_Temp();
        $stream->add($data, true);

        return array(
            array($data),
            array($stream)
        );
    }

}
