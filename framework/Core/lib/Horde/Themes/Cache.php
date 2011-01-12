<?php
/**
 * This class is responsible for parsing/building theme elements and then
 * caching these results.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Themes_Cache implements Serializable
{
    /* Constants */
    const HORDE_DEFAULT = 1;
    const APP_DEFAULT = 2;
    const HORDE_THEME = 4;
    const APP_THEME = 8;

    /**
     * Has the data changed?
     *
     * @var boolean
     */
    public $changed = false;

    /**
     * Application name.
     *
     * @var string
     */
    protected $_app;

    /**
     * The cache ID.
     *
     * @var string
     */
    protected $_cacheid;

    /**
     * Is this a complete representation of the theme?
     *
     * @var boolean
     */
    protected $_complete = false;

    /**
     * Theme data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Theme name.
     *
     * @var string
     */
    protected $_theme;

    /**
     * Constructor.
     *
     * @param string $app    The application name.
     * @param string $theme  The theme name.
     */
    public function __construct($app, $theme)
    {
        $this->_app = $app;
        $this->_theme = $theme;
    }

    /**
     * Build the entire theme data structure.
     *
     * @return array  The list of theme files.
     */
    public function build()
    {
        if (!$this->_complete) {
            $this->_data = array();

            $this->_build('horde', 'default', self::HORDE_DEFAULT);
            $this->_build('horde', $this->_theme, self::HORDE_THEME);
            if ($this->_app != 'horde') {
                $this->_build($this->_app, 'default', self::APP_DEFAULT);
                $this->_build($this->_app, $this->_theme, self::APP_THEME);
            }

            $this->changed = $this->_complete = true;
        }

        return array_keys($this->_data);
    }

    /**
     * Add theme data from an app/theme combo.
     *
     * @param string $app    The application name.
     * @param string $theme  The theme name.
     * @param integer $mask  Mask for the app/theme combo.
     */
    protected function _build($app, $theme, $mask)
    {
        $path = $GLOBALS['registry']->get('themesfs', $app) . '/'. $theme;

        try {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        } catch (UnexpectedValueException $e) {
            return;
        }

        foreach ($it as $val) {
            if (!$val->isDir()) {
                $sub = $it->getSubPathname();

                if (isset($this->_data[$sub])) {
                    $this->_data[$sub] |= $mask;
                } else {
                    $this->_data[$sub] = $mask;
                }
            }
        }
    }

    /**
     */
    public function get($item, $mask = 0)
    {
        if (!($entry = $this->_get($item))) {
            return null;
        }

        if ($mask) {
            $entry &= $mask;
        }

        if ($entry & self::APP_THEME) {
            $app = $this->_app;
            $theme = $this->_theme;
        } elseif ($entry & self::HORDE_THEME) {
            $app = 'horde';
            $theme = $this->_theme;
        } elseif ($entry & self::APP_DEFAULT) {
            $app = $this->_app;
            $theme = 'default';
        } elseif ($entry & self::HORDE_DEFAULT) {
            $app = 'horde';
            $theme = 'default';
        } else {
            return null;
        }

        return $this->_getOutput($app, $theme, $item);
    }

    /**
     */
    protected function _get($item)
    {
        if (!isset($this->_data[$item])) {
            $entry = 0;

            $path = $GLOBALS['registry']->get('themesfs', 'horde');
            if (file_exists($path . '/default/' . $item)) {
                $entry |= self::HORDE_DEFAULT;
            }
            if (file_exists($path . '/' . $this->_theme . '/' . $item)) {
                $entry |= self::HORDE_THEME;
            }

            if ($this->_app != 'horde') {
                $path = $GLOBALS['registry']->get('themesfs', $this->_app);
                if (file_exists($path . '/default/' . $item)) {
                    $entry |= self::APP_DEFAULT;
                }
                if (file_exists($path . '/' . $this->_theme . '/' . $item)) {
                    $entry |= self::APP_THEME;
                }
            }

            $this->_data[$item] = $entry;
            $this->changed = true;
        }

        return $this->_data[$item];
    }

    /**
     */
    protected function _getOutput($app, $theme, $item)
    {
        return array(
            'fs' => $GLOBALS['registry']->get('themesfs', $app) . '/' . $theme . '/' . $item,
            'uri' => $GLOBALS['registry']->get('themesuri', $app) . '/' . $theme . '/' . $item
        );
    }

    /**
     */
    public function getAll($item, $mask = 0)
    {
        if (!($entry = $this->_get($item))) {
            return array();
        }

        if ($mask) {
            $entry &= $mask;
        }
        $out = array();

        if ($entry & self::APP_THEME) {
            $out[] = $this->_getOutput($this->_app, $this->_theme, $item);
        }
        if ($entry & self::HORDE_THEME) {
            $out[] = $this->_getOutput('horde', $this->_theme, $item);
        }
        if ($entry & self::APP_DEFAULT) {
            $out[] = $this->_getOutput($this->_app, 'default', $item);
        }
        if ($entry & self::HORDE_DEFAULT) {
            $out[] = $this->_getOutput('horde', 'default', $item);
        }

        return $out;
    }

    /**
     */
    public function getCacheId()
    {
        if (!isset($this->_cacheid)) {
            $check = isset($GLOBALS['conf']['cachethemesparams']['check']) ? $GLOBALS['conf']['cachethemesparams']['check'] : null;
            switch ($check) {
            case 'appversion':
            default:
                $id = array($GLOBALS['registry']->getVersion($this->_app));
                if ($this->_app != 'horde') {
                    $id[] = $GLOBALS['registry']->getVersion('horde');
                }
                $this->_cacheid = 'v:' . implode('|', $id);
                break;

            case 'none':
                $this->_cacheid = '';
                break;
            }
        }

        return $this->_cacheid;
    }

    /* Serializable methods. */

    /**
     */
    public function serialize()
    {
        return serialize(array(
            'a' => $this->_app,
            'c' => $this->_complete,
            'd' => $this->_data,
            'id' => $this->getCacheId(),
            't' => $this->_theme
        ));
    }

    /**
     */
    public function unserialize($data)
    {
        $out = @unserialize($data);

        if (isset($out['id']) && ($out['id'] != $this->getCacheId())) {
            throw new Exception('Cache invalidated');
        }

        $this->_app = $out['a'];
        $this->_complete = $out['c'];
        $this->_data = $out['d'];
        $this->_theme = $out['t'];
    }

}
