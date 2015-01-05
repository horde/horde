<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   CssMinify
 */

/**
 * Abstract base class for implementing a CSS minification driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   CssMinify
 */
abstract class Horde_CssMinify
{
    /**
     * Original CSS data.
     *
     * @var mixed
     */
    protected $_data;

    /**
     * Minification options.
     *
     * @var array
     */
    protected $_opts = array();

    /**
     * Constructor.
     *
     * @param mixed $css   Either a string (the CSS text to compress) or an
     *                     array of URLs (keys) to filenames (values)
     *                     containing the CSS data to compress.
     * @param array $opts  Additional options. See setOptions().
     */
    public function __construct($css, array $opts = array())
    {
        if (!is_array($css) && !is_string($css)) {
            throw new InvalidArgumentException('First argument must either be an array or a string.');
        }

        $this->_data = $css;
        $this->setOptions($opts);
    }

    /**
     * @see Horde_CssMinify::minify()
     */
    public function __toString()
    {
        return $this->minify();
    }

    /**
     * Set minification options.
     *
     * @param array $opts  Options:
     * <pre>
     *   - logger: (Horde_Log_Logger) Log object to use for log messages.
     * </pre>
     */
    public function setOptions(array $opts = array())
    {
        $this->_opts = array_merge($this->_opts, $opts);

        // Ensure we have a logger object.
        if (!isset($this->_opts['logger']) ||
            !($this->_opts['logger'] instanceof Horde_Log_Logger)) {
            $this->_opts['logger'] = new Horde_Log_Logger(
                new Horde_Log_Handler_Null()
            );
        }
    }

    /**
     * Return the minified CSS.
     *
     * @return string  Minified CSS.
     * @throws Horde_CssMinify_Exception
     */
    abstract public function minify();

}
