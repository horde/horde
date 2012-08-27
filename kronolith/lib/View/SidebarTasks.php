<?php
/**
 * This is a dummy view of Kronolith's sidebar while in tasks mode.
 *
 * The only purpose is to retrieve the result of the "New Task" button.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Kronolith
 */
class Kronolith_View_SidebarTasks extends Horde_View_Sidebar
{
    /**
     * Constructor.
     *
     * @param array $config  Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        $blank = new Horde_Url();
        $this->addNewButton(
            _("_New Task"),
            $blank,
            array('id' => 'kronolithNewTask')
        );
    }
}
