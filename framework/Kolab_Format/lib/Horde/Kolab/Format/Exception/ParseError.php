<?php
/**
 * Indicates a parse error when reading a Kolab Format object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * Indicates a parse error when reading a Kolab Format object.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
class Horde_Kolab_Format_Exception_ParseError extends Horde_Kolab_Format_Exception
{
    /**
     * Constructor.
     *
     * @param string $input The input that failed to parse.
     */
    public function __construct($input)
    {
        if (strlen((string) $input) > 50) {
            $output = substr((string) $input, 50) . '... [shortened to 50 characters]';
        } else {
            $output = (string) $input;
        }
        parent::__construct(
            sprintf(
                "Failed parsing Kolab object input data of type %s! Input was:\n%s",
                gettype($input), $output
            )
        );
    }
}