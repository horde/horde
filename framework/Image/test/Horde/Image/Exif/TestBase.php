<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Image_Exif_TestBase extends Horde_Test_Case
{
    /**
     * @var Horde_Image_Exif_Base
     */
    protected static $_exif = null;

    /**
     * Cache of retrieved EXIF data
     */
    protected static $_data;

    public function setUp()
    {
        if (self::$_exif === null) {
            $this->markTestSkipped('No exif driver');
        }
    }

    public function testTitleFields()
    {
        $fields = Horde_Image_Exif::getTitleFields();
        $this->assertTrue(array_search('ObjectName', $fields) !== false);
        $this->assertTrue(array_search('Title', $fields) !== false);
    }
    public function testDescriptionFields()
    {
        $descFields = Horde_Image_Exif::getDescriptionFields();
        $this->assertTrue(array_search('ImageDescription', $descFields) !== false);
        $this->assertTrue(array_search('Description', $descFields) !== false);
        $this->assertTrue(array_search('Caption-Abstract', $descFields) !== false);
    }

    /**
     * Tests ability to extract EXIF data without errors. Does not test data
     * for validity.
     */
    public function testExtract()
    {
        $fixture = __DIR__ . '/../Fixtures/img_exif.jpg';
        setlocale(LC_ALL, 'de_DE');
        self::$_data = self::$_exif->getData($fixture);
        $this->assertInternalType('array', self::$_data);
    }

    /**
     * @depends testExtract
     */
    public function testKeywordIsString()
    {
        $this->_testKeywordIsString();
    }

    /**
     * @depends testExtract
     */
    public function testKeywords()
    {
        $this->_testKeywords();
    }

    /**
     * @depends testExtract
     */
    public function testGPS()
    {
        $lat = self::$_data['GPSLatitude'];
        $lon = self::$_data['GPSLongitude'];
        $this->assertEquals(44.3535, $lat);
        $this->assertEquals(68.223, $lon);
    }

    protected function _testKeywords()
    {
        $this->markTestSkipped('Keyword field not supported by driver');
    }

    protected function _testKeywordIsString()
    {
        $this->markTestSkipped('Keyword field not supported by driver');
    }

}
