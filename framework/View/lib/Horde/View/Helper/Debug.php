<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */

/**
 * Dumps a variable for inspection.
 * Portions borrowed from Paul M. Jones' Solar_Debug
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Debug extends Horde_View_Helper_Base
{
    /**
     * Dumps a variable for inspection.
     *
     * @param   string  $var
     * @return  string
     */
    public function debug($var)
    {
        return '<pre class="debug_dump">'
             . htmlspecialchars($this->_fetch($var))
             . '</pre>';
    }

    /**
     * Returns formatted output from var_dump().
     *
     * Buffers the var_dump output for a variable and applies some
     * simple formatting for readability.
     *
     * @param  mixed   $var   variable to dump
     * @return string         formatted results of var_dump()
     */
    private function _fetch($var)
    {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        return $output;
    }

}
