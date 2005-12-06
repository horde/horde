<?php

/**
*
* Parses for image placement.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
* @author Moritz Venn <moritz.venn@freaque.net>
*
* @license LGPL
*
* @version $Id$
*
*/

/**
*
* Parses for image placement.
*
* @category Text
*
* @package Text_Wiki
*
* @author Paul M. Jones <pmjones@php.net>
*
* @author Moritz Venn <moritz.venn@freaque.net>
*
*/

class Text_Wiki_Parse_Image extends Text_Wiki_Parse {

    /**
     * URL schemes recognized by this rule.
     *
     * @access public
     * @var array
    */
    var $conf = array(
        'schemes' => 'http|https|ftp|gopher|news',
        'host_regexp' => '(?:[^.\s/"\'<\\\#delim#\ca-\cz]+\.)*[a-z](?:[-a-z0-9]*[a-z0-9])?\.?',
        'path_regexp' => '(?:/[^\s"<\\\#delim#\ca-\cz]*)?'
    );

    /**
    *
    * The regular expression used to find source text matching this
    * rule. It already containt the german localization.
    *
    * @access public
    *
    * @var string
    *
    */

    var $regex = '/\[\[(image|bild)(\:|\s+)(.+)\]\]/i';


    /**
     * The regular expressions used to check external urls
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $url = '';

     /**
     * Constructor.
     * We override the constructor to build up the url regex from config
     *
     * @param object &$obj the base conversion handler
     * @return The parser object
     * @access public
     */
    function Text_Wiki_Parse_Image(&$obj)
    {
        $default = $this->conf;
        parent::Text_Wiki_Parse($obj);

        // convert the list of recognized schemes to a regex OR,
        $schemes = $this->getConf('schemes', $default['schemes']);
        $this->url = str_replace( '#delim#', $this->wiki->delim,
           '#(?:' . (is_array($schemes) ? implode('|', $schemes) : $schemes) . ')://'
           . $this->getConf('host_regexp', $default['host_regexp'])
           . $this->getConf('path_regexp', $default['path_regexp']) .'#');
    }

    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * 'src' => The image source, typically a relative path name.
    *
    * 'opts' => Any macro options following the source.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    function process(&$matches)
    {
        $pos = strpos($matches[3], '|');

        if ($pos === false) {
            $options = array(
                'src' => $matches[3],
                'attr' => array());
        } else {
            // everything after the seperator is attribute arguments
            $options = array(
                'src' => substr($matches[3], 0, $pos),
                'attr' => $this->getAttrs(substr($matches[3], $pos+1))
            );
            // check the scheme case of external link
            if (array_key_exists('link', $options['attr'])) {
                // external url ?
                if (($pos = strpos($options['attr']['link'], '://')) !== false) {
                    if (!preg_match($this->url, $options['attr']['link'])) {
                        return $matches[0];
                    }
                } elseif (in_array('Wikilink', $this->wiki->disable)) {
                        return $matches[0]; // Wikilink disabled
                }
            }
        }

        return $this->wiki->addToken($this->rule, $options);
    }

    function getAttrs($text)
    {
        // Description taken from Mediawiki Code
        # Options are:
        #  * thumbnail          make a thumbnail with enlarge-icon and caption, alignment depends on lang
        #  * left               no resizing, just left align. label is used for alt= only
        #  * right              same, but right aligned
        #  * none               same, but not aligned
        #  * ___px              scale to ___ pixels width, no aligning. e.g. use in taxobox
        #  * center             center the image
        #  * framed             Keep original image size, no magnify-button.

        $isThumb = $isFramed = false;
	
        // find the |s;
        $tmp = explode('|', trim($text));

        // basic setup
        $attrs = array();

        // loop through the sections
        foreach ($tmp as $val) {
        	// Show only Picture's Thumb (not yet implemented)
            if ($val === 'thumb' || $val === 'thumbnail') {
                $isThumb = true;
                continue;
           }
           // Define Picture as Framed (implemented as default)
           if ($val == 'framed') {
                $isFramed = true;
                continue;
           }
           // Define Picture's Align
           if (in_array($val, array('left', 'right', 'center', 'none'))) {
           	    // style="float:right"
           	    $attrs['style'] = 'float: ' . $val;
           	    continue;
           }
           // Define Picture's width
           if (substr($val, -2) == 'px') {
           	    $attrs['width'] = substr($val, 0, -2);
           	    continue;
           }
           // Define Picture's Alt 
           $attrs['alt'] = $val;
        }

        return $attrs;

    }

}
?>
