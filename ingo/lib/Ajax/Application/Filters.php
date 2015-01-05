<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Defines AJAX actions used in the Ingo basic filters view.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Ajax_Application_Filters extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Re-sort the filters list.
     *
     * Variables used:
     *   - sort: (string) JSON serialized sort list of rule IDs.
     *
     * @return boolean  True on success.
     */
    public function reSortFilters()
    {
        global $injector, $notification;

        if (!Ingo::hasSharePermission(Horde_Perms::EDIT)) {
            $notification->push(_("You do not have permission to edit filter rules."), 'horde.error');
            return false;
        }

        $storage = $injector->getInstance('Ingo_Factory_Storage')->create();
        $filters = $storage->retrieve(Ingo_Storage::ACTION_FILTERS);

        try {
            $filters->sort(json_decode($this->vars->sort));
            $storage->store($filters);

            $notification->push(_("Rule sort saved successfully."), 'horde.success');
        } catch (Ingo_Exception $e) {
            $notification->push(_("Rule sort not saved."), 'horde.error');
            return false;
        }

        try {
            Ingo_Script_Util::update();
        } catch (Ingo_Exception $e) {
            $notification->push($e, 'horde.warning');
        }

        return true;
    }

}
