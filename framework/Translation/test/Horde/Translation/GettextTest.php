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
    private $_dictA;
    private $_dictB;

    public function setUp()
    {
        parent::setUp();
        $baseDir = dirname(dirname(__DIR__));
        $this->_dictA = new Horde_Translation_Handler_Gettext('Horde_WrapperA', $baseDir . '/WrapperA/locale');
        $this->_dictB = new Horde_Translation_Handler_Gettext('Horde_WrapperB', $baseDir . '/WrapperB/locale');
    }

    public function testGettext()
    {
        $this->assertEquals('Heute', $this->_dictA->t('Today'));
        $this->assertEquals('Schön', $this->_dictA->t('Beautiful'));
        $this->assertEquals('2 Tage', sprintf($this->_dictA->t('%d days'), 2));
        $this->assertEquals('Morgen', $this->_dictB->t('Tomorrow'));
    }

    public function testNgettext()
    {
        $this->assertEquals('1 Woche', sprintf($this->_dictA->ngettext('%d week', '%d weeks', 1), 1));
        $this->assertEquals('2 Wochen', sprintf($this->_dictA->ngettext('%d week', '%d weeks', 2), 2));
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
