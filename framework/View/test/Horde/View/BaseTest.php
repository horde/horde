<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_View_BaseTest extends Horde_Test_Case
{
    protected $_view = null;

    public function setUp()
    {
        $this->_view = new Horde_View();
        $this->_view->addTemplatePath(dirname(__FILE__) . '/fixtures/');
    }

    /*##########################################################################
    # Assignment
    ##########################################################################*/

    // test setting/getting dynamic properties
    public function testSet()
    {
        $this->_view->publicVar = 'test';
        $this->assertEquals('test', $this->_view->publicVar);
    }

    public function testAssign()
    {
        $this->_view->assign(array('publicVar' => 'test'));
        $this->assertEquals('test', $this->_view->publicVar);
    }

    public function testAssignDoesntOverridePrivateVariables()
    {
        try {
            $this->_view->assign(array('_templatePath' => 'test'));
        } catch (Exception $e) {
            return;
        }
        $this->fail('Overwriting a private/protected variable should fail');
    }

    public function testAssignAllowsUnderscoreVariables()
    {
        $this->_view->assign(array('_private' => 'test'));
        $this->assertEquals('test', $this->_view->_private);
    }

    // test accessing variable
    public function testAccessVar()
    {
        $this->_view->testVar = 'test';
        $this->assertTrue(!empty($this->_view->testVar));

        $this->_view->testVar2 = '';
        $this->assertTrue(empty($this->_view->testVar2));

        $this->assertTrue(isset($this->_view->testVar2));
        $this->assertTrue(!isset($this->_view->testVar3));
    }

    // test adding a template path
    public function testAddTemplatePath()
    {
        $this->_view->addTemplatePath('app/views/shared/');

        $expected = array('app/views/shared/',
                          dirname(__FILE__) . '/fixtures/',
                          './');
        $this->assertEquals($expected, $this->_view->getTemplatePaths());
    }

    // test adding a template path
    public function testAddTemplatePathAddSlash()
    {
        $this->_view->addTemplatePath('app/views/shared');
        $expected = array('app/views/shared/',
                          dirname(__FILE__) . '/fixtures/',
                          './');
        $this->assertEquals($expected, $this->_view->getTemplatePaths());
    }


    /*##########################################################################
    # Rendering
    ##########################################################################*/

    // test rendering
    public function testRender()
    {
        $this->_view->myVar = 'test';

        $expected = "<div>test</div>";
        $this->assertEquals($expected, $this->_view->render('testRender.html.php'));
    }

    // test rendering
    public function testRenderNoExtension()
    {
        $this->_view->myVar = 'test';

        $expected = "<div>test</div>";
        $this->assertEquals($expected, $this->_view->render('testRender'));
    }

    // test that the
    public function testRenderPathOrder()
    {
        $this->_view->myVar = 'test';

        // we should be rendering the testRender.html in fixtures/
        $expected = "<div>test</div>";
        $this->assertEquals($expected, $this->_view->render('testRender'));

        // after we specify the 'subdir' path, it should read from subdir path first
        $this->_view->addTemplatePath(dirname(__FILE__) . '/fixtures/subdir/');
        $expected = "<div>subdir test</div>";
        $this->assertEquals($expected, $this->_view->render('testRender'));
    }


    /*##########################################################################
    # Partials
    ##########################################################################*/

    // test rendering partial
    public function testRenderPartial()
    {
        $this->_view->myVar1 = 'main';
        $this->_view->myVar2 = 'partial';

        $expected = '<div>main<p>partial</p></div>';
        $this->assertEquals($expected, $this->_view->render('testPartial'));
    }

    // test rendering partial with object passed in
    public function testRenderPartialObject()
    {
        $this->_view->myObject = (object)array('string_value' => 'hello world');
        $expected = '<div><p>hello world</p></div>';
        $this->assertEquals($expected, $this->_view->render('testPartialObject'));
    }

    // test rendering partial with locals passed in
    public function testRenderPartialLocals()
    {
        $expected = '<div><p>hello world</p></div>';
        $this->assertEquals($expected, $this->_view->render('testPartialLocals'));
    }

    // test rendering partial with collection passed in
    public function testRenderPartialCollection()
    {
        $this->_view->myObjects = array((object)array('string_value' => 'hello'),
                                        (object)array('string_value' => 'world'));
        $expected = '<div><p>hello</p><p>world</p></div>';
        $this->assertEquals($expected, $this->_view->render('testPartialCollection'));
    }

    // test rendering partial with empty set as collection
    public function testRenderPartialCollectionEmpty()
    {
        $this->_view->myObjects = null;

        $expected = '<div></div>';
        $this->assertEquals($expected, $this->_view->render('testPartialCollection'));
    }

    // test rendering partial with empty array as collection
    public function testRenderPartialCollectionEmptyArray()
    {
        $this->_view->myObjects = array();

        $expected = '<div></div>';
        $this->assertEquals($expected, $this->_view->render('testPartialCollection'));
    }

    // partial collection is a model collection
    public function testRenderPartialModelCollection()
    {
        $this->_view->myObjects = array((object)array('string_value' => 'name a'), (object)array('string_value' => 'name b'));

        $expected = '<div><p>name a</p><p>name b</p></div>';
        $this->assertEquals($expected, $this->_view->render('testPartialCollection'));
    }


    /*##########################################################################
    # Escape output
    ##########################################################################*/

    public function testEscapeTemplate()
    {
        $this->_view->myVar = '"escaping"';
        $this->_view->addHelper(new Horde_View_Helper_Text($this->_view));

        $expected = "<div>test &quot;escaping&quot; quotes</div>";
        $this->assertEquals($expected, $this->_view->render('testEscape'));
    }

    // test adding a helper
    public function testAddHorde_View_Helper_Text()
    {
        $str = 'The quick brown fox jumps over the lazy dog tomorrow morning.';

        // helper doesn't exist
        try {
            $this->_view->truncateMiddle($str, 40);
        } catch (Exception $e) {}
        $this->assertTrue($e instanceof Horde_View_Exception);

        // add text helper
        $this->_view->addHelper(new Horde_View_Helper_Text($this->_view));
        $expected = 'The quick brown fox... tomorrow morning.';
        $this->assertEquals($expected, $this->_view->truncateMiddle($str, 40));
    }

    // test adding a helper where methods conflict
    public function testAddHorde_View_Helper_TextMethodOverwrite()
    {
        // add text helper
        $this->_view->addHelper(new Horde_View_Helper_Text($this->_view));

        // successful when trying to add it again
        $this->_view->addHelper(new Horde_View_Helper_Text($this->_view));
    }
}
