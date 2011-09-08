<?php
/**
 * A parser variant that does not automatically exit on a parser error.
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
 * A parser variant that does not automatically exit on a parser error.
 *
 * Copyright 2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter_Cli_Parser extends Horde_Argv_Parser
{
    public function parserError($msg)
    {
        return $this->parserExit(0, $msg);
    }

    public function parserExit($status = 0, $msg = null)
    {
        throw new InvalidArgumentException(
            sprintf("%s: error: %s\n", $this->getProgName(), $msg)
        );
    }
}
