<?php
/**
 * Test the restructured text renderer.
 *
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/gpl
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Test the restructured text renderer.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Wicked_Unit_RstTest extends Wicked_TestCase
{
    protected function _getWiki()
    {
        $wiki = new Text_Wiki_Default();
        $wiki->insertRule('Heading2', 'Heading');
        $wiki->deleteRule('Heading');
        $wiki->loadParseObj('Paragraph');
        $skip = $wiki->parseObj['Paragraph']->getConf('skip');
        $skip[] = 'heading2';
        $wiki->setParseConf('Paragraph', 'skip', $skip);
        $wiki->insertRule('Toc2', 'Toc');
        $wiki->deleteRule('Toc');
        return $wiki;
    }

    public function testEmpty()
    {
        $this->assertEquals(
            '',
            $this->protectAgainstPearError($this->_getWiki()->transform('', 'Rst'))
        );
    }

    public function testHeaderOne()
    {
        $this->assertEquals(
            '========
 HEADER
========

',
            $this->protectAgainstPearError($this->_getWiki()->transform('+ HEADER', 'Rst'))
        );
    }

    public function testHeaderTwo()
    {
        $this->assertEquals(
            '--------
 HEADER
--------

',
            $this->protectAgainstPearError($this->_getWiki()->transform('++HEADER', 'Rst'))
        );
    }

    public function testHeaderThree()
    {
        $this->assertEquals(
            'HEADER
======

',
            $this->protectAgainstPearError($this->_getWiki()->transform('+++HEADER', 'Rst'))
        );
    }

    public function testHeaderFour()
    {
        $this->assertEquals(
            'HEADER
******

',
            $this->protectAgainstPearError($this->_getWiki()->transform('++++HEADER', 'Rst'))
        );
    }

    public function testHeaderFive()
    {
        $this->assertEquals(
            'HEADER
------

',
            $this->protectAgainstPearError($this->_getWiki()->transform('+++++HEADER', 'Rst'))
        );
    }

    public function testHeaderSix()
    {
        $this->assertEquals(
            'HEADER
``````

',
            $this->protectAgainstPearError($this->_getWiki()->transform('++++++HEADER', 'Rst'))
        );
    }

    public function testToc()
    {
        $this->assertEquals(
            '.. contents:: Contents
.. section-numbering::

===
 H
===

H4
**

===
 G
===

H6
``

',
            $this->protectAgainstPearError($this->_getWiki()->transform('
[[toc]]

+H

++++H4

+G

++++++H6', 'Rst'))
        );
    }

    public function testPlainUrl()
    {
        $this->assertEquals(
            'Further information on Horde and the latest version can be obtained at

``  ``http://www.horde.org/apps/horde

',
            $this->protectAgainstPearError($this->_getWiki()->transform('Further information on Horde and the latest version can be obtained at

{{  }}http://www.horde.org/apps/horde

', 'Rst'))
        );
    }

    public function testPlainEmbeddedUrl()
    {
        $this->assertEquals(
            'There
is a list of Horde applications and projects at http://www.horde.org/apps.

',
            $this->protectAgainstPearError($this->_getWiki()->transform('There
is a list of Horde applications and projects at http://www.horde.org/apps.

', 'Rst'))
        );
    }

    public function testNamedUrl()
    {
        $this->assertEquals(
            'certification mark of the `Open Source Initiative`_.

.. _`Open Source Initiative`: http://www.opensource.org/

',
            $this->protectAgainstPearError($this->_getWiki()->transform('certification mark of the [http://www.opensource.org/ Open Source Initiative].
', 'Rst'))
        );
    }

    public function testLiteral()
    {
        $this->assertEquals(
            'in the ``docs/`` directory

',
            $this->protectAgainstPearError($this->_getWiki()->transform('in the ``docs/`` directory', 'Rst'))
        );
    }

    public function testFreelink()
    {
        $this->assertEquals(
            'The following documentation is available in the Horde distribution:

:`COPYING`_:      Copyright and license information
:`docs/CHANGES`_: Changes by release



.. _`COPYING`: http://www.horde.org/licenses/lgpl
.. _`docs/CHANGES`: CHANGES',
            $this->protectAgainstPearError($this->_getWiki()->transform('The following documentation is available in the Horde distribution:

: [http://www.horde.org/licenses/lgpl COPYING] : Copyright and license information
: ((CHANGES|docs/CHANGES)) : Changes by release
', 'Rst'))
        );
    }

    public function testCode()
    {
        $this->assertEquals(
            '::

 test

',
            $this->protectAgainstPearError($this->_getWiki()->transform('
<code>
test
</code>
', 'Rst'))
        );
    }

    public function testItalic()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '*italic*

',
            $this->protectAgainstPearError($wiki->transform("''italic''", 'Rst'))
        );
    }

    public function testBold()
    {
        $this->assertEquals(
            '**bold**

',
            $this->protectAgainstPearError($this->_getWiki()->transform("'''bold'''", 'Rst'))
        );
    }

    public function testDeflist()
    {
        $this->assertEquals(
            ':The term:     A definition
:Another term: Another definition

',
            $this->protectAgainstPearError($this->_getWiki()->transform('
: The term : A definition
: Another term : Another definition
', 'Rst'))
        );
    }

    public function testLongDeflist()
    {
        $this->assertEquals(
            ':The term:     A long long long long long long long long long long long long
               long definition
:Another term: Another definition

',
            $this->protectAgainstPearError($this->_getWiki()->transform('
: The term : A long long long long long long long long long long long long long definition
: Another term : Another definition
', 'Rst'))
        );
    }

    public function testBulletlist()
    {
        $this->assertEquals(
            '* A
* B

',
            $this->protectAgainstPearError($this->_getWiki()->transform('
* A
* B
', 'Rst'))
        );
    }

    public function testTwoLevelBulletlist()
    {
        $this->assertEquals(
            '* A
  * B


',
            $this->protectAgainstPearError($this->_getWiki()->transform('
* A
  * B
', 'Rst'))
        );
    }

    public function testNumberedList()
    {
        $this->assertEquals(
            '1. A
2. B

',
            $this->protectAgainstPearError($this->_getWiki()->transform('
# A
# B
', 'Rst'))
        );
    }

    public function testTwoLevelNumberedList()
    {
        $this->assertEquals(
            '1. A
  1. B


',
            $this->protectAgainstPearError($this->_getWiki()->transform('
# A
  # B
', 'Rst'))
        );
    }

    public function testFixtureCliModular()
    {
        $fixture = __DIR__ . '/../fixtures/cli_modular';
        $this->assertEquals(
            file_get_contents($fixture . '.rst'),
            $this->protectAgainstPearError(
                $this->_getWiki()->transform(file_get_contents($fixture . '.wiki'), 'Rst')
            )
        );
    }
}
