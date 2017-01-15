<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */

/**
 * YUI Compressor minification driver.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   JavascriptMinify
 */
class Horde_JavascriptMinify_Yui extends Horde_JavascriptMinify_Null
{
    /**
     * @param array $opts  Driver specific options:
     * <pre>
     *   - cmdline: (string) Command line arguments to use.
     *   - java: (string) [REQUIRED] Path to the java binary.
     *   - yui: (string) [REQUIRED] Path to the YUI compressor.
     * </pre>
     */
    public function setOptions(array $opts = array())
    {
        foreach (array('java', 'yui') as $val) {
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
        $js = parent::minify();

        if (!is_executable($this->_opts['java']) ||
            !is_readable($this->_opts['yui'])) {
            $this->_opts['logger']->log(
                'The java path or YUI location can not be accessed.',
                Horde_Log::ERR
            );
            return $js;
        }

        $cmd = escapeshellcmd($this->_opts['java']) . ' -jar ' . escapeshellarg($this->_opts['yui']) . ' --type js';
        if (isset($this->_opts['cmdline'])) {
            $cmd .= ' ' . $this->_opts['cmdline'];
        }

        $cmdline = new Horde_JavascriptMinify_Util_Cmdline();
        return $cmdline->runCmd($js, trim($cmd), $this->_opts['logger']) .
            $this->_sourceUrls();
    }

}
