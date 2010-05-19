<?php
/**
 * The IMP_Compose_Exception:: class handles exceptions thrown from the
 * IMP_Compose class.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Compose_Exception extends IMP_Exception
{
    /**
     * Stores information on whether an encryption dialog window needs
     * to be opened.
     *
     * @var string
     */
    public $encrypt = null;

    /**
     * If set, indicates that this identity matches the given to address.
     *
     * @var integer
     */
    public $tied_identity = null;

}
