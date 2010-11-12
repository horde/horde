<?php

require_once dirname(__FILE__) . '/TestBase.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */
class Horde_Translation_WrapperTest extends Horde_Translation_TestBase
{
    public function testWrappers()
    {
        $this->assertEquals('1 Woche', sprintf(Horde_Translation_TestWrapper::ngettext('%d week', '%d weeks', 1), 1));
        $this->assertEquals('Heute', Horde_Translation_TestWrapper::t('Today'));
    }
}

class Horde_Translation_TestWrapper extends Horde_Translation
{
    static public function t($message)
    {
        self::$_domain = 'Horde_Translation';
        self::$_directory = dirname(__FILE__) . '/locale';
        return parent::t($message);
    }

    static public function ngettext($singular, $plural, $number)
    {
        self::$_domain = 'Horde_Translation';
        self::$_directory = dirname(__FILE__) . '/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}
