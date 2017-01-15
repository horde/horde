<?php
/**
 * Copyright 2016-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */

/**
 * Takes HTML and removes any MS Office formatting quirks.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Text_Filter
 */
class Horde_Text_Filter_Msoffice extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'charset' => 'UTF-8',
    );

    /**
     * Executes any code necessary after applying the filter patterns.
     *
     * @param string $text  The text after the filtering.
     *
     * @return string  The modified text.
     */
    public function postProcess($text)
    {
        // We cannot find those elements via DOM because HTML doesn't know
        // about namespaces.
        $text = str_replace('<o:p>&nbsp;</o:p>', '', $text);

        try {
            $dom = new Horde_Domhtml($text, $this->_params['charset']);
        } catch (Exception $e) {
            return $text;
        }

        // Replace all <p> elements of class "MsoNormal" with <br> elements,
        // unless they contain other classes. Then replace with <div> elements.
        foreach ($dom as $child) {
            if ($child instanceof DOMElement &&
                Horde_String::lower($child->tagName) == 'p') {
            }
            if (!($child instanceof DOMElement) ||
                Horde_String::lower($child->tagName) != 'p' ||
                !($css = $child->getAttribute('class')) ||
                strpos($css, 'MsoNormal') === false) {
                continue;
            }
            $css = trim(str_replace('MsoNormal', '', $css));
            if (strlen($css)) {
                $div = $dom->dom->createElement('div');
                $div->setAttribute('class', $css);
                foreach ($child->childNodes as $subchild) {
                    $div->appendChild($subchild);
                }
                $child->parentNode->insertBefore($div, $child);
            } elseif (strlen(preg_replace('/^\s*(.*)\s*$/u', '$1', $child->textContent))) {
                while ($child->hasChildNodes()) {
                    $tomove = $child->removeChild($child->firstChild);
                    $child->parentNode->insertBefore($tomove, $child);
                }
                $child->parentNode->insertBefore(
                    $dom->dom->createElement('br'), $child
                );
            }
            $child->parentNode->removeChild($child);
        }

        return $dom->returnHtml(array('charset' => $this->_params['charset']));
    }
}