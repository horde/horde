<?php
/**
 * Defines AJAX calls used to interact with Horde Groups.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Ajax_Application_Handler_Groups extends Horde_Core_Ajax_Application_Handler
{
    /**
     * Returns a hash of group IDs and group names that the user has access
     * to.
     *
     * @return object  Object with the following properties:
     *   - groups: (array) Groups hash.
     */
    public function listGroups()
    {
        $result = new stdClass;

        try {
            $groups = $GLOBALS['injector']
                ->getInstance('Horde_Group')
                ->listAll(empty($GLOBALS['conf']['share']['any_group'])
                          ? $GLOBALS['registry']->getAuth()
                          : null);
            if ($groups) {
                asort($groups);
                $result->groups = $groups;
            }
        } catch (Horde_Group_Exception $e) {
            Horde::logMessage($e);
        }

        return $result;
    }

}
