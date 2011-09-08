<?php
/**
 * Test the restructured text renderer.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the restructured text renderer.
 *
 * @category   Horde
 * @package    Wicked
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @link       http://www.horde.org/apps/wicked
 * @license    http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, version 2
 */
class Wicked_Unit_RstTest extends Wicked_TestCase
{
    public function setUp()
    {
        $this->unstrictPearTestingMode();
    }

    public function tearDown()
    {
        $this->revertTestingMode();
    }

    public function testEmpty()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '',
            $this->protectAgainstPearError($wiki->transform('', 'Rst'))
        );
    }

    public function testHeaderOne()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '=========
  HEADER
=========

',
            $this->protectAgainstPearError($wiki->transform('+ HEADER', 'Rst'))
        );
    }

    public function testHeaderTwo()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            '--------
 HEADER
--------

',
            $this->protectAgainstPearError($wiki->transform('++HEADER', 'Rst'))
        );
    }

    public function testHeaderThree()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
======

',
            $this->protectAgainstPearError($wiki->transform('+++HEADER', 'Rst'))
        );
    }

    public function testHeaderFour()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
******

',
            $this->protectAgainstPearError($wiki->transform('++++HEADER', 'Rst'))
        );
    }

    public function testHeaderFive()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
------

',
            $this->protectAgainstPearError($wiki->transform('+++++HEADER', 'Rst'))
        );
    }

    public function testHeaderSix()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'HEADER
``````

',
            $this->protectAgainstPearError($wiki->transform('++++++HEADER', 'Rst'))
        );
    }

    public function testToc()
    {
        $wiki = new Text_Wiki_Default();
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
            $this->protectAgainstPearError($wiki->transform('
[[toc]]

+H

++++H4

+G

++++++H6', 'Rst'))
        );
    }

    public function testPlainUrl()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'Further information on Horde and the latest version can be obtained at

  http://www.horde.org/apps/horde

',
            $this->protectAgainstPearError($wiki->transform('Further information on Horde and the latest version can be obtained at

{{  }}http://www.horde.org/apps/horde

', 'Rst'))
        );
    }

    public function testPlainEmbeddedUrl()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'There
is a list of Horde applications and projects at http://www.horde.org/apps.

',
            $this->protectAgainstPearError($wiki->transform('There
is a list of Horde applications and projects at http://www.horde.org/apps.

', 'Rst'))
        );
    }

    public function testNamedUrl()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'certification mark of the `Open Source Initiative`_.

.. _`Open Source Initiative`: http://www.opensource.org/

',
            $this->protectAgainstPearError($wiki->transform('certification mark of the [http://www.opensource.org/ Open Source Initiative].
', 'Rst'))
        );
    }

    public function testLiteral()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'in the ``docs/`` directory

',
            $this->protectAgainstPearError($wiki->transform('in the ``docs/`` directory', 'Rst'))
        );
    }

    public function testFreelink()
    {
        $wiki = new Text_Wiki_Default();
        $this->assertEquals(
            'The following documentation is available in the Horde distribution:

:`COPYING`_:      Copyright and license information
:`docs/CHANGES`_: Changes by release


.. _`COPYING`: http://www.horde.org/licenses/lgpl
.. _`docs/CHANGES`: CHANGES',
            $this->protectAgainstPearError($wiki->transform('The following documentation is available in the Horde distribution:

: [http://www.horde.org/licenses/lgpl COPYING] : Copyright and license information
: ((CHANGES|docs/CHANGES)) : Changes by release
', 'Rst'))
        );
    }


}
