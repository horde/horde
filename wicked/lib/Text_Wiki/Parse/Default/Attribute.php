<?php
/**
 * This parser parses "attributes," which carry meta-information about the
 * page.  These attributes are in the form [[WikiWord: value]].
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Attribute extends Text_Wiki_Parse {

    /**
     * The regular expression used to find source text matching this rule (this
     * is set in the constructor).
     *
     * @var string
     */
    var $regex;

    function Text_Wiki_Parse_Attribute(&$obj)
    {
        parent::Text_Wiki_Parse($obj);

        $this->regex = '/((?:\[\[' . Wicked::REGEXP_WIKIWORD .
                       ':\s+.*?\]\]\s*)+)/';
    }

    /**
     * Generates a token entry for the matched text. Token options are:
     *
     * 'src'  => The image source, typically a relative path name.
     * 'opts' => Any macro options following the source.
     *
     * @param array &$matches  The array of matches from parse().
     *
     * @return  A delimited token number to be used as a placeholder in
     *          the source text.
     */
    function process(&$matches)
    {
        $options = array('attributes' => array());

        $text = $matches[1];
        while (preg_match('/^\[\[([A-Za-z0-9]+):\s+(.*?)\]\]\s*(.*)$/s',
                          $text, $sub)) {

            $options['attributes'][] = array('name' => $sub[1],
                                             'value' => $sub[2]);
            $text = $sub[3];
        }

        return $this->wiki->addToken($this->rule, $options);
    }

}
