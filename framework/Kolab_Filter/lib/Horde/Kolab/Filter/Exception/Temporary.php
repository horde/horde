<?php
/**
 * This class provides an error thrown when a potentially temporary failure
 * occured.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * This class provides an error thrown when a potentially temporary failure
 * occured.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Exception_Temporary
extends Horde_Kolab_Filter_Exception
{
    /**
     * Construct the exception
     *
     * @param string $msg
     * @param Exception $previous
     */
    public function __construct($msg = '', Exception $previous = null)
    {
        parent::__construct(
            $msg,
            Horde_Kolab_Filter_Exception::OUT_STDOUT |
            Horde_Kolab_Filter_Exception::EX_TEMPFAIL,
            $previous
        );
    }
}
