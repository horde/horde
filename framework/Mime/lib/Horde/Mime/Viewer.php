<?php
/**
 * The Horde_MIME_Viewer:: class provides an abstracted interface to render
 * MIME data into various formats.  It depends on both a set of
 * Horde_MIME_Viewer_* drivers which handle the actual rendering, and a
 * configuration file to map MIME types to drivers.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_MIME
 */
class Horde_MIME_Viewer
{
    /**
     * The config array. This array is shared between all instances of
     * Horde_MIME_Viewer.
     *
     * @var array
     */
    static protected $_config = array();

    /**
     * The driver cache array.
     *
     * @var array
     */
    static protected $_drivercache = array();

    /**
     * Attempts to return a concrete Horde_MIME_Viewer_* object based on the
     * MIME type.
     *
     * @param string $mime_type  The MIME type.
     *
     * @return Horde_MIME_Viewer  The Horde_MIME_Viewer object, or false on
     *                            error.
     */
    static final public function factory($mime_type)
    {
        /* Spawn the relevant driver, and return it (or false on failure). */
        if (($ob = self::_getDriver($mime_type, $GLOBALS['registry']->getApp())) &&
            self::_resolveDriver($ob['driver'], $ob['app']) &&
            class_exists($ob['class'])) {
            return new $ob['class'](self::$_config['mime_drivers'][$ob['app']][$ob['driver']]);
        }

        return false;
    }

