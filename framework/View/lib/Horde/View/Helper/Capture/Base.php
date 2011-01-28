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
 * @subpackage Helper
 */

/**
 * An instance of this class is returned by
 * Horde_View_Helper_Capture::capture().
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
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
     * Start capturing.
     */
    public function __construct()
    {
        ob_start();
    }

    /**
     * Stop capturing and return what was captured.
     *
     * @return string
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
