<?php
/**
 * The Ingo_Script_Ispconfig:: class represents an ISPConfig Vacation "script generator".
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael Bunk <mb@computer-leipzig.com>
 * @package Ingo
 */
class Ingo_Script_Ispconfig extends Ingo_Script
{
    /**
     * The categories of filtering allowed.
     *
     * @var array
     */
    protected $_categories = array(
        Ingo_Storage::ACTION_VACATION,
    );

    protected $_categoryFeatures = array(
        Ingo_Storage::ACTION_VACATION => array('period', 'reason'),
    );

    /**
     * Does the driver require a script file to be generated?
     *
     * We don't generate a script here, but if $_scriptfile isn't true,
     * we don't get called at all.
     *
     * @var boolean
     */
    protected $_scriptfile = true;

    /**
     * Generates the "script"
     *
     * @return string  The script.
     * @throws Ingo_Exception
     */
    public function generate()
    {
        $filters = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_FILTERS);

        foreach ($filters->getFilterList() as $filter) {
            switch ($filter['action']) {
            case Ingo_Storage::ACTION_VACATION:
                // save for additionalScripts()
                $this->_disable = !empty($filter['disable']);
                break;
            }
        }

        return '';
    }


    /**
     * Returns any additional scripts that need to be sent to the transport
     * layer.
     *
     * @return array  A list of scripts with script names as keys and script
     *                code as values.
     */
    public function additionalScripts()
    {
        $vacation = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_VACATION);
        return array(
            'vacation' => $vacation,
            'disable' => $this->_disable
        );
    }
}
