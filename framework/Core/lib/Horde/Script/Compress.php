<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Compresses javascript based on Horde configuration parameters.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 *
 * @property-read boolean $sourcemap_support  True if the driver supports
 *                                            sourcemaps.
 */
class Horde_Script_Compress
{
    /**
     * Javascript minification driver.
     *
     * @var string
     */
    protected $_driver;

    /**
     * Javacscript minification params.
     *
     * @var array
     */
    protected $_params;

    /**
     * Does the minification driver support sourcemaps?
     *
     * @var boolean
     */
    protected $_sourcemap = false;

    /**
     * Constructor.
     *
     * @param string $driver  Minification driver.
     * @param array $params   Configuration parameters.
     */
    public function __construct($driver, array $params = array())
    {
        global $injector;

        $this->_params = array(
            'logger' => $injector->getInstance('Horde_Log_Logger')
        );

        switch ($driver) {
        case 'closure':
            $this->_driver = 'Horde_JavascriptMinify_Closure';
            $this->_params = array_merge($this->_params, array(
                'closure' => $params['closurepath'],
                'java' => $params['javapath']
            ));
            $this->_sourcemap = true;
            break;

        case 'none':
            $this->_driver = 'Horde_JavascriptMinify_Null';
            break;

        case 'php':
            /* Due to licensing issues, Jsmin might not be available. */
            $this->_driver = class_exists('Horde_JavascriptMinify_Jsmin')
                ? 'Horde_JavascriptMinify_Jsmin'
                : 'Horde_JavascriptMinify_Null';
            break;

        case 'uglifyjs':
            $this->_driver = 'Horde_JavascriptMinify_Uglifyjs';
            $this->_params = array_merge($this->_params, array(
                'uglifyjs' => $params['uglifyjspath']
            ));

            if (isset($params['uglifyjscmdline'])) {
                $this->_params['cmdline'] = trim($params['uglifyjscmdline']);
            }

            if (isset($params['uglifyjsversion'])) {
                switch ($params['uglifyjsversion']) {
                case 2:
                    if ($this->_params['cmdline'] != '-c') {
                        $this->_params['cmdline'] = '-c';
                    }
                    $this->_sourcemap = true;
                    break;
                }
            } else {
                $this->_sourcemap = true;
            }
            break;

        case 'yui':
            $this->_driver = 'Horde_JavascriptMinify_Yui';
            $this->_params = array_merge($this->_params, array(
                'java' => $params['javapath'],
                'yui' => $params['yuipath']
            ));
            break;

        default:
            /* Treat as a custom driver. */
            if (class_exists($driver)) {
                $this->_driver = $driver;
                $this->_params = array_merge($this->_params, $params);
            } else {
                $this->_driver = 'Horde_JavascriptMinify_Null';
            }
            break;
        }
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'sourcemap_support':
            return $this->_sourcemap;
        }
    }

    /**
     * Returns a minifier object.
     *
     * @param array $scripts     Script list.
     * @param string $sourcemap  Sourcemap URL.
     *
     * @return Horde_JavascriptMinify  Minifier object.
     */
    public function getMinifier($scripts, $sourcemap = null)
    {
        $js_files = array();
        foreach ($scripts as $val) {
            $js_files[strval($val->url_full)] = $val->full_path;
        }

        return new $this->_driver($js_files, array_merge($this->_params, array(
            'sourcemap' => $sourcemap
        )));
    }

}
