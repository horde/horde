<?php
/**
 * @category Horde
 * @package Horde_View
 * @subpackage UnitTests
 */

require_once dirname(__FILE__) . '/../../../lib/Horde/View/Interface.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/View/Base.php';
require_once dirname(__FILE__) . '/../../../lib/Horde/View.php';

class Horde_View_InterfaceTest extends PHPUnit_Framework_TestCase {

    public function testViewInterface()
    {
        eval('class Test_View extends Horde_View implements Horde_View_Interface {};');
    }

}
