<?php
/**
 * The Horde_Text_Filter_Tabs2spaces:: converts tabs into spaces.
 *
 * TODO: parameters (breakchar, tabstop)
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Tabs2spaces extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'breakchar' => "\n",
        'tabstop' => 8
    );

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        $lines = explode($this->_params['breakchar'], $text);
        for ($i = 0, $l = count($lines); $i < $l; ++$i) {
            while (($pos = strpos($lines[$i], "\t")) !== false) {
                $new_str = str_repeat(' ', $this->_params['tabstop'] - ($pos % $this->_params['tabstop']));
                $lines[$i] = substr_replace($lines[$i], $new_str, $pos, 1);
            }
        }
        return implode("\n", $lines);
    }

}
