<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Translation
 * @subpackage UnitTests
 */
class Horde_Translation_Composer_TestAutodetect extends Horde_Translation_Autodetect
{
    protected static $_domain = 'Horde_Translation_Composer';
    protected static $_pearDirectory = '/non/existant/directory';
}
