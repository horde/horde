<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Default: Parses for smileys / emoticons tags
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * smileys defined by symbols as ':)' , ':-)' or ':smile:'
 * The symbol is replaced with a token.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Smiley rule parser class for Default.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Smiley extends Text_Wiki_Parse {

    /**
     * Configuration keys for this rule
     * 'smileys' => array Smileys recognized by this rule, symbols key definitions:
     *              'symbol' => array ( 'name', 'description' [, 'shortname'] ) as
     *                  ':)'  => array('smile', 'Smile'),
     *                  ':D'  => array('biggrin', 'Very Happy','grin'),
     *              or, in order to define a variante
     *              'variante' => 'symbol'  as e.g. '(:' => ':)' equates left handed smile
     *
     * 'auto_nose' => boolean enabling the auto nose feature:
     *                auto build a variante for 2 chars symbols by inserting a '-' as ':)' <=> ':-)'
     *
     * @access public
     * @var array 'config-key' => mixed config-value
     */
    var $conf = array(
        'smileys' => array(
            ':D'  => array('biggrin', 'Very Happy'),
            ':grin:' => ':D',
            ':)'  => array('smile', 'Smile'),
            ':('  => array('sad', 'Sad'),
            ':o'  => array('surprised', 'Surprised'),
            ':eek:' => ':o',
            ':shock:' => array('eek', 'Shocked'),
            ':?'  => array('confused', 'Confused'),
            ':???:' => ':?',
            '8)'  => array('cool', 'Cool'),
            ':lol:' => array('lol', 'Laughing'),
            ':x'  => array('mad', 'Mad'),
            ':P'  => array('razz', 'Razz'),
            ':oops:' => array('redface', 'Embarassed'),
            ':cry:' => array('cry', 'Crying or Very sad'),
            ':evil:' => array('evil', 'Evil or Very Mad'),
            ':twisted:' => array('twisted', 'Twisted Evil'),
            ':roll:' => array('rolleyes', 'Rolling Eyes'),
            ';)'  => array('wink', 'Wink'),
            ':!:' => array('exclaim', 'Exclamation'),
            ':?:' => array('question', 'Question'),
            ':idea:' => array('idea', 'Idea'),
            ':arrow:' => array('arrow', 'Arrow'),
            ':|'  => array('neutral', 'Neutral'),
            ':mrgreen:' => array('mrgreen', 'Mr. Green'),
        // left-handed variantes
            '(:'  => ':)',
            '):'  => ':(',
            'o:'  => ':o',
            '(8'  => '8)',
            '(;'  => ';)',
            '|:'  => ':|'
        ),
        'auto_nose' => true
    );

    var $smileys = array();

     /**
     * Constructor.
     * We override the constructor to build up the regex from config
     *
     * @param object &$obj the base conversion handler
     * @return The parser object
     * @access public
     */
    function Text_Wiki_Parse_Smiley(&$obj)
    {
        $default = $this->conf;
        parent::Text_Wiki_Parse($obj);

        // read the list of smileys to sort out variantes and :xxx: while building the regexp
        $this->smileys = $this->getConf('smileys', $default['smileys']);
        $autoNose = $this->getConf('auto_nose', $default['auto_nose']);
        $reg1 = $reg2 = '';
        $sep1 = ':(?:';
        $sep2 = '';
        foreach ($this->smileys as $smiley => $def) {
            $len = strlen($smiley);
            if (is_string($def)) {
                if (!isset($this->smileys[$def])) { // missing smiley !
                    continue;
                }
                $this->smileys[$smiley] = &$this->smileys[$def];
            }
            if (($smiley{0} == ':') && ($len > 2) && ($smiley{$len - 1} == ':')) {
                $reg1 .= $sep1 . preg_quote(substr($smiley, 1, -1), '#');
                $sep1 = '|';
                continue;
            }
            if ($autoNose && ($len === 2)) {
                $variante = $smiley{0} . '-' . $smiley{1};
                $this->smileys[$variante] = &$this->smileys[$smiley];
                $smiley = preg_quote($smiley{0}, '#') . '-?' . preg_quote($smiley{1}, '#');
            } else {
                $smiley = preg_quote($smiley, '#');
            }
            $reg2 .= $sep2 . $smiley;
            $sep2 = '|';
        }
        $delim = '[\n\r\s' . $this->wiki->delim . '$^]';
        $this->regex = '#(?<=' . $delim .
             ')(' . ($reg1 ? $reg1 . '):' . ($reg2 ? '|' : '') : '') . $reg2 .
             ')(?=' . $delim . ')#i';
    }

    /**
     * Generates a replacement token for the matched text.  Token options are:
     *     'src' => the URL / path to the smiley
     *     'attr' => empty for basic BBCode
     *
     * @param array &$matches The array of matches from parse().
     * @return string Delimited token representing the smiley
     * @access public
     */
    function process(&$matches)
    {
        // tokenize
        return $this->wiki->addToken($this->rule,
            array(
                'symbol' => $matches[1],
                'name'   => $this->smileys[$matches[1]][0],
                'desc'   => $this->smileys[$matches[1]][1],
            ));
    }
}
?>
