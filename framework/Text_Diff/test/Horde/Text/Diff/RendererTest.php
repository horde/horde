<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/gpl GPL
 * @category   Horde
 * @package    Text_Diff
 * @subpackage UnitTests
 */
class Horde_Text_Diff_RendererTest extends Horde_Test_Case
{
    protected $_lines = array();

    public function setUp()
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->_lines[$i] = file(__DIR__ . '/fixtures/' . $i . '.txt');
        }
    }

    public function testContextRenderer()
    {
        $renderer = new Horde_Text_Diff_Renderer_Context();

        $diff = new Horde_Text_Diff('Native', array($this->_lines[1], $this->_lines[2]));
        $patch = <<<END_OF_PATCH
***************
*** 1,3 ****
  This line is the same.
! This line is different in 1.txt
  This line is the same.
--- 1,3 ----
  This line is the same.
! This line is different in 2.txt
  This line is the same.

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));

        $diff = new Horde_Text_Diff('Native', array($this->_lines[5], $this->_lines[6]));
        $patch = <<<END_OF_PATCH
***************
*** 1,5 ****
  This is a test.
  Adding random text to simulate files.
  Various Content.
! More Content.
! Testing diff and renderer.
--- 1,7 ----
  This is a test.
  Adding random text to simulate files.
+ Inserting a line.
  Various Content.
! Replacing content.
! Testing similarities and renderer.
! Append content.

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testInlineRenderer()
    {
        $diff = new Horde_Text_Diff('Native', array($this->_lines[1], $this->_lines[2]));

        $renderer = new Horde_Text_Diff_Renderer_Inline(array('split_characters' => true));
        $patch = <<<END_OF_PATCH
This line is the same.
This line is different in <del>1</del><ins>2</ins>.txt
This line is the same.

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));

        $renderer = new Horde_Text_Diff_Renderer_Inline();
        $patch = <<<END_OF_PATCH
This line is the same.
This line is different in <del>1.txt</del><ins>2.txt</ins>
This line is the same.

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));

        $diff = new Horde_Text_Diff('Native', array($this->_lines[7], $this->_lines[8]));
        $patch = <<<END_OF_PATCH
This is a test.
Adding random text to simulate files.
<ins>Inserting a line.</ins>
Various Content.
<del>More Content.</del><ins>Replacing content.</ins>
Testing <del>diff</del><ins>similarities</ins> and renderer.<ins>
Append content.</ins>

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testUnifiedRenderer()
    {
        $renderer = new Horde_Text_Diff_Renderer_Unified();

        $diff = new Horde_Text_Diff('Native', array($this->_lines[1], $this->_lines[2]));
        $patch = <<<END_OF_PATCH
@@ -1,3 +1,3 @@
 This line is the same.
-This line is different in 1.txt
+This line is different in 2.txt
 This line is the same.

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));

        $diff = new Horde_Text_Diff('Native', array($this->_lines[5], $this->_lines[6]));
        $patch = <<<END_OF_PATCH
@@ -1,5 +1,7 @@
 This is a test.
 Adding random text to simulate files.
+Inserting a line.
 Various Content.
-More Content.
-Testing diff and renderer.
+Replacing content.
+Testing similarities and renderer.
+Append content.

