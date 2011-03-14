<?php
/**
 * @category Horde
 * @package View
 */

/**
 * Horde_View_Interface is a reference for classes to be used as Horde
 * Views. Implementing it is optional; type hinting is not used to
 * enforce the interface.
 *
 * @category Horde
 * @package View
 */
interface Horde_View_Interface
{
    /**
     * Undefined variables return null.
     *
     * @return null
     */
    public function __get($name);

    /**
     * Accesses a helper object from within a template.
     *
     * @param string $method  The helper method.
     * @param array $args     The parameters for the helper.
     *
     * @return string  The result of the helper method.
     */
    public function __call($name, $args);

    /**
     * Adds to the stack of template paths in LIFO order.
     *
     * @param string|array  The directory (-ies) to add.
     */
    public function addTemplatePath($path);

    /**
     * Resets the stack of template paths.
     *
     * To clear all paths, use Horde_View::setTemplatePath(null).
     *
     * @param string|array  The directory (-ies) to set as the path.
     */
    public function setTemplatePath($path);

    /**
     * Adds to the stack of helpers in LIFO order.
     *
     * @param Horde_View_Helper|string $helper  The helper instance to add.
     *
     * @return Horde_View_Helper  Returns the helper object that was added.
     */
    public function addHelper($helper);

    /**
     * Assigns multiple variables to the view.
     *
     * The array keys are used as names, each assigned their corresponding
     * array value.
     *
     * @param array $array  The array of key/value pairs to assign.
     */
    public function assign($array);

    /**
     * Processes a template and returns the output.
     *
     * @param string $name  The template to process.
     *
     * @return string  The template output.
     */
    public function render($name);

    /**
     * Sets the output encoding.
     *
     * @param string $encoding  A character set name.
     */
    public function setEncoding($encoding);

    /**
     * Returns the current output encoding.
     *
     * @return string  The current character set.
     */
    public function getEncoding();
}
