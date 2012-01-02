<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */

/**
 * Dumps a variable for inspection.
 *
 * Portions borrowed from Paul M. Jones' Solar_Debug
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Debug extends Horde_View_Helper_Base
{
    /**
     * Dumps a variable for inspection.
     *
     * @param mixed $var  A variable.
     *
     * @return string  Debug output of the variable.
     */
    public function debug($var)
    {
        return '<pre class="debug_dump">'
            . htmlspecialchars($this->_fetch($var))
            . '</pre>';
    }

    /**
     * Pretty exception dumper.
     *
     * Inspired by:
     * http://www.sitepoint.com/blogs/2006/04/04/pretty-blue-screen/ and
     * http://www.sitepoint.com/blogs/2006/08/12/pimpin-harrys-pretty-bluescreen/.
     *
     * Also see for future ideas:
     * http://mikenaberezny.com/archives/55
     *
     * @param Exception $e  An exception to dump.
     *
     * @return string  Debug output of the exception.
     */
    public function dump(Exception $e)
    {
        $input = array(
            'type'    => get_class($e),
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'trace'   => $e->getTrace(),
        );

        // Store previous output.
        $previous_output = ob_get_contents();

        $desc = $input['type'] . ' making ' . $_SERVER['REQUEST_METHOD'] . ' request to ' . $_SERVER['REQUEST_URI'];
        return $this->render('_dump.html.php');
    }

    /**
     * Returns formatted output from var_dump().
     *
     * Buffers the var_dump() output for a variable and applies some simple
     * formatting for readability.
     *
     * @param mixed $var  Variable to dump.
     *
     * @return string  Formatted results of var_dump().
     */
    protected function _fetch($var)
    {
        ob_start();
        var_dump($var);
        return preg_replace('/\]\=\>\n(\s+)/m', '] => ', ob_get_clean());
    }

    protected function _sub($f)
    {
        $loc = '';
        if (isset($f['class'])) {
            $loc .= $f['class'] . $f['type'];
        }
        if (isset($f['function'])) {
            $loc .= $f['function'];
        }
        if (!empty($loc)) {
            $loc = htmlspecialchars($loc);
            $loc = "<strong>$loc</strong>";
        }
        return $loc;
    }

    protected function _clean($line)
    {
        $l = trim(strip_tags($line));
        return $l ? $l : '&nbsp;';
    }

    protected function _parms($f)
    {
        if (isset($f['function'])) {
            try {
                if (isset($f['class'])) {
                    $r = new ReflectionMethod($f['class'] . '::' . $f['function']);
                } else {
                    $r = new ReflectionFunction($f['function']);
                }
                return $r->getParameters();
            } catch(Exception $e) {
            }
        }
        return array();
    }

    protected function _src2lines($file)
    {
        $src = nl2br(highlight_file($file, true));
        return explode('<br />', $src);
    }
}
