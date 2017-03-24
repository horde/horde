<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * This package is ported from Python's Optik (http://optik.sourceforge.net/).
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Argv
 */

/**
 * An option group allows to group a number of options under a common header
 * and description.
 *
 * @category  Horde
 * @package   Argv
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Mike Naberezny <mike@maintainable.com>
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 */
class Horde_Argv_OptionGroup extends Horde_Argv_OptionContainer
{
    protected $_title;

    public function __construct($parser, $title, $description = null)
    {
        $this->parser = $parser;
        parent::__construct($parser->optionClass, $parser->conflictHandler, $description);
        $this->_title = $title;
    }

    protected function _createOptionList()
    {
        $this->optionList = array();
        $this->_shareOptionMappings($this->parser);
    }

    public function setTitle($title)
    {
        $this->_title = $title;
    }

    public function __destruct()
    {
        unset($this->optionList);
    }

    // -- Help-formatting methods ---------------------------------------

    public function formatHelp($formatter = null)
    {
        if (is_null($formatter))
            return '';

        $result = $formatter->formatHeading($this->_title);
        $formatter->indent();
        $result .= parent::formatHelp($formatter);
        $formatter->dedent();
        return $result;
    }

}
