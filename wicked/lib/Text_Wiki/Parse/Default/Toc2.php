<?php

require_once 'Text/Wiki/Parse/Default/Toc.php';

/**
 * Replaces the default Toc parser to search for Heading2 tokens instead.
 *
 * @package Wicked
 */
class Text_Wiki_Parse_Toc2 extends Text_Wiki_Parse_Toc
{
    /**
    *
    * Generates a replacement for the matched text.
    *
    * Token options are:
    *
    * 'type' => ['list_start'|'list_end'|'item_start'|'item_end'|'target']
    *
    * 'level' => The heading level (1-6).
    *
    * 'count' => Which entry number this is in the list.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return string A token indicating the TOC collection point.
    *
    */

    public function process(&$matches)
    {
        $count = 0;

        if (isset($matches[1])) {
            $attr = $this->getAttrs(trim($matches[1]));
        } else {
            $attr = array();
        }

        $output = $this->wiki->addToken(
            $this->rule,
            array(
                'type' => 'list_start',
                'level' => 0,
                'attr' => $attr
            )
        );

        foreach ($this->wiki->getTokens('Heading2') as $key => $val) {

            if ($val[1]['type'] != 'start') {
                continue;
            }

            $options = array(
                'type'  => 'item_start',
                'id'    => $val[1]['id'],
                'level' => $val[1]['level'],
                'count' => $count ++
            );

            $output .= $this->wiki->addToken($this->rule, $options);

            $output .= $val[1]['text'];

            $output .= $this->wiki->addToken(
                $this->rule,
                array(
                    'type' => 'item_end',
                    'level' => $val[1]['level']
                )
            );
        }

        $output .= $this->wiki->addToken(
            $this->rule, array(
                'type' => 'list_end',
                'level' => 0
            )
        );

        return "\n$output\n";
    }
}
