<?php
/**
 * This class provides a data structure for storing a virtual folder.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Search_Vfolder extends IMP_Search_Query
{
    /**
     * Display this virtual folder in the preferences screen?
     *
     * @var boolean
     */
    public $prefDisplay = true;

}