END_OF_PATCH;
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testPearBug4879()
    {
        /* inline renderer hangs on numbers in input string */
        $test = array(array('Common text',
                            'Bob had 1 apple, Alice had 2.',
                            'Bon appetit!'),
                      array('Common text',
                            'Bob had 10 apples, Alice had 1.',
                            'Bon appetit!'));
        $patch = <<<END_OF_PATCH
Common text
Bob had <del>1 apple,</del><ins>10 apples,</ins> Alice had <del>2.</del><ins>1.</ins>
Bon appetit!

END_OF_PATCH;

        $diff = new Horde_Text_Diff('Native', $test);
        $renderer = new Horde_Text_Diff_Renderer_Inline();
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testPearBug4982()
    {
        $this->markTestIncomplete('Still needs to be fixed.');
        /* wrong line breaks with inline renderer */
        $test = array(array('This line is different in 1.txt'),
                      array('This is new !!',
                            'This line is different in 2.txt'));
        $patch = <<<END_OF_PATCH
<ins>This is new !!</ins>
This line is different in <del>1.txt</del><ins>2.txt</ins>

END_OF_PATCH;

        $diff = new Horde_Text_Diff('Native', $test);
        $renderer = new Horde_Text_Diff_Renderer_Inline();
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testPearBug6251()
    {
        /* too much trailing context */
        $oldtext = <<<EOT

Original Text



ss
ttt
EOT;

        $newtext = <<<EOT

Modified Text



ss
ttt
EOT;

        $patch = "@@ -1,5 +1,5 @@\n \n-Original Text\n+Modified Text\n \n \n \n";

        $test = array(explode("\n", $oldtext), explode("\n", $newtext));
        $diff = new Horde_Text_Diff('Native', $test);
        $renderer = new Horde_Text_Diff_Renderer_Unified(array('leading_context_lines' => 3, 'trailing_context_lines' => 3));
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testPearBug6428()
    {
        /* problem with single digits after space */
        $test = array(array('Line 1',  'Another line'),
                      array('Line  1', 'Another line'));
        $patch = <<<END_OF_PATCH
Line <del>1</del><ins> 1</ins>
Another line

END_OF_PATCH;

        $diff = new Horde_Text_Diff('Native', $test);
        $renderer = new Horde_Text_Diff_Renderer_Inline();
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testPearBug7839()
    {
        $oldtext = <<<EOT
This is line 1.
This is line 2.
This is line 3.
This is line 4.
This is line 5.
This is line 6.
This is line 7.
This is line 8.
This is line 9.
EOT;

        $newtext = <<<EOT
This is line 1.
This was line 2.
This is line 3.
This is line 5.
This was line 6.
This was line 7.
This was line 8.
This is line 9.
This is line 10.
EOT;

        $patch = <<<END_OF_PATCH
2c2
< This is line 2.
---
> This was line 2.
4d3
< This is line 4.
6,8c5,7
< This is line 6.
< This is line 7.
< This is line 8.
---
> This was line 6.
> This was line 7.
> This was line 8.
9a9
> This is line 10.

END_OF_PATCH;

        $test = array(explode("\n", $oldtext), explode("\n", $newtext));
        $diff = new Horde_Text_Diff('Native', $test);
        $renderer = new Horde_Text_Diff_Renderer();
        $this->assertEquals($patch, $renderer->render($diff));
    }

    public function testPearBug12710()
    {
        /* failed assertion */
        $a = <<<QQ
<li>The tax credit amounts to 30% of the cost of the system, with a
maximum of 2,000. This credit is separate from the 500 home improvement
credit.</li>
<h3>Fuel Cells<a
href="12341234213421341234123412341234123421341234213412342134213423"
class="anchor" title="Link to this section"><br />
<li>Your fuel 123456789</li>
QQ;

        $b = <<<QQ
<li> of gas emissions by 2050</li>
<li>Raise car fuel economy to 50 mpg by 2017</li>
<li>Increase access to mass transit systems</li>
QQ;

        $diff = new Horde_Text_Diff('Native', array(explode("\n", $b), explode("\n", $a)));
        $renderer = new Horde_Text_Diff_Renderer_Inline();
        $renderer->render($diff);
    }
    
    public function testGithubPullRequest86() 
    {
        $a = <<<EOA
One
Two
EOA;
                
        $b = <<<EOB
Ones
Twos
EOB;
        $patch = <<<EOPATCH
One<ins>s</ins>
Two<ins>s</ins>

EOPATCH;
    
        $diff = new Horde_Text_Diff('Native', array(explode("\n", $a), explode("\n", $b)));
        $renderer = new Horde_Text_Diff_Renderer_Inline(array('split_characters' => true));
        $this->assertEquals($patch, $renderer->render($diff));
    }
}
