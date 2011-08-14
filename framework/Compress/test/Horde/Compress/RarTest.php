<?php
/**
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Compress
 * @subpackage UnitTests
 */
class Horde_Compress_RarTest extends Horde_Test_Case
{
    public function testInvalidRarData()
    {
        $compress = Horde_Compress::factory('Rar');

        try {
            $compress->decompress('1234');
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Exception $e) {}

        try {
            $compress->decompress(Horde_Compress_Rar::BLOCK_START . '1234');
            $this->fail('Expected exception.');
        } catch (Horde_Compress_Exception $e) {}

        $compress->decompress(Horde_Compress_Rar::BLOCK_START . '1234567');
    }

}
