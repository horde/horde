<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

class Horde_Translation_GettextTest extends PHPUnit_Framework_TestCase
{
    private $_dict;
    private $_otherDict;
    private $_env;

    public function setUp()
    {
        try {
            $this->setLocale(LC_ALL, 'de_DE.UTF-8');
        } catch (PHPUnit_Framework_Exception $e) {
            $this->markTestSkipped('Setting the locale failed. de_DE.UTF-8 might not be supported.');
        }
        $this->_setEnv('de_DE.UTF-8');
        $this->_dict = new Horde_Translation_Gettext('Horde_Translation', dirname(__FILE__) . '/locale');
        $this->_otherDict = new Horde_Translation_Gettext('Horde_Other', dirname(__FILE__) . '/locale');
    }

    public function tearDown()
    {
        $this->_restoreEnv();
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

    private function _setEnv($value)
    {
        foreach (array('LC_ALL', 'LANG', 'LANGUAGE') as $env) {
            $this->_env[$env] = getenv($env);
            putenv($env . '=' . $value);
        }
    }

    private function _restoreEnv()
    {
        foreach (array('LC_ALL', 'LANG', 'LANGUAGE') as $env) {
            putenv($env . '=' . $this->_env[$env]);
        }
    }
}
