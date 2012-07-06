<?php
/**
 * Exception handler for the Horde_Data package that indicates the wrong
 * charset was provided for the given data..
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @package  Data
 */
class Horde_Data_Exception_Charset extends Horde_Data_Exception
{
    /**
     * Bad charset provided.
     *
     * @var string
     */
    public $badCharset = null;

}
