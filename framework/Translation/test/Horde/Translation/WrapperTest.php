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
        $this->assertEquals('Heute', Horde_Translation_TestWrapper::t('Today'));
        $this->assertEquals('1 Woche', sprintf(Horde_Translation_TestWrapper::ngettext('%d week', '%d weeks', 1), 1));
    }
}

class Horde_Translation_TestWrapper extends Horde_Translation
{
    /**
     * Returns the translation of a message.
     *
     * @var string $message  The string to translate.
     *
     * @return string  The string translation, or the original string if no
     *                 translation exists.
     */
    static public function t($message)
    {
        self::$_domain = 'Horde_Translation';
        self::$_directory = 'locale';
        return parent::t($message);
    }
}
