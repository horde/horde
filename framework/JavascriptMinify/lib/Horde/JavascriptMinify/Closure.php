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
 * Google Closure Compiler minification driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */
class Horde_JavascriptMinify_Closure extends Horde_JavascriptMinify_Null
{
    /**
     * @param array $opts  Driver specific options:
     * <pre>
     *   - closure: (string) [REQUIRED] Path to the Closure compiler.
     *   - cmdline: (string) Command line arguments to use.
     *   - java: (string) [REQUIRED] Path to the java binary.
     *   - sourcemap: (string) The URL to the web-accessible location the
     *                sourcemap file will be stored at.
     * </pre>
     */
    public function setOptions(array $opts = array())
    {
        foreach (array('closure', 'java') as $val) {
            if (!isset($opts[$val])) {
                throw new InvalidArgumentException(
                    sprintf('Missing required %s option.', $val)
                );
            }
        }

        parent::setOptions($opts);
    }

    /**
     */
    public function minify()
    {
        if (!is_executable($this->_opts['java']) ||
            !is_readable($this->_opts['closure'])) {
            $this->_opts['logger']->log(
                'The java path or Closure location can not be accessed.',
                Horde_Log::ERR
            );
            return parent::minify();
        }

        $cmd = trim(escapeshellcmd($this->_opts['java']) . ' -jar ' . escapeshellarg($this->_opts['closure']));
        if (isset($this->_opts['sourcemap']) && is_array($this->_data)) {
            $this->_sourcemap = Horde_Util::getTempFile();
            $cmd .= ' --create_source_map ' .
                escapeshellarg($this->_sourcemap) .
                ' --source_map_format=V3';
            $suffix = "\n//# sourceMappingURL=" . $this->_opts['sourcemap'];
        } else {
            $suffix = '';
        }
        if (isset($this->_opts['cmdline'])) {
            $cmd .= ' ' . trim($this->_opts['cmdline']);
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
        return $cmdline->runCmd($js, $cmd, $this->_opts['logger']) . $suffix
            . $this->_sourceUrls();
    }

}
