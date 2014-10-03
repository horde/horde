<?php

require_once __DIR__ . '/TestBase.php';
require_once dirname(dirname(__DIR__)) . '/WrapperA/lib/Horde/WrapperA/Translation.php';
require_once dirname(dirname(__DIR__)) . '/WrapperB/lib/Horde/WrapperB/Translation.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */
class Horde_Translation_WrapperTest extends Horde_Translation_TestBase
{
    public function testWrappers()
    {
        $this->assertEquals('Heute', Horde_Translation_TestWrapperA::t('Today'));
        $this->assertEquals('Today', Horde_Translation_TestWrapperA::r('Today'));
        $this->assertEquals('1 Woche', sprintf(Horde_Translation_TestWrapperA::ngettext('%d week', '%d weeks', 1), 1));

        $this->assertEquals('Morgen', Horde_Translation_TestWrapperB::t('Tomorrow'));
        $this->assertEquals('Tomorrow', Horde_Translation_TestWrapperB::r('Tomorrow'));
    }

    public function testAutodetectWrappers()
    {
        $this->assertEquals('Heute', Horde_WrapperA_Translation::t('Today'));
        $this->assertEquals('Today', Horde_WrapperA_Translation::r('Today'));
        $this->assertEquals('1 Woche', sprintf(Horde_WrapperA_Translation::ngettext('%d week', '%d weeks', 1), 1));

        $this->assertEquals('Morgen', Horde_WrapperB_Translation::t('Tomorrow'));
        $this->assertEquals('Tomorrow', Horde_WrapperB_Translation::r('Tomorrow'));
    }
}

class Horde_Translation_TestWrapperA extends Horde_Translation
{
    public static function t($message)
    {
        self::$_domain = 'Horde_WrapperA';
        self::$_directory = dirname(dirname(__DIR__)) . '/WrapperA/locale';
        return parent::t($message);
    }

    public static function ngettext($singular, $plural, $number)
    {
        self::$_domain = 'Horde_WrapperA';
        self::$_directory = dirname(dirname(__DIR__)) . '/WrapperA/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}

class Horde_Translation_TestWrapperB extends Horde_Translation
{
    public static function t($message)
    {
        self::$_domain = 'Horde_WrapperB';
        self::$_directory = dirname(dirname(__DIR__)) . '/WrapperB/locale';
        return parent::t($message);
    }

    public static function ngettext($singular, $plural, $number)
    {
        self::$_domain = 'Horde_WrapperB';
        self::$_directory = dirname(dirname(__DIR__)) . '/WrapperB/locale';
        return parent::ngettext($singular, $plural, $number);
    }
}
