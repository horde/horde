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
     * Cached URI/filesystem path values.
     *
     * @var array
     */
    protected $_cache = array();

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
     * 'notheme' - (boolean) If true, do not use themed data.
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
            $this->_cache = $this->_opts['data'];
            unset($this->_opts['data']);
        }
    }

    /**
     * String representation of this object.
     *
     * @return string  The URI.
     */
    public function __toString()
    {
        return $this->uri;
    }

    /**
     * Retrieve URI/filesystem path values.
     *
     * @param string $name  Either 'fs' or 'uri'.
     *
     * @return string  The requested value.
     */
    public function __get($name)
    {
        global $registry;

        if (empty($this->_cache)) {
            $app_list = array($this->app);
            if (($this->app != 'horde') && empty($this->_opts['nohorde'])) {
                $app_list[] = 'horde';
            }
            $path = '/' . $this->_dirname . (is_null($this->_name) ? '' : '/' . $this->_name);

            /* Check themes first. */
            if (empty($this->_opts['notheme']) &&
                isset($GLOBALS['prefs']) &&
                (($theme = $GLOBALS['prefs']->getValue('theme')) ||
                 (!empty($this->_opts['theme']) &&
                  ($theme = $this->_opts['theme'])))) {
                $tpath = '/' . $theme . $path;
                foreach ($app_list as $app) {
                    $filepath = $registry->get('themesfs', $app) . $tpath;
                    if (is_null($this->_name) || file_exists($filepath)) {
                        $this->_cache = array(
                            'fs' => $filepath,
                            'uri' => $registry->get('themesuri', $app) . $tpath
                        );
                        break;
                    }
                }
            }

            /* Fall back to app/horde defaults. */
            if (empty($this->_cache)) {
                foreach ($app_list as $app) {
                    $filepath = $registry->get('themesfs', $app) . $path;
                    if (file_exists($filepath)) {
                        $this->_cache = array(
                            'fs' => $filepath,
                            'uri' => $registry->get('themesuri', $app) . $path
                        );
                        break;
                    }
                }
            }
        }

        switch ($name) {
        case 'fs':
        case 'uri':
            return isset($this->_cache[$name])
                ? $this->_cache[$name]
                : '';

        default:
            return null;
        }
    }

    /**
     * Convert a URI into a Horde_Themes_Image object.
     *
     * @param string $uri  The URI to convert.
     *
     * @return Horde_Themes_Image  An image object.
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
