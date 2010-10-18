<?php
/**
 * This parser parses Horde Registry links, which allow calling Horde
 * API "*"/show methods from within the page. Basic syntax is
 * [[link link title | link-app/link-method argname1=value1 argname2=value2 ...]].
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Registrylink extends Text_Wiki_Parse
{
    /**
     * The regular expression used to find registry links.
     *
     * @access public
     *
     * @var string
     */
    public $regex = "/\[\[link (.*)\]\]/sU";

    /**
     * Generates a token entry for the matched text. Token options are:
     *
     * 'app'  => The application to link to.
     * 'args' => The parameters passed to the app/show method.
     *
     * @access public
     *
     * @param array &$matches  The array of matches from parse().
     *
     * @return  A delimited token number to be used as a placeholder in
     *          the source text.
     */
    public function process(&$matches)
    {
        @list($title, $call) = explode('|', $matches[1], 2);
        $opts = explode(' ', trim($call));
        $method = trim(array_shift($opts));
        parse_str(implode('&', $opts), $args);

        return $this->wiki->addToken($this->rule, array('title' => trim($title),
                                                        'method' => $method,
                                                        'args' => $args));
    }
}
