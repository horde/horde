<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */

/**
 * @group      view
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage UnitTests
 */
class Horde_View_Helper_JavascriptTest extends Horde_Test_Case
{
    public function setUp()
    {
        $this->view = new Horde_View();
        $this->view->addHelper('Javascript');
        $this->view->addHelper('Tag');
    }

    public function testJavascriptTag()
    {
        $this->assertEquals("<script type=\"text/javascript\">\n//<![CDATA[\nfoo = 1;\n//]]>\n</script>",
                            $this->view->javascriptTag('foo = 1;'));
    }

}
