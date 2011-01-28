<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_View_Helper_FormTagTest extends Horde_Test_Functional
{
    public function setUp()
    {
        $this->view = new Horde_View();
        $this->view->addHelper('FormTag');
        $this->view->addHelper('Tag');
        $this->view->addHelper(new Horde_View_Helper_FormTagTest_MockUrlHelper($this->view));
    }

    public function testFormTag()
    {
        $actual   = $this->view->formTag();
        $expected = '<form action="http://www.example.com" method="post">';
        $this->assertEquals($expected, $actual);
    }

    public function testFormTagWithExplicitUrl()
    {
        $actual   = $this->view->formTag('/controller/action');
        $expected = '<form action="/controller/action" method="post">';
        $this->assertEquals($expected, $actual);
    }

    public function testFormTagMultipart()
    {
        $actual   = $this->view->formTag(array(), array('multipart' => true));
        $expected = '<form action="http://www.example.com" enctype="multipart/form-data" method="post">';
        $this->assertEquals($expected, $actual);
    }

    public function testFormTagWithMethod()
    {
        $actual   = $this->view->formTag(array(), array('method' => 'put'));
        $expected = '<form action="http://www.example.com" method="post"><div style="margin:0;padding:0"><input name="_method" type="hidden" value="put"></div>';
        $this->assertEquals($expected, $actual);
    }

    public function testCheckBoxTag()
    {
        $actual   = $this->view->checkBoxTag('admin');
        $expected = '<input id="admin" name="admin" type="checkbox" value="1">';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testHiddenFieldTag()
    {
        $actual   = $this->view->hiddenFieldTag('id', 3);
        $expected = '<input id="id" name="id" type="hidden" value="3">';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testFileFieldTag()
    {
        $actual   = $this->view->fileFieldTag('id');
        $expected = '<input id="id" name="id" type="file">';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testPasswordFieldTag()
    {
        $actual   = $this->view->passwordFieldTag();
        $expected = '<input id="password" name="password" type="password">';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testRadioButtonTag()
    {
        $actual   = $this->view->radioButtonTag('people', 'david');
        $expected = '<input id="people_david" name="people" type="radio" value="david">';
        $this->assertHtmlDomEquals($expected, $actual);

        $actual   = $this->view->radioButtonTag('num_people', 5);
        $expected = '<input id="num_people_5" name="num_people" type="radio" value="5">';
        $this->assertHtmlDomEquals($expected, $actual);

        $actual   = $this->view->radioButtonTag('gender', 'm')
                  . $this->view->radioButtonTag('gender', 'f');
        $expected = '<input id="gender_m" name="gender" type="radio" value="m">'
                  . '<input id="gender_f" name="gender" type="radio" value="f">';
        $this->assertEquals($expected, $actual); // @todo assertHtmlDomEquals

        $actual   = $this->view->radioButtonTag('opinion', '-1')
                  . $this->view->radioButtonTag('opinion', '1');
        $expected = '<input id="opinion_-1" name="opinion" type="radio" value="-1">'
                  . '<input id="opinion_1" name="opinion" type="radio" value="1">';
        $this->assertEquals($expected, $actual); // @todo assertHtmlDomEquals
    }

    public function testSelectTag()
    {
        $actual   = $this->view->selectTag('people', '<option>david</option>');
        $expected = '<select id="people" name="people"><option>david</option></select>';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testTextAreaTagSizeString()
    {
        $actual   = $this->view->textAreaTag('body', 'hello world', array('size' => '20x40'));
        $expected = '<textarea cols="20" id="body" name="body" rows="40">hello world</textarea>';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testTextAreaTagShouldDisregardSizeIfGivenAsAnInteger()
    {
        $actual   = $this->view->textAreaTag('body', 'hello world', array('size' => 20));
        $expected = '<textarea id="body" name="body">hello world</textarea>';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testTextFieldTag()
    {
        $actual   = $this->view->textFieldTag('title', 'Hello!');
        $expected = '<input id="title" name="title" type="text" value="Hello!">';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testTextFieldTagClassString()
    {
        $actual   = $this->view->textFieldTag('title', 'Hello!', array('class' => 'admin'));
        $expected = '<input class="admin" id="title" name="title" type="text" value="Hello!">';
        $this->assertHtmlDomEquals($expected, $actual);
    }

    public function testBooleanOptions()
    {
        $this->assertHtmlDomEquals('<input checked="checked" disabled="disabled" id="admin" name="admin" readonly="readonly" type="checkbox" value="1">',
                               $this->view->checkBoxTag("admin", 1, true, array('disabled' => true, 'readonly' => "yes")));

        $this->assertHtmlDomEquals('<input checked="checked" id="admin" name="admin" type="checkbox" value="1">',
                               $this->view->checkBoxTag('admin', 1, true, array('disabled' => false, 'readonly' => null)));

        $this->assertHtmlDomEquals('<select id="people" multiple="multiple" name="people"><option>david</option></select>',
                               $this->view->selectTag('people', '<option>david</option>', array('multiple' => true)));

        $this->assertHtmlDomEquals('<select id="people" name="people"><option>david</option></select>',
                               $this->view->selectTag('people', '<option>david</option>', array('multiple' => null)));
    }

    public function testSubmitTag()
    {
        $expected = '<input name="commit" onclick="this.setAttribute(\'originalValue\', this.value);this.disabled=true;this.value=\'Saving...\';alert(\'hello!\');result = (this.form.onsubmit ? (this.form.onsubmit() ? this.form.submit() : false) : this.form.submit());if (result == false) { this.value = this.getAttribute(\'originalValue\'); this.disabled = false };return result" type="submit" value="Save">';
        $actual   = $this->view->submitTag('Save', array('disableWith' => 'Saving...', 'onclick' => "alert('hello!')"));
        $this->assertHtmlDomEquals($expected, $actual);
    }

}

class Horde_View_Helper_FormTagTest_MockUrlHelper extends Horde_View_Helper_Url
{
    public function urlFor($first = array(), $second = array())
    {
        return $first ? parent::urlFor($first, $second) : 'http://www.example.com';
    }
}
