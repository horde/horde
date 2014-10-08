<?php
/**
 * Copyright 2010-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 */

/**
 * An object-oriented interface to a themed image.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL
 * @package   Core
 *
 * @property-read string $base64img  See Horde_Themes_Image::base64ImgData()
 *                                   (since 2.10.0).
 */
class Horde_Themes_Image extends Horde_Themes_Element
{
    /**
     * The default directory name for this element type.
     *
     * @var string
     */
    protected $_dirname = 'graphics';

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'base64img':
            return self::base64ImgData($this);

        default:
            return parent::__get($name);
        }
    }

    /**
     * Constructs a correctly-pathed tag to an image.
     *
     * @param mixed $src   The image file (either a string or a
     *                     Horde_Themes_Image object).
     * @param array $opts  Additional options:
     *   - alt: (string) Text describing the image.
     *   - attr: (mixed) Any additional attributes for the image tag. Can be a
     *           pre-built string or an array of key/value pairs that will be
     *           assembled and html-encoded.
     *   - fullsrc: (boolean) TODO
     *   - imgopts: (array) TODO
     *
     * @return string  The full image tag.
     */
    public static function tag($src, array $opts = array())
    {
        global $browser, $conf;

        $opts = array_merge(array(
            'alt' => '',
            'attr' => array(),
            'fullsrc' => false,
            'imgopts' => array()
        ), $opts);

        /* If browser does not support images, simply return the ALT text. */
        if (!$browser->hasFeature('images')) {
            return htmlspecialchars($opts['alt']);
        }

        $xml = new SimpleXMLElement('<root><img ' . (is_array($opts['attr']) ? '' : $opts['attr']) . '/></root>');
        $img = $xml->img;

        if (is_array($opts['attr'])) {
            foreach ($opts['attr'] as $key => $val) {
                $img->addAttribute($key, $val);
            }
        }

        if (strlen($opts['alt'])) {
            $img->addAttribute('alt', $opts['alt']);
        }

        /* If no directory has been specified, get it from the registry. */
        if (!($src instanceof Horde_Themes_Image) &&
            (substr($src, 0, 1) != '/')) {
            $src = Horde_Themes::img($src, $opts['imgopts']);
        }

        if (empty($conf['nobase64_img'])) {
            $src = self::base64ImgData($src);
        }
        if ($opts['fullsrc'] && (substr($src, 0, 10) != 'data:image')) {
            $src = Horde::url($src, true, array('append_session' => -1));
        }

        $img->addAttribute('src', $src);

        return $img->asXML();
    }

    /*
     * Generate RFC 2397-compliant image data strings.
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
        if (!($dataurl = $GLOBALS['browser']->hasFeature('dataurl'))) {
            return $in;
        }

        if (!is_null($limit) &&
            (is_bool($dataurl) || ($limit < $dataurl))) {
            $dataurl = $limit;
        }

        /* Only encode image files if they are below the dataurl limit. */
        if (!($in instanceof Horde_Themes_Image)) {
            $in = self::fromUri($in);
        }
        if (!file_exists($in->fs)) {
            return $in->uri;
        }

        /* Delete approx. 50 chars from the limit to account for the various
         * data/base64 header text.  Multiply by 0.75 to determine the
         * base64 encoded size. */
        return (($dataurl === true) ||
                (filesize($in->fs) <= (($dataurl * 0.75) - 50)))
            ? strval(Horde_Url_Data::create(Horde_Mime_Magic::extToMime(substr($in->uri, strrpos($in->uri, '.') + 1)), file_get_contents($in->fs)))
            : $in->uri;
    }

}
