<?php
/**
 * Copyright 2014-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */

/**
 * UglifyJS minification driver.  Supports UglifyJS versions 1 (with limited
 * features) and 2.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */
class Horde_JavascriptMinify_Uglifyjs extends Horde_JavascriptMinify_Null
{
    /**
     * @param array $opts  Driver specific options:
     * <pre>
     *   - cmdline: (string) Command-line options to pass to binary.
     *   - sourcemap: (string) The URL to the web-accessible location the
     *                sourcemap file will be stored at. Only supported by
     *                UglifyJS2.
     *   - uglifyjs: (string) [REQUIRED] Path to the UglifyJS binary.
     * </pre>
     */
    public function setOptions(array $opts = array())
    {
        if (!isset($opts['uglifyjs'])) {
            throw new InvalidArgumentException('Missing required uglifyjs option.');
        }

        parent::setOptions($opts);
    }

    /**
     */
    public function minify()
    {
        if (!is_readable($this->_opts['uglifyjs'])) {
            $this->_opts['logger']->log(
                'The UglifyJS binary cannot be accessed.',
                Horde_Log::ERR
            );
            return parent::minify();
        }

        $cmd = escapeshellcmd($this->_opts['uglifyjs']);
        /* Sourcemaps only supported by UglifyJS2. */
        if (isset($this->_opts['sourcemap']) && is_array($this->_data)) {
            $this->_sourcemap = Horde_Util::getTempFile();
            $cmd .= ' --source-map ' .
                escapeshellarg($this->_sourcemap) .
                ' --source-map-url ' .
                escapeshellarg($this->_opts['sourcemap']);
        }

        if (is_array($this->_data)) {
            $js = '';
            foreach ($this->_data as $val) {
                $cmd .= ' ' . $val;
            }
        } else {
            $js = $this->_data;
        }

        $cmdline = new Horde_JavascriptMinify_Util_Cmdline();
        return $cmdline->runCmd(
            $js,
            trim($cmd . ' ' . $this->_opts['cmdline']),
            $this->_opts['logger']
        ) . $this->_sourceUrls();
    }

}
