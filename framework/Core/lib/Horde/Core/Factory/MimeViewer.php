<?php
/**
 * A Horde_Injector:: based Horde_Mime_Viewer factory.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */

/**
 * A Horde_Injector:: based Horde_Mime_Viewer factory.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Core
 * @author   Michael Slusarz <slusarz@horde.org>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Core
 */
class Horde_Core_Factory_MimeViewer
{
    /**
     * Driver configuration.
     *
     * @var array
     */
    private $_config = array();

    /**
     * The injector.
     *
     * @var Horde_Injector
     */
    private $_injector;

    /**
     * Constructor.
     *
     * @param Horde_Injector $injector  The injector to use.
     */
    public function __construct(Horde_Injector $injector)
    {
        $this->_injector = $injector;
    }

    /**
     * Attempts to return a concrete Horde_Mime_Viewer object based on the
     * MIME type.
     *
     * @param Horde_Mime_Part $mime  An object with the data to be rendered.
     * @param array $opts            Additional options:
     * <pre>
     * 'app' - (string) The Horde application to search for drivers in.
     *         DEFAULT: current app
     * 'type' - (string) The MIME type to use for loading.
     *          DEFAULT: Uses MIME type in $mime.
     * </pre>
     *
     * @return Horde_Mime_Viewer_Base  The newly created instance.
     * @throws Horde_Mime_Viewer_Exception
     */
    public function getViewer(Horde_Mime_Part $mime, array $opts = array())
    {
        $app = isset($opts['app'])
            ? $opts['app']
            : $GLOBALS['registry']->getApp();

        $type = isset($opts['type'])
            ? $opts['type']
            : $mime->getType();

        list($driver, $params) = $this->getViewerConfig($type, $app);

        return Horde_Mime_Viewer::factory($driver, $mime, $params);
    }

    /**
     * Gets the configuration for a MIME type.
     *
     * @param string $type  The MIME type.
     * @param string $app   The current Horde application.
     *
     * @return array  The driver and a list of configuration parameters.
     */
    public function getViewerConfig($type, $app)
    {
        $config = $this->_getDriver($type, $app);

        $config['driver'] = ucfirst($config['driver']);
        $driver = ($config['app'] == 'horde')
            ? $config['driver']
            : $config['app'] . '_Mime_Viewer_' . $config['driver'];

        $params = array_merge($config, array(
            'charset' => $GLOBALS['registry']->getCharset(),
            // TODO: Logging
            // 'logger' => $this->_injector->getInstance('Horde_Log_Logger'),
            'temp_file' => array('Horde', 'getTempFile'),
            'text_filter' => array($this->_injector->getInstance('Horde_Text_Filter'), 'filter')
        ));

        switch ($config['driver']) {
        case 'Css':
            if ($config['app'] == 'horde') {
                $driver = 'Horde_Core_Mime_Viewer_Css';
            }
            $params['registry'] = $GLOBALS['registry'];
            break;

        case 'Deb':
        case 'Rpm':
            $params['monospace'] = 'fixed';
            break;

        case 'Html':
            $params['browser'] = $GLOBALS['browser'];
            break;

        case 'Ooo':
            $params['zip'] = Horde_Compress::factory('Zip');
            break;

        case 'Rar':
            $params['monospace'] = 'fixed';
            $params['rar'] = Horde_Compress::factory('Rar');
            break;

        case 'Report':
        case 'Security':
            $params['viewer_callback'] = array($this, 'getViewerCallback');
            break;

        case 'Tgz':
            $params['gzip'] = Horde_Compress::factory('Gzip');
            $params['monospace'] = 'fixed';
            $params['tar'] = Horde_Compress::factory('Tar');
            break;

        case 'Tnef':
            $params['tnef'] = Horde_Compress::factory('Tnef');
            break;

        case 'Vcard':
            if ($config['app'] == 'horde') {
                $driver = 'Horde_Core_Mime_Viewer_Vcard';
            }
            $params['browser'] = $GLOBALS['browser'];
            $params['notification'] = $GLOBALS['notification'];
            $params['prefs'] = $GLOBALS['prefs'];
            $params['registry'] = $GLOBALS['registry'];
            break;

        case 'Zip':
            $params['monospace'] = 'fixed';
            $params['zip'] = Horde_Compress::factory('Zip');
            break;
        }

        return array($driver, $params);
    }

