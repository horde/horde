<?php
/**
 * Indicates a parse error when reading a Kolab Format object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Indicates a parse error when reading a Kolab Format object.
 *
 * Copyright 2009-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Exception_ParseError
extends Horde_Kolab_Format_Exception
{
    /**
     * The input that failed to parse.
     *
     * @var resource
     */
    private $_input;

    /**
     * Constructor.
     *
     * @param string $input The input that failed to parse.
     */
    public function __construct($input)
    {
        if (strlen((string) $input) > 50) {
            $output = substr((string) $input, 0, 50)
                . '... [shortened to 50 characters]';
        } else {
            $output = (string) $input;
        }
        $this->_input = $input;
        parent::__construct(
            sprintf(
                "Failed parsing Kolab object input data of type %s! Input was:\n%s",
                gettype($input), $output
            )
        );
    }

    /**
     * Return the complete input.
     *
     * @return resource The input that failed to parse.
     */
    public function getInput()
    {
        return $this->_input;
    }
}