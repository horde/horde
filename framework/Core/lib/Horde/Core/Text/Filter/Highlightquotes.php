<?php
/**
 * Class that extends the base Highlightquotes class to allow toggling of
 * quoteblocks via javascript.
 *
 * CSS class names "toggleQuoteHide" and "toggleQuoteShow" are used to style
 * toggle text.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Text_Filter_Highlightquotes extends Horde_Text_Filter_Highlightquotes
{
     /**
     * Constructor.
     *
     * @param array $params  Parameters that the filter instance needs.
     *                       Additional Parameters to base driver:
     * <pre>
     * 'noJS' - (boolean) Don't add javascript toggle code.
     *          DEFAULT: false
     * 'outputJS' - (boolean) Add necessary JS files?
     *              DEFAULT: true
     * </pre>
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array(
            'noJS' => false,
            'outputJS' => true
        ), $params);

        parent::__construct($params);

        if (!$this->_params['noJS'] && $this->_params['outputJS']) {
            Horde::addScriptFile('prototype.js', 'horde');
        }
    }

    /**
     * Add HTML code at the beginning of a large block of quoted lines.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  HTML code.
     */
    protected function _beginLargeBlock($lines, $qcount)
    {
        if ($this->_params['noJS']) {
            return '';
        }

        return (($this->_params['citeblock']) ? '<br />' : '') .
            '<div class="toggleQuoteParent">' .
            '<span ' . ($this->_params['outputJS'] ? 'onclick="[ this, this.next(), this.next(1) ].invoke(\'toggle\')" ' : '') .
            'class="widget toggleQuoteShow"' . ($this->_params['hideBlocks'] ? '' : ' style="display:none"') . '>' . htmlspecialchars(sprintf(_("[Show Quoted Text - %d lines]"), $qcount)) . '</span>' .
            '<span ' . ($this->_params['outputJS'] ? 'onclick="[ this, this.previous(), this.next() ].invoke(\'toggle\')" ' : "") .
            'class="widget toggleQuoteHide"' . ($this->_params['hideBlocks'] ? ' style="display:none"' : '') . '>' . htmlspecialchars(_("[Hide Quoted Text]")) . '</span>';
    }

    /**
     * Add HTML code at the end of a large block of quoted lines.
     *
     * @param array $lines     Lines.
     * @param integer $qcount  Number of lines in quoted level.
     *
     * @return string  HTML code.
     */
    protected function _endLargeBlock($lines, $qcount)
    {
        return $this->_params['noJS']
            ? ''
            : '</div>';
    }

}