    /**
     * Callback used to return a MIME Viewer object from within certain
     * Viewer drivers.
     *
     * @param Horde_Mime_Viewer_Base $viewer  The MIME Viewer driver
     *                                        requesting the new object.
     * @param Horde_Mime_Part $mime           An object with the data to be
     *                                        rendered.
     * @param string $type                    The MIME type to use for
     *                                        rendering.
     *
     * @return Horde_Mime_Viewer_Base  The newly created instance.
     * @throws Horde_Mime_Viewer_Exception
     */
    public function getViewerCallback(Horde_Mime_Viewer_Base $viewer,
                                      Horde_Mime_Part $mime, $type)
    {
        return $this->getViewer($mime, array('type' => $type));
    }

    /**
     * Return the appropriate icon for a MIME object/MIME type.
     *
     * @param Horde_Mime_Part|string $mime  The MIME object or type to query.
     * @param array $opts                   Additional options:
     * <pre>
     * 'app' - (string) The Horde application to search for drivers in.
     *         DEFAULT: current app
     * </pre>
     *
     * @return Horde_Themes_Image  An object which contains the URI
     *                             and filesystem location of the image.
     */
    public function getIcon($mime, array $opts = array())
    {
        $app = isset($opts['app'])
            ? $opts['app']
            : $GLOBALS['registry']->getApp();

        $type = ($mime instanceof Horde_Mime_Part)
            ? $mime->getType()
            : $mime;

        $config = $this->_getDriver($type, $app);

        if (!isset($config['icon'])) {
            $config['icon'] = array(
                'app' => 'horde',
                'icon' => 'text.png'
            );
        }

        return Horde_Themes::img('mime/' . $config['icon']['icon'], $config['icon']['app']);
    }

    /**
     * Create the driver configuration for an application.
     *
     * @param string $app  The Horde application to search for drivers in.
     */
    private function _loadConfig($app)
    {
        if ($app != 'horde') {
            $this->_loadConfig('horde');
        }

        /* Make sure app's config is loaded. There is no requirement that
         * an app have a config, so ignore any errors. */
        if (isset($this->_config[$app])) {
            return;
        }

        try {
            $aconfig = Horde::loadConfiguration('mime_drivers.php', 'mime_drivers', $app);
        } catch (Horde_Exception $e) {
            $aconfig = array();
        }

        $config = array(
            'config' => array(),
            'handles' => array()
        );

        foreach ($aconfig as $key => $val) {
            if (empty($val['disable'])) {
                if (!empty($val['handles'])) {
                    $config['handles'] = array_merge($config['handles'], array_fill_keys(array_values($val['handles']), $key));
                    unset($val['handles']);
                }

                $config['config'][$key] = $val;
            }
        }

        /* Make sure there is a default entry. */
        if (($app == 'horde') && !isset($config['config']['default'])) {
            $config['config']['default'] = array();
        }

        $this->_config[$app] = $config;
    }

    /**
     * Get the driver config for a MIME type.
     *
     * @param string $type  The MIME type query.
     * @param string $app   The Horde application to search for drivers in.
     *
     * @return array  The driver config.
     */
    private function _getDriver($type, $app)
    {
        $this->_loadConfig($app);

        /* Start with default driver, and then merge in wildcard and exact
         * match configs. */
        $config = array();
        list($ptype,) = explode('/', $type, 2);
        $wild = $ptype . '/*';

        $app_list = array(
            array('horde', 'default', 'config'),
            array($app, 'default', 'config'),
            array('horde', $wild, 'handles'),
            array($app, $wild, 'handles'),
            array('horde', $type, 'handles'),
            array($app, $type, 'handles')
        );
        if ($app == 'horde') {
            unset($app_list[1], $app_list[3], $app_list[5]);
        }

        foreach ($app_list as $val) {
            $driver = isset($this->_config[$val[0]][$val[2]][$val[1]])
                ? (($val[1] == 'default') ? 'default' : $this->_config[$val[0]][$val[2]][$val[1]])
                : null;

            if ($driver) {
                $tmp = $this->_config[$val[0]]['config'][$driver];
                if (isset($tmp['icons'])) {
                    foreach (array($type, $wild, 'default') as $val2) {
                        if (isset($tmp['icons'][$val2])) {
                            $tmp['icon'] = array(
                                'app' => $val[0],
                                'icon' => $tmp['icons'][$val2]
                            );
                            break;
                        }
                    }
                    unset($tmp['icons']);
                }

                $config = array_merge(Horde_Array::array_merge_recursive_overwrite($config, $tmp), array(
                    'app' => $val[0],
                    'driver' => $driver
                ));

            }
        }

        return $config;
    }

}
