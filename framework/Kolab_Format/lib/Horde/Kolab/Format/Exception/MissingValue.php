<?php
/**
 * Indicates a missing value when reading or writing a Kolab Format object.
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
 * Indicates a missing value when reading or writing a Kolab Format object.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Format_Exception_MissingValue
extends Horde_Kolab_Format_Exception
{
    /**
     * The name of the value that was missing.
     *
     * @var string
     */
    private $_value;

    /**
     * Constructor.
     *
     * @param string $value The value that was missing.
     */
    public function __construct($value)
    {
        $this->_value = $value;
        parent::__construct(
            sprintf(
                "Data value for \"%s\" is empty in the Kolab XML object!",
                $value
            )
        );
    }

    /**
     * Return the name of the missing value.
     *
     * @return string The name
     */
    public function getValue()
    {
        return $this->_value;
    }
}