    /**
     * Given a MIME type and an app name, determine which driver can best
     * handle the data.
     *
     * @param string $mime_type  MIME type to resolve.
     * @param string $app        App in which to search for the driver.
     *
     * @return mixed  Returns false if driver could not be found. Otherwise,
     *                an array with the following elements:
     * <pre>
     * 'app' - (string) The app containing the driver (e.g. 'horde')
     * 'driver' - (string) Name of driver (e.g. 'enscript')
     * 'exact' - (boolean) Was the driver and exact match?
     * </pre>
     */
    static final protected function _getDriver($mime_type, $app = 'horde')
    {
        $sig = $mime_type . '|' . $app;
        if (isset(self::$_drivercache[$sig])) {
            return self::$_drivercache[$sig];
        }

        /* Make sure 'horde' mime_drivers config is loaded. */
        if (empty(self::$_config['mime_drivers']['horde'])) {
            $res = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), 'horde');
            if (is_a($res, 'PEAR_Error')) {
                return false;
            }
            self::$_config = $res;
        }

        /* Make sure app's' mime_drivers config is loaded. */
        if (($app != 'horde') && empty(self::$_config['mime_drivers'][$app])) {
            $res = Horde::loadConfiguration('mime_drivers.php', array('mime_drivers', 'mime_drivers_map'), $app);
            if (is_a($res, 'PEAR_Error')) {
                return false;
            }

            require_once 'Horde/Array.php';
            self::$_config = Horde_Array::array_merge_recursive_overwrite(self::$_config, $res);
        }

        $driver = '';
        $exact = false;

        list($primary_type,) = explode('/', $mime_type, 2);
        $allSub = $primary_type . '/*';

        /* If the app doesn't exist in $mime_drivers_map, check for Horde-wide
         * viewers. */
        if (!isset(self::$_config['mime_drivers_map'][$app]) &&
            ($app != 'horde')) {
            return self::_getDriver($mime_type, 'horde');
        }

        $dr = self::$_config['mime_drivers'][$app];
        $map = self::$_config['mime_drivers_map'][$app];

        /* If an override exists for this MIME type, then use that */
        if (isset($map['overrides'][$mime_type])) {
            $driver = $map['overrides'][$mime_type];
            $exact = true;
        } elseif (isset($map['overrides'][$allSub])) {
            $driver = $map['overrides'][$allSub];
            $exact = true;
        } elseif (isset($map['registered'])) {
            /* Iterate through the list of registered drivers, and see if
             * this MIME type exists in the MIME types that they claim to
             * handle. If the driver handles it, then assign it as the
             * rendering driver. If we find a generic handler, keep iterating
             * to see if we can find a specific handler. */
            foreach ($map['registered'] as $val) {
                if (in_array($mime_type, $dr[$val]['handles'])) {
                    $driver = $val;
                    $exact = true;
                    break;
                } elseif (in_array($allSub, $dr[$val]['handles'])) {
                    $driver = $val;
                }
            }
        }

        /* If this is an application specific app, and an exact match was
           not found, search for a Horde-wide specific driver. Only use the
           Horde-specific driver if it is NOT the 'default' driver AND the
           Horde driver is an exact match. */
        if (!$exact && ($app != 'horde')) {
            $ob = self::_getDriver($mime_type, 'horde');
            if (empty($driver) ||
                (($ob['driver'] != 'default') && $ob['exact'])) {
                $driver = $ob['driver'];
                $app = 'horde';
            }
        }

        /* If the 'default' driver exists in this app, fall back to that. */
        if (empty($driver) && self::_resolveDriver('default', $app)) {
            $driver = 'default';
        }

        if (empty($driver)) {
            return false;
        }

        self::$_drivercache[$sig] = array(
            'app' => $app,
            'class' => (($app == 'horde') ? '' : $app . '_') . 'Horde_MIME_Viewer_' . $driver,
            'driver' => $driver,
            'exact' => $exact,
        );

        return self::$_drivercache[$sig];
    }

    /**
     * Given a driver and an application, attempts to load the library file.
     *
     * @param string $driver  Driver name.
     * @param string $app     Application name.
     *
     * @return boolean  True if library file was loaded.
     */
    static final protected function _resolveDriver($driver = 'default',
                                                   $app = 'horde')
    {
        $file = ($app == 'horde')
            ? dirname(__FILE__) . '/Viewer/' . $driver . '.php'
            : $GLOBALS['registry']->applications[$app]['fileroot'] . '/lib/MIME/Viewer/' . $driver . '.php';

        require_once dirname(__FILE__) . '/Viewer/Driver.php';

        $old_error = error_reporting(0);
        $ret = require_once $file;
        error_reporting($old_error);

        return $ret;
    }

    /**
     * Prints out the status message for a given MIME Part.
     *
     * @param string $msg     The message to output.
     * @param string $img     An image link to add to the beginning of the
     *                        message.
     * @param string $class   An optional style for the status box.
     *
     * @return string  The formatted status message string.
     */
    static public function formatStatusMsg($msg, $img = null, $class = null)
    {
        if (empty($msg)) {
            return '';
        }

        if (!is_array($msg)) {
            $msg = array($msg);
        }

        /* If we are viewing as an attachment, don't print HTML code. */
        if (self::viewAsAttachment()) {
            return implode("\n", $msg);
        }

        if (is_null($class)) {
            $class = 'mimeStatusMessage';
        }
        $text = '<table class="' . $class . '">';

        /* If no image, simply print out the message. */
        if (is_null($img)) {
            foreach ($msg as $val) {
                $text .= '<tr><td>' . $val . '</td></tr>' . "\n";
            }
        } else {
            $text .= '<tr><td class="mimeStatusIcon">' . $img . '</td><td>';
            if (count($msg) == 1) {
                $text .= $msg[0];
            } else {
                $text .= '<table>';
                foreach ($msg as $val) {
                    $text .= '<tr><td>' . $val . '</td></tr>' . "\n";
                }
                $text .= '</table>';
            }
            $text .= '</td></tr>' . "\n";
        }

        return $text . '</table>';
    }

    /**
     * Given a MIME type, this function will return an appropriate icon.
     *
     * @param string $mime_type  The MIME type that we need an icon for.
     *
     * @return string  The URL to the appropriate icon.
     */
    static final public function getIcon($mime_type)
    {
        $app = $GLOBALS['registry']->getApp();
        $ob = self::_getIcon($mime_type, $app);

        if (is_null($ob)) {
            if ($app == 'horde') {
                return null;
            }

            $obHorde = self::_getIcon($mime_type, 'horde');
            return is_null($obHorde) ? null : $obHorde['url'];
        } elseif (($ob['match'] !== 0) && ($app != 'horde')) {
            $obHorde = self::_getIcon($mime_type, 'horde');
            if (!is_null($ob['match']) &&
                ($obHorde['match'] <= $ob['match'])) {
                return $obHorde['url'];
            }
        }

        return $ob['url'];
    }

    /**
     * Given an input MIME type and app, this function returns the URL of an
     * icon that can be associated with it
     *
     * @param string $mime_type  MIME type to get the icon for.
     *
     * @return mixed  Null if not found, or an array with the following keys:
     * <pre>
     * 'exact' - (integer) How exact the match is.
     *           0 => 'exact', 1 => 'primary', 2 => 'driver',
     *           3 => 'default', or null.
     * 'url' - (string) URL to an icon, or null if none could be found.
     * </pre>
     */
    static final protected function _getIcon($mime_type, $app = 'horde')
    {
        if (!($ob = self::_getDriver($mime_type, $app))) {
            return null;
        }
        $driver = $ob['driver'];

        list($primary_type,) = explode('/', $mime_type, 2);
        $allSub = $primary_type . '/*';
        $ret = null;

        /* If the app doesn't exist in $mime_drivers, return now. */
        if (!isset(self::$_config['mime_drivers'][$app])) {
            return null;
        }

        $dr = self::$_config['mime_drivers'][$app];

        /* If a specific icon for this driver and mimetype is defined,
           then use that. */
        if (isset($dr[$driver]['icons'])) {
            $icondr = $dr[$driver]['icons'];
            $iconList = array($mime_type => 0, $allSub => 1, 'default' => 2);
            foreach ($iconList as $key => $val) {
                if (isset($icondr[$key])) {
                    $ret = array('match' => $val, 'url' => $icondr[$key]);
                    break;
                }
            }
        }

        /* Try to use a default icon if none already obtained. */
        if (is_null($ret['url']) &&
            isset($dr['default']['icons']['default'])) {
            $ret = array('match' => 3, 'url' => $dr['default']['icons']['default']);
        }

        if (!is_null($ret)) {
            $ret['url'] = $GLOBALS['registry']->getImageDir($app) . '/mime/' . $ret['url'];
        }

        return $ret;
    }
}
