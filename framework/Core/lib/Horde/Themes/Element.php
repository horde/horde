<?php
/**
 * The Horde_Themes_Element:: class provides an object-oriented interface to
 * a themes element.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Themes_Element
{
    /**
     * Current application name.
     *
     * @var string
     */
    public $app;

    /**
     * URI/filesystem path values.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * The default directory name for this element type.
     *
     * @var string
     */
    protected $_dirname = '';

    /**
     * Element name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Options.
     *
     * @var array
     */
    protected $_opts;

    /**
     * Constructor.
     *
     * @param string $name    The element name. If null, will return the
     *                        element directory.
     * @param array $options  Additional options:
     * <pre>
     * 'app' - (string) Use this application instead of the current app.
     * 'data' - (array) Contains 2 elements: 'fs' - filesystem path,
                        'uri' - the element URI. If set, use as the data
                        values instead of auto determining.
     * 'nohorde' - (boolean) If true, do not fallback to horde for element.
     * 'theme' - (string) Use this theme instead of the Horde default.
     * 'uri' - (string) Use this as the URI value.
     * </pre>
     */
    public function __construct($name = '', array $options = array())
    {
        $this->app = empty($options['app'])
            ? $GLOBALS['registry']->getApp()
            : $options['app'];
        $this->_name = $name;
        $this->_opts = $options;

        if ($GLOBALS['registry']->get('status', $this->app) == 'heading') {
            $this->app = 'horde';
        }

        if (isset($this->_opts['data'])) {
            $this->_data = $this->_opts['data'];
            unset($this->_opts['data']);
        }
    }

    /**
     * String representation of this object.
     *
     * @return string  The relative URI.
     */
    public function __toString()
    {
        try {
            return (string)$this->uri;
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
            return '';
        }
    }

    /**
     * Retrieve URI/filesystem path values.
     *
     * @param string $name  One of:
     * <pre>
     * 'fs' - (string) Filesystem location.
     * 'fulluri' - (Horde_Url) Full URI.
     * 'uri' - (string) Relative URI.
     * </pre>
     *
     * @return string  The requested value.
     */
    public function __get($name)
    {
        global $prefs, $registry;

        if (empty($this->_data)) {
            $theme = array_key_exists('theme', $this->_opts)
                ? $this->_opts['theme']
                : $prefs->getValue('theme');

            if (is_null($this->_name)) {
                /* Return directory only. */
                $this->_data = array(
                    'fs' => $registry->get('themesfs', $this->app) . '/' . $theme . '/' . $this->_dirname,
                    'uri' => $registry->get('themesuri', $this->app) . '/' . $theme . '/' . $this->_dirname
                );
            } else {
                $cache = $GLOBALS['injector']->getInstance('Horde_Core_Factory_ThemesCache')->create($this->app, $theme);
                $mask = empty($this->_opts['nohorde'])
                    ? 0
                    : Horde_Themes_Cache::APP_DEFAULT | Horde_Themes_Cache::APP_THEME;

                $this->_data = $cache->get($this->_dirname . '/' . $this->_name, $mask);
            }
        }

        switch ($name) {
        case 'fs':
        case 'uri':
            return $this->_data[$name];

        case 'fulluri':
            return Horde::url($this->_data['uri'], true);

        default:
            return null;
        }
    }

    /**
     * Convert a URI into a Horde_Themes_Element object.
     *
     * @param string $uri  The URI to convert.
     *
     * @return Horde_Themes_Element  A theme element object.
     */
    static public function fromUri($uri)
    {
        global $registry;

        return new self('', array(
            'data' => array(
                'fs' => realpath($registry->get('fileroot', 'horde')) . preg_replace('/^' . preg_quote($registry->get('webroot', 'horde'), '/') . '/', '', $uri),
                'uri' => $uri
            )
        ));
    }

}
