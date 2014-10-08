<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Contains deprecated methods from the Horde:: class. To be removed in
 * Horde_Core v3.0+.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Deprecated
{
    /**
     * Send response data to browser.
     *
     * @deprecated
     *
     * @param mixed $data  The data to serialize and send to the browser.
     * @param string $ct   The content-type to send the data with.  Either
     *                     'json', 'js-json', 'html', 'plain', and 'xml'.
     */
    public static function sendHTTPResponse($data, $ct)
    {
        // Output headers and encoded response.
        switch ($ct) {
        case 'json':
        case 'js-json':
            /* JSON responses are a structured object which always
             * includes the response in a member named 'response', and an
             * additional array of messages in 'msgs' which may be updates
             * for the server or notification messages.
             *
             * Make sure no null bytes sneak into the JSON output stream.
             * Null bytes cause IE to stop reading from the input stream,
             * causing malformed JSON data and a failed request.  These
             * bytes don't seem to break any other browser, but might as
             * well remove them anyway.
             *
             * Finally, add prototypejs security delimiters to returned
             * JSON. */
            $s_data = str_replace("\00", '', Horde::escapeJson($data));

            if ($ct == 'json') {
                header('Content-Type: application/json');
                echo $s_data;
            } else {
                header('Content-Type: text/html; charset=UTF-8');
                echo htmlspecialchars($s_data);
            }
            break;

        case 'html':
        case 'plain':
        case 'xml':
            $s_data = is_string($data) ? $data : $data->response;
            header('Content-Type: text/' . $ct . '; charset=UTF-8');
            echo $s_data;
            break;

        default:
            echo $data;
        }

        exit;
    }

    /**
     * Returns a response object with added notification information.
     *
     * @deprecated
     *
     * @param mixed $data      The 'response' data.
     * @param boolean $notify  If true, adds notification info to object.
     *
     * @return object  The Horde JSON response.  It has the following
     *                 properties:
     *   - msgs: (array) [OPTIONAL] List of notification messages.
     *   - response: (mixed) The response data for the request.
     */
    public static function prepareResponse($data = null, $notify = false)
    {
        $response = new stdClass();
        $response->response = $data;

        if ($notify) {
            $stack = $GLOBALS['notification']->notify(array('listeners' => 'status', 'raw' => true));
            if (!empty($stack)) {
                $response->msgs = $stack;
            }
        }

        return $response;
    }

    /**
     * Constructs a correctly-pathed tag to an image.
     *
     * @deprecated  Use Horde_Themes_Image::tag()
     *
     * @param mixed $src   The image file (either a string or a
     *                     Horde_Themes_Image object).
     * @param string $alt  Text describing the image.
     * @param mixed $attr  Any additional attributes for the image tag. Can
     *                     be a pre-built string or an array of key/value
     *                     pairs that will be assembled and html-encoded.
     *
     * @return string  The full image tag.
     */
    public static function img($src, $alt = '', $attr = '')
    {
        return Horde_Themes_Image::tag($src, array(
            'alt' => $alt,
            'attr' => $attr
        ));
    }

    /**
     * Same as img(), but returns a full source url for the image.
     * Useful for when the image may be part of embedded Horde content on an
     * external site.
     *
     * @deprecated  Use Horde_Themes_Image::tag()
     * @see img()
     */
    public static function fullSrcImg($src, array $opts = array())
    {
        return Horde_Themes_Image::tag($src, array_filter(array(
            'attr' => isset($opts['attr']) ? $opts['attr'] : null,
            'fullsrc' => true,
            'imgopts' => $opts
        )));
    }

    /**
     * Generate RFC 2397-compliant image data strings.
     *
     * @deprecated  Use Horde_Themes_Image::base64ImgData()
     *
     * @param mixed $in       URI or Horde_Themes_Image object containing
     *                        image data.
     * @param integer $limit  Sets a hard size limit for image data; if
     *                        exceeded, will not string encode.
     *
     * @return string  The string to use in the image 'src' attribute; either
     *                 the image data if the browser supports, or the URI
     *                 if not.
     */
    public static function base64ImgData($in, $limit = null)
    {
        return Horde_Themes_Image::base64ImgData($in, $limit);
    }

    /**
     * Call a Horde hook, handling all of the necessary lookups and parsing
     * of the hook code.
     *
     * WARNING: Throwing exceptions is expensive, so use callHook() with care
     * and cache the results if you going to use the results more than once.
     *
     * @deprecated  Use Horde_Core_Hooks object instead.
     *
     * @param string $hook  The function to call.
     * @param array  $args  An array of any arguments to pass to the hook
     *                      function.
     * @param string $app   The hook application.
     *
     * @return mixed  The results of the hook.
     * @throws Horde_Exception  Thrown on error from hook code.
     * @throws Horde_Exception_HookNotSet  Thrown if hook is not active.
     */
    public static function callHook($hook, $args = array(), $app = 'horde')
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Hooks')
            ->callHook($hook, $app, $args);
    }

    /**
     * Returns whether a hook exists.
     *
     * Use this if you have to call a hook many times and expect the hook to
     * not exist.
     *
     * @deprecated  Use Horde_Core_Hooks object instead.
     *
     * @param string $hook  The function to call.
     * @param string $app   The hook application.
     *
     * @return boolean  True if the hook exists.
     */
    public static function hookExists($hook, $app = 'horde')
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Hooks')
            ->hookExists($hook, $app);
    }

    /**
     * Loads global and vhost specific configuration files.
     *
     * @deprecated  Use Horde_Registry#loadConfigFile() instead.
     *
     * @param string $config_file      The name of the configuration file.
     * @param string|array $var_names  The name(s) of the variable(s) that
     *                                 is/are defined in the configuration
     *                                 file.
     * @param string $app              The application. Defaults to the current
     *                                 application.
     * @param boolean $show_output     If true, the contents of the requested
     *                                 config file are simply output instead of
     *                                 loaded into a variable.
     *
     * @return mixed  The value of $var_names, in a compact()'ed array if
     *                $var_names is an array.
     * @throws Horde_Exception
     */
    public static function loadConfiguration($config_file, $var_names = null,
                                             $app = null, $show_output = false)
    {
        global $registry;

        $app_conf = $registry->loadConfigFile($config_file, $var_names, $app);

        if ($show_output) {
            echo $app_conf->output;
        }

        if (is_null($var_names)) {
            return;
        }

        return is_array($var_names)
            ? array_intersect_key($app_conf->config, array_flip($var_names))
            : $app_conf->config[$var_names];
    }

    /**
     * Initialize a HordeMap.
     *
     * @deprecated  Call Horde_Core_HordeMap::init() instead.
     *
     * @param array $params
     */
    public static function initMap(array $params = array())
    {
        Horde_Core_HordeMap::init();
    }

}
