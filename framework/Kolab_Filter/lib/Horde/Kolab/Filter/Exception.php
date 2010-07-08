<?php
/**
 * This class provides the standard error class for the Kolab_Filter package.
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
 * This class provides the standard error class for the Kolab_Filter package.
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
class Horde_Kolab_Filter_Exception
extends Exception
{
    /**
     * Failure constants from postfix src/global/sys_exits.h
     *
     * These are required as exit codes for our communication with postfix.
     */

    /* command line usage error */
    const EX_USAGE = 64;
    /* data format error */
    const EX_DATAERR = 65;
    /* cannot open input */
    const EX_NOINPUT = 66;
    /* user unknown */
    const EX_NOUSER = 67;
    /* host name unknown */
    const EX_NOHOST = 68;
    /* service unavailable */
    const EX_UNAVAILABLE = 69;
    /* internal software error */
    const EX_SOFTWARE = 70;
    /* system resource error */
    const EX_OSERR = 71;
    /* critical OS file missing */
    const EX_OSFILE = 72;
    /* can't create user output file */
    const EX_CANTCREAT = 73;
    /* input/output error */
    const EX_IOERR = 74;
    /* temporary failure */
    const EX_TEMPFAIL = 75;
    /* remote error in protocol */
    const EX_PROTOCOL = 76;
    /* permission denied */
    const EX_NOPERM = 77;
    /* local configuration error */
    const EX_CONFIG = 78;
    
    /**
     * Some output constants.
     *
     * These indicate to the view how an exception should be handled.
     */
    
    const OUT_STDOUT = 128;
    const OUT_LOG = 256;
}
