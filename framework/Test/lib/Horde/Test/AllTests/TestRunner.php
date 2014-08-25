<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @link      http://www.horde.org/components/Horde_Test
 * @package   Test
 */

/**
 * TestRunner for Horde AllTests.php scripts.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @link      http://www.horde.org/components/Horde_Test
 * @package   Test
 */
class Horde_Test_AllTests_TestRunner extends PHPUnit_Runner_BaseTestRunner
{
    /**
     * Get the test suite.
     *
     * @param string $package  The name of the package tested by this suite.
     * @param string $dir      The path of the AllTests class.
     */
    public function getSuite($package, $dir)
    {
        $suite = $this->getTest(
            $dir,
            '',
            'Test.php'
        );
        $suite->setName('Horde Framework - ' . $package);

        return $suite;
    }

    /**
     */
    protected function runFailed($message)
    {
    }

}
