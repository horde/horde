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
 * @package   Core
 */

/**
 * Object handling storage of cached JS data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
abstract class Horde_Script_Cache
{
    /**
     * Configuration parameters.
     *
     * @var array
     */
    protected $_params;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_params = $params;
    }

    /**
     * Process the scripts contained in Horde_Script_List.
     *
     * @param Horde_Script_List $hsl  Script list.
     *
     * @return object  Object with these properties:
     * <pre>
     *   - all: (array)
     *   - jsvars: (array)
     *   - script: (array)
     * </pre>
     */
    public function process(Horde_Script_List $hsl)
    {
        $out = new stdClass;
        $out->all = array();
        $out->jsvars = array();
        $out->script = array();

        $last_cache = null;
        $tmp = array();

        foreach ($hsl as $val) {
            $out->all[] = $url = strval($val->url);

            if (is_null($val->cache)) {
                $out->script = array_merge(
                    $out->script,
                    $this->_process($tmp),
                    array($url)
                );
                $tmp = array();
            } else {
                if (!is_null($last_cache) && ($last_cache != $val->cache)) {
                    $out->script = array_merge(
                        $out->script,
                        $this->_process($tmp)
                    );
                    $tmp = array();
                }
                $tmp[$val->hash] = $val;
            }

            $last_cache = $val->cache;

            if (!empty($val->jsvars)) {
                $out->jsvars = array_merge($out->jsvars, $val->jsvars);
            }
        }

        $out->script = array_merge($out->script, $this->_process($tmp));

        return $out;
    }

    /**
     * Perform garbage collection.
     */
    public function gc()
    {
    }

    /**
     * Process a list of scripts.
     *
     * @param array $scripts  Script list.
     *
     * @return array  List of JS files to load.
     */
    abstract protected function _process($scripts);

}
