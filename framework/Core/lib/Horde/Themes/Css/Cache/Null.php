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
 * Null backend for the CSS caching library (directly outputs original CSS).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Themes_Css_Cache_Null extends Horde_Themes_Css_Cache
{
    /**
     */
    public function process($css, $cacheid)
    {
        global $registry;

        $out = array();

        foreach ($css as $file) {
            $url = Horde::url($file['uri'], true, -1);
            if (!is_null($file['app']) &&
                !empty($this->_params['url_version_param'])) {
                $url->add('v', hash(
                    (PHP_MINOR_VERSION >= 4) ? 'fnv132' : 'sha1',
                    $registry->getVersion($file['app'])
                ));
            }
            $out[] = $url;
        }

        return $out;
    }

}
