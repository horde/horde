<?php
/**
 * The IMP_Compose_Exception:: class handles exceptions thrown from the
 * IMP_Compose class.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Compose_Exception extends Horde_Exception
{
    /**
     * Stores information on whether an encryption dialog window needs
     * to be opened.
     *
     * @var string
     */
    public $encrypt = null;

}
