<?php
/**
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */

/**
 * Provides methods for generating a country flag image.
 *
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2003-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 */
class Horde_Core_Ui_FlagImage
{
    /**
     * Generate a flag image tag.
     *
     * @param string $host  The hostname.
     *
     * @return string  An HTML IMG tag (or empty if host is not found).
     */
    public static function generateFlagImageByHost($host)
    {
        if (($data = self::getFlagImageObByHost($host)) === false) {
            return '';
        }

        $img = Horde_Themes_Image::tag($data['ob'], array(
            'alt' => $data['name'],
            'attr' => array('title' => $data['name'])
        ));

        return $img
            ? $img
            : '[' . $data['name'] . ']';
    }

    /**
     * Generate flag image object.
     *
     * @since 2.10.0
     *
     * @param string $host  The hostname.
     *
     * @return array  False if not found, or an array with these keys:
     * <pre>
     *   - name: (string) Country name.
     *   - ob: (Horde_Themes_Image) Image object.
     * </pre>
     */
    public static function getFlagImageObByHost($host)
    {
        global $conf, $injector;

        if (!Horde_Nls::$dnsResolver) {
            Horde_Nls::$dnsResolver = $injector->getInstance('Net_DNS2_Resolver');
        }

        $data = Horde_Nls::getCountryByHost(
            $host,
            empty($conf['geoip']['datafile']) ? null : $conf['geoip']['datafile']
        );
        if ($data === false) {
            return false;
        }

        return array(
            'name' => $data['name'],
            'ob' => Horde_Themes::img('flags/' . $data['code'] . '.png')
        );
    }

}
