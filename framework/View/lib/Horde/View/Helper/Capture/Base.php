<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
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
 * An instance of this class is returned by
 * Horde_View_Helper_Capture::capture().
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Capture_Base
{
    /**
     * Are we currently buffering?
     *
     * @var boolean
     */
    protected $_buffering = true;

    /**
     * Starts capturing.
     */
    public function __construct()
    {
        ob_start();
    }

    /**
     * Stops capturing and returns what was captured.
     *
     * @return string  The captured string.
     * @throws Horde_View_Exception
     */
    public function end()
    {
        if ($this->_buffering) {
            $this->_buffering = false;
            $output = ob_get_clean();
            return $output;
        } else {
            throw new Horde_View_Exception('Capture already ended');
        }
    }
}
