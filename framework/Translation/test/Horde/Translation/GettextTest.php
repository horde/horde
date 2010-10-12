<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */

class Horde_Translation_GettextTest extends PHPUnit_Framework_TestCase
{
    private $_dict;
    private $_otherDict;

    public function setUp()
    {
        putenv('LC_ALL=de_DE.UTF-8');
        putenv('LANG=de_DE.UTF-8');
        putenv('LANGUAGE=de_DE.UTF-8');
        $this->_dict = new Horde_Translation_Gettext('Horde_Translation', dirname(__FILE__) . '/locale');
        $this->_otherDict = new Horde_Translation_Gettext('Horde_Other', dirname(__FILE__) . '/locale');
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
}
