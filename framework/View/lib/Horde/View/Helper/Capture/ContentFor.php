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
 * Horde_View_Helper_Capture::contentFor().
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Capture_ContentFor extends Horde_View_Helper_Capture_Base
{
    /**
     * Name that will become "$this->contentForName".
     *
     * @var string
     */
    private $_name;

    /**
     * Starts capturing content that will be stored as $view->contentForName.
     *
     * @param string $name           Name of the content that becomes the
     *                               instance variable name.
     *                               "foo" -> "$this->contentForFoo"
     * @param Horde_View_Base $view  A view object.
     */
    public function __construct($name, $view)
    {
        $this->_name = $name;
        $this->_view = $view;
        parent::__construct();
    }

    /**
     * Stops capturing content and stores it in the view.
     */
    public function end()
    {
        $name = 'contentFor' . Horde_String::ucfirst($this->_name);
        $this->_view->$name = parent::end();
    }
}
