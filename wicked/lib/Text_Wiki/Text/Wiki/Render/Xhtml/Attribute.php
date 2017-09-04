<?php
/**
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Attribute extends Text_Wiki_Render
{
    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options  The "options" portion of the token (second
     *                        element).
     *
     * @return string  The text rendered from the token options.
     */
    public function token($options)
    {
        $output = '<table width="100%" class="attributes"><tbody>';

        foreach ($options['attributes'] as $attribute) {

            $link = array('page' => $attribute['name'],
                          'anchor' => '',
                          'text' => $attribute['name']);

            // We should do full wiki formatting, I guess, but there isn't
            // a convenient way to do it.
            if (preg_match('/^(' . Wicked::REGEXP_WIKIWORD . ')$/',
                           $attribute['value'], $matches)) {
                $vlink = array('page' => $matches[1],
                               'anchor' => '',
                               'text' => $matches[1]);
                $value = $this->wiki->renderObj['Wikilink']->token($vlink);
            } elseif (preg_match('/^\(\((.*)\)\)$/', $attribute['value'],
                                 $matches)) {
                $vlink = array('page' => $matches[1],
                               'anchor' => '',
                               'text' => $matches[1]);
                $value = $this->wiki->renderObj['Wikilink']->token($vlink);
            } else {
                $value = htmlspecialchars($attribute['value']);
            }

            $output .= '<tr><td width="1%" nowrap="nowrap"><strong><em>' .
                       $this->wiki->renderObj['Wikilink']->token($link) .
                       ' :</em></strong></td><td>' . $value .
                       '</td></tr>';
        }

        $output .= '</tbody></table>';

        return $output;
    }
}
