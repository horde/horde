<?php
/**
 * This class provides an error thrown when an I/O error occured.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/**
 * This class provides an error thrown when an I/O error occured.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Exception_IoError
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
            Horde_Kolab_Filter_Exception::OUT_LOG |
            Horde_Kolab_Filter_Exception::EX_IOERR,
            $previous
        );
    }
}
