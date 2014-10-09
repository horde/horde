<?php

require_once __DIR__ . '/TestBase.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */
class Horde_Translation_GettextTest extends Horde_Translation_TestBase
{
    private $_dict;
    private $_otherDict;

    public function setUp()
    {
        parent::setUp();
        $this->_dict = new Horde_Translation_Handler_Gettext('Horde_Translation', __DIR__ . '/locale');
        $this->_otherDict = new Horde_Translation_Handler_Gettext('Horde_Other', __DIR__ . '/locale');
    }

    public function testGettext()
    {
        $this->assertEquals('Heute', $this->_dict->t('Today'));
        $this->assertEquals('Schön', $this->_dict->t('Beautiful'));
        $this->assertEquals('2 Tage', sprintf($this->_dict->t('%d days'), 2));
        $this->assertEquals('Morgen', $this->_otherDict->t('Tomorrow'));
    }

    public function testNgettext()
    {
        $this->assertEquals('1 Woche', sprintf($this->_dict->ngettext('%d week', '%d weeks', 1), 1));
        $this->assertEquals('2 Wochen', sprintf($this->_dict->ngettext('%d week', '%d weeks', 2), 2));
    }

    public function testInvalidConstruction()
    {
        try {
            new Horde_Translation_Handler_Gettext('Horde_Translation', __DIR__ . '/DOES_NOT_EXIST');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(
                __DIR__ . '/DOES_NOT_EXIST is not a directory',
                $e->getMessage()
            );
        }
    }
}
