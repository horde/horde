<?php

require_once 'Text/Wiki/Parse/Mediawiki/Wikilink.php';

/**
 * Placeholder class as a complement to the Wikilink2 renderer.
 *
 * We are overwriting the image() function because we want it to pass on to add
 * an Image2 token instead.
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Wikilink2 extends Text_Wiki_Parse_Wikilink
{
    /**
     * Generates an image token.  Token options are:
     * - 'src' => the name of the image file
     * - 'attr' => an array of attributes for the image:
     * | - 'alt' => the optional alternate image text
     * | - 'align' => 'left', 'center' or 'right'
     * | - 'width' => 'NNNpx'
     * | - 'height' => 'NNNpx'
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return string token to be used as replacement
     */
    public function image($name, $text, $interlang, $colon)
    {
        $attr = array('alt' => '');
        $splits = explode('|', $text);
        $sep = '';
        foreach ($splits as $split) {
            switch (strtolower($split)) {
                case 'left': case 'center': case 'right':
                    $attr['align'] = strtolower($split);
                    break;
                default:
                    // this regex is imho not restrictive enough but should
                    // keep false positives to a minimum
                    if (preg_match('/\dpx\s*$/i', $split)) {
                        $split = preg_replace("/\s/", "", $split);
                        $split = preg_replace("/px$/i", "", $split);
                        list($width,$height) = explode("x", $split);
                        $attr['width'] = $width;
                        if ($height) {
                            $attr['height'] = $height;
                        }
                    }
					else {
                        $attr['alt'] .= $sep . $split;
                        $sep = '|';
                    }
            }
        }
        $options = array(
            'src' => ($interlang ? $interlang . ':' : '') . $name,
            'attr' => $attr);

        // create and return the replacement token
        return $this->wiki->addToken('Image2', $options);
    }
}
