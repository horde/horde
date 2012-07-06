<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Data
 * @subpackage UnitTests
 */

class Horde_Data_CsvTest extends PHPUnit_Framework_TestCase
{
    public function testImportFile()
    {
        $data = new Horde_Data_Csv(new Horde_Data_Storage_Mock());

        $expected = array(array(0 => 'one',
                                1 => 'two',
                                2 => 'three four',
                                3 => 'five'),
                          array(0 => 'six',
                                1 => 'seven',
                                2 => 'eight nine',
                                3 => 'ten'));
        $this->assertEquals($expected,
                            $data->importFile(__DIR__ . '/fixtures/simple_dos.csv', false, ',', '', 4));
        $this->assertEquals($expected,
                            $data->importFile(__DIR__ . '/fixtures/simple_unix.csv', false, ',', '', 4));

        $expected = array(array('one' => 'six',
                                'two' => 'seven',
                                'three four' => 'eight nine',
                                'five' => 'ten'));
        $this->assertEquals($expected,
                            $data->importFile(__DIR__ . '/fixtures/simple_dos.csv', true, ',', '', 4));
        $this->assertEquals($expected,
                            $data->importFile(__DIR__ . '/fixtures/simple_unix.csv', true, ',', '', 4));

        $expected = array(array('one' => 'four',
                                'two' => 'five"six',
                                'three' => 'seven
"eight"',''=>''));
        $this->assertEquals($expected,
                            $data->importFile(__DIR__ . '/fixtures/quotes1.csv', true, ',', '"', 4));
        $this->assertEquals($expected,
                            $data->importFile(__DIR__ . '/fixtures/quotes2.csv', true, ',', '"', 4));
    }
}
