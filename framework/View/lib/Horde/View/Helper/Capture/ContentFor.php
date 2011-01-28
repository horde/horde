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
 * Horde_View_Helper_Capture::contentFor().
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_View
 * @subpackage Helper
 */
class Horde_View_Helper_Capture_ContentFor extends Horde_View_Helper_Capture_Base
{
    /**
     * Name that will become "$this->contentForName"
     *
     * @var string
     */
    private $_name;

    /**
     * Start capturing content that will be stored as
     * $view->contentForName.
     *
     * @param string $name  Name of the content that becomes the instance
     *                      variable name. "foo" -> "$this->contentForFoo"
     * @param Horde_View_Base $view
     */
    public function __construct($name, $view)
    {
        $this->_name = $name;
        $this->_view = $view;
        parent::__construct();
    }

    /**
     * Stop capturing content and store it in the view.
     */
    public function end()
    {
        $name = 'contentFor' . ucfirst($this->_name);
        $this->_view->$name = parent::end();
    }

}
