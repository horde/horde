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
 * Compresses CSS based on Horde configuration parameters.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Core
 * @since     2.12.0
 */
class Horde_Themes_Css_Compress
{
    /**
     * Loads CSS files, cleans up the input (and compresses), and concatenates
     * to a string.
     *
     * @param array $css  See Horde_Themes_Css#getStylesheets().
     *
     * @return string  CSS data.
     */
    public function compress($css)
    {
        global $browser, $conf, $injector;

        $files = array();
        foreach ($css as $val) {
            $files[$val['uri']] = $val['fs'];
        }

        $parser = new Horde_CssMinify_CssParser($files, array(
            'dataurl' => (empty($conf['nobase64_img']) && $browser->hasFeature('dataurl')) ? array($this, 'dataurlCallback') : null,
            'import' => array($this, 'importCallback'),
            'logger' => $injector->getInstance('Horde_Log_Logger')
        ));

        return $parser->minify();
    }

    /**
     */
    public function dataurlCallback($uri)
    {
        /* Limit data to 16 KB in stylesheets. */
        return Horde_Themes_Image::base64ImgData($uri, 16384);
    }

    /**
     */
    public function importCallback($uri)
    {
        $ob = Horde_Themes_Element::fromUri($uri);
        return array($ob->uri, $ob->fs);
    }

}
