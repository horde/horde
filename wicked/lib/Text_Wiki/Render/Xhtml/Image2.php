<?php
/**
 * This class renders an inline image.
 *
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Image2 extends Text_Wiki_Render {

    var $conf = array(
        'base' => '',
        'url_base' => null,
        'css'  => null,
        'css_link' => null
    );

    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options  The "options" portion of the token (second
     *                        element).
     *
     * @return string  The text rendered from the token options.
     */
    function token($options)
    {
        if (!isset($options['attr']['alt'])) {
            $options['attr']['alt'] = $options['src'];
        }

        if (strpos($options['src'], '://') === false) {
            if ($options['src'][0] != '/') {
                if (strpos($options['src'], ':')) {
                    list($page, $options['src']) = explode(':', $options['src'], 2);
                } else {
                    $page = Horde_Util::getFormData('page');
                    if ($page == 'EditPage') {
                        $page = Horde_Util::getFormData('referrer');
                    }
                    if (empty($page)) {
                        $page = 'WikiHome';
                    }
                }
                $params = array('page' => $page,
                                'mime' => '1',
                                'file' => $options['src']);
                $options['src'] = Horde_Util::addParameter(Horde::url('view.php', true),
                                                     $params, null, false);
            }
        } else {
            $options['src'] = Horde_Util::addParameter(Horde::externalUrl($options['src']), 'untrusted', 1, false);
        }

        // Send external links through Horde::externalUrl().
        if (isset($options['attr']['link']) && strpos($options['attr']['link'], '://')) {
            $href = htmlspecialchars($options['attr']['link']);
            unset($options['attr']['link']);
            return Horde::link(Horde::externalUrl($href), $href) . $this->_token($options) . '</a>';
        } else {
            return $this->_token($options);
        }
    }

    /**
     * Render code from Text_Wiki's Image with Horde tweaks (remove
     * getimagesize call, etc).
     *
     * @access private
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     */
    function _token($options)
    {
        // note the image source
        $src = $options['src'];

        // is the source a local file or URL?
        if (strpos($src, '://') === false) {
            // the source refers to a local file.
            // add the URL base to it.
            $src = $this->getConf('base', '/') . $src;
        }

        // stephane@metacites.net
        // is the image clickable?
        if (isset($options['attr']['link'])) {
            // yes, the image is clickable.
            // are we linked to a URL or a wiki page?
            if (strpos($options['attr']['link'], '://')) {
                // it's a URL, prefix the URL base
                $href = $this->getConf('url_base') . $options['attr']['link'];
            } else {
                // it's a WikiPage; assume it exists.
                /** @todo This needs to honor sprintf wikilinks (pmjones) */
                /** @todo This needs to honor interwiki (pmjones) */
                /** @todo This needs to honor freelinks (pmjones) */
                $href = $this->wiki->getRenderConf('xhtml', 'wikilink', 'view_url') .
                    $options['attr']['link'];
            }
        } else {
            // image is not clickable.
            $href = null;
        }
        // unset so it won't show up as an attribute
        unset($options['attr']['link']);

        // start the HTML output
        $output = '<img src="' . htmlspecialchars($src) . '"';

        // get the CSS class but don't add it yet
        $css = $this->formatConf(' class="%s"', 'css');

        // add the attributes to the output, and be sure to
        // track whether or not we find an "alt" attribute
        $alt = false;
        foreach ($options['attr'] as $key => $val) {

            // track the 'alt' attribute
            if (strtolower($key) == 'alt') {
                $alt = true;
            }

            // the 'class' attribute overrides the CSS class conf
            if (strtolower($key) == 'class') {
                $css = null;
            }

            $key = htmlspecialchars($key);
            $val = htmlspecialchars($val);
            $output .= " $key=\"$val\"";
        }

        // always add an "alt" attribute per Stephane Solliec
        if (!$alt) {
            $alt = htmlspecialchars(basename($options['src']));
            $output .= " alt=\"$alt\"";
        }

        // end the image tag with the automatic CSS class (if any)
        $output .= "$css />";

        // was the image clickable?
        if ($href) {
            // yes, add the href and return
            $href = htmlspecialchars($href);
            $css = $this->formatConf(' class="%s"', 'css_link');
            $output = "<a$css href=\"$href\">$output</a>";
        }

        return $output;
    }

}
