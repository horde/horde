<?php
/**
 * @category   Horde
 * @package    Compress_Fast
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Compress_Fast
 * @subpackage UnitTests
 */
class Horde_Compress_Fast_CompressFastTest extends Horde_Test_Case
{
    /**
     * @dataProvider providerTestStringInput
     */
    public function testStringInput($data, $success)
    {
        $ob = new Horde_Compress_Fast(array(
            'drivers' => array(
                'Horde_Compress_Fast_Null'
            )
        ));

        try {
            $ob->compress($data);
            if (!$success) {
                $this->fail('Expected exception.');
            }
        } catch (Horde_Compress_Fast_Exception $e) {
            if ($success) {
                $this->fail('Unexpected exception.');
            }
        }
    }

    public function providerTestStringInput()
    {
        // Format: data, expected success
        return array(
            array('a', true),
            array(0.1, true),
            array(1, true),
            array(true, true),
            array(null, true),
            array(array(), false),
            array(new stdClass, false),
            array(opendir(__DIR__), false)
        );
    }

}
