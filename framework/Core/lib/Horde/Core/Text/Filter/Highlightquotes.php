<?php
/**
 * Class that extends the base Highlightquotes class to allow toggling of
 * quoteblocks via javascript.
 *
 * CSS class names "toggleQuoteHide" and "toggleQuoteShow" are used to style
 * toggle text.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Text_Filter_Highlightquotes extends Horde_Text_Filter_Highlightquotes
{
    /**
     * @param array $params  Additional Parameters to base driver:
     * <pre>
     * 'noJS' - (boolean) Don't add javascript toggle code.
     *          DEFAULT: false
     * </pre>
     */
    public function __construct(array $params = array())
    {
        if (empty($params['noJS'])) {
            $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');
            $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
            $page_output->addScriptFile('toggle_quotes.js', 'horde');
        }

        parent::__construct($params);
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
        return (($this->_params['citeblock']) ? '<br />' : '') .
            '<div class="toggleQuoteParent">' .
            '<span class="widget toggleQuoteShow"' . ($this->_params['hideBlocks'] ? '' : ' style="display:none"') . '>' . htmlspecialchars(sprintf(Horde_Core_Translation::t("[Show Quoted Text - %d lines]"), $qcount)) . '</span>' .
            '<span class="widget toggleQuoteHide"' . ($this->_params['hideBlocks'] ? ' style="display:none"' : '') . '>' . htmlspecialchars(Horde_Core_Translation::t("[Hide Quoted Text]")) . '</span>';
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
        return '</div>';
    }

}
