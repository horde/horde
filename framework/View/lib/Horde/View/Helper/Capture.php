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
 * Capture lets you extract parts of code which can be used in other points of
 * the template or even layout file.
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://www.horde.org/licenses/bsd
 * @category   Horde
 * @package    View
 * @subpackage Helper
 */
class Horde_View_Helper_Capture extends Horde_View_Helper_Base
{
    /**
     * Capture allows you to extract a part of the template into an instance
     * variable.
     *
     * You can use this instance variable anywhere in your templates and even
     * in your layout. Example:
     *
     * <code>
     * <?php $capture = $this->capture() ?>
     * Welcome To my shiny new web page!
     * <?php $this->greeting = $capture->end() ?>
     * </code>
     *
     * @return Horde_View_Helper_Capture_Base
     */
    public function capture()
    {
        return new Horde_View_Helper_Capture_Base();
    }

    /**
     * Calling contentFor() stores the block of markup for later use.
     *
     * Subsequently, you can retrieve it inside an instance variable
     * that will be named "contentForName" in another template
     * or in the layout.  Example:
     *
     * <code>
     * <?php $capture = $this->contentFor("header") ?>
     * <script type="text/javascript">alert('hello world')</script>
     * <?php $capture->end() ?>
     *
     * // Use $this->contentForHeader anywhere in your templates:
     * <?php echo $this->contentForHeader ?>
     * </code>
     *
     * @param string $name  Name of the content that becomes the instance
     *                      variable name. "foo" -> "$this->contentForFoo"
     * @return Horde_View_Helper_Capture_ContentFor
     */
    public function contentFor($name)
    {
        return new Horde_View_Helper_Capture_ContentFor($name, $this->_view);
    }
}
