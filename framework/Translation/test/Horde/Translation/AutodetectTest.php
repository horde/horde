<?php

require_once __DIR__ . '/TestBase.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */
class Horde_Translation_AutodetectTest extends Horde_Translation_TestBase
{
    public function testAutodetect()
    {
        $this->assertEquals('Heute', Horde_Translation_TestAutodetectA::t('Today'));
        $this->assertEquals('Today', Horde_Translation_TestAutodetectA::r('Today'));
        $this->assertEquals('1 Woche', sprintf(Horde_Translation_TestAutodetectA::ngettext('%d week', '%d weeks', 1), 1));

        $this->assertEquals('Morgen', Horde_Translation_TestAutodetectB::t('Tomorrow'));
        $this->assertEquals('Tomorrow', Horde_Translation_TestAutodetectB::r('Tomorrow'));
    }
}

class Horde_Translation_TestAutodetectA extends Horde_Translation_Autodetect
{
    protected static $_domain = 'Horde_Translation';
    protected static $_pearDirectory = '@data_dir@';

    protected static function _getSearchDirectories()
    {
        return array(
            __DIR__ . '/locale'
        );
    }
}

class Horde_Translation_TestAutodetectB extends Horde_Translation_Autodetect
{
    protected static $_domain = 'Horde_Other';
    protected static $_pearDirectory = '@data_dir@';

    protected static function _getSearchDirectories()
    {
        return array(
            __DIR__ . '/locale'
        );
    }
}
