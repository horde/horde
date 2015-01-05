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
 * @package   JavascriptMinify
 */

/**
 * Abstract base class for implementing a javascript minification driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */
abstract class Horde_JavascriptMinify
{
    /**
     * Original javascript data.
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
     * Temporary file containing sourcemap data.
     *
     * @var string
     */
    protected $_sourcemap = null;

    /**
     * Constructor.
     *
     * @param mixed $js    Either a string (the JS text to compress) or an
     *                     array of URLs (keys) to filenames (values)
     *                     containing the JS data to compress.
     * @param array $opts  Additional options. See setOptions().
     */
    public function __construct($js, array $opts = array())
    {
        if (!is_array($js) && !is_string($js)) {
            throw new InvalidArgumentException('First argument must either be an array or a string.');
        }

        $this->_data = $js;
        $this->setOptions($opts);
    }

    /**
     * @see Horde_JavascriptMinify::minify()
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
     * Return the minified javascript.
     *
     * @return string  Minified javascript.
     * @throws Horde_JavascriptMinify_Exception
     */
    abstract public function minify();

    /**
     * Returns the sourcemap data.
     * Only supported if javascript is data is provided via web-accessible
     * static files.
     * minify() must be called before this method will return any data.
     *
     * @return mixed  The sourcemap data, or null if it doesn't exist.
     */
    public function sourcemap()
    {
        if (is_null($this->_sourcemap) ||
            !is_readable($this->_sourcemap) ||
            !strlen($sourcemap = file_get_contents($this->_sourcemap))) {
            return null;
        }

        /* Sourcemap data is JSON encoded. Need to grab 'sources', which
         * contains filenames, and convert to URLs. */
        $sourcemap = json_decode($sourcemap);
        $data_lookup = array_flip($this->_data);
        $new_sources = array();

        foreach ($sourcemap->sources as $val) {
            $new_sources[] = $data_lookup[$val];
        }

        $sourcemap->sources = $new_sources;
        unset($sourcemap->sourceRoot, $sourcemap->file);

        return json_encode($sourcemap);
    }

    /**
     * Creates a list of source comments linking to the original URLs of the
     * source files.
     *
     * Needed in minification files to ensure that all license terms of the
     * minified code (which may have been removed during the minification
     * process) are accessible.
     *
     * @since 1.1.0
     *
     * @return string  Source URL data.
     */
    protected function _sourceUrls()
    {
        $out = '';

        if (is_array($this->_data)) {
            foreach (array_keys($this->_data) as $val) {
                $out .= "\n// @source: " . $val;
            }
        }

        return $out;
    }

}
