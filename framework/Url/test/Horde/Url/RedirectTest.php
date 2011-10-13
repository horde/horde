<?php
/**
 * @author     Michael Slusarz <slusarz@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Url
 * @subpackage UnitTests
 */

class Horde_Url_RedirectTest extends PHPUnit_Framework_TestCase
{
    public function testEmptyRedirect()
    {
        $url = new Horde_Url('');

        try {
            $url->redirect();
            $this->fail();
        } catch (Horde_Url_Exception $e) {}
    }

}
