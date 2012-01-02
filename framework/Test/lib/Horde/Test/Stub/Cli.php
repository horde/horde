<?php
/**
 * A test helper for testing Horde_Cli based classes.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * A test helper for testing Horde_Cli based classes.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.2.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Stub_Cli extends Horde_Cli
{
    /**
     * Displays a fatal error message.
     *
     * @param mixed $error  The error text to display, an exception or an
     *                      object with a getMessage() method.
     */
    public function fatal($error)
    {
        if ($error instanceof Exception) {
            $trace = $error;
        } else {
            $trace = debug_backtrace();
        }
        $backtrace = new Horde_Support_Backtrace($trace);
        if (is_object($error) && method_exists($error, 'getMessage')) {
            $error = $error->getMessage();
        }
        $this->writeln($this->red('===================='));
        $this->writeln();
        $this->writeln($this->red('Fatal Error:'));
        $this->writeln($this->red($error));
        $this->writeln();
        $this->writeln((string)$backtrace);
        $this->writeln($this->red('===================='));
    }
}