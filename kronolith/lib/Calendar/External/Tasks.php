<?php
/**
 * Kronolith_Calendar_External_Tasks defines an API for single task lists.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_Calendar_External_Tasks extends Kronolith_Calendar_External
{
    /**
     * The share of this task list.
     *
     * @var Horde_Share_Object
     */
    protected $_share;

    /**
     * Constructor.
     *
     * @param array $params  A hash with any parameters that this calendar
     *                       might need.
     *                       Required parameters:
     *                       - share: The share of this calendar.
     */
    public function __construct($params = array())
    {
        if (!isset($params['share'])) {
            throw new BadMethodCallException('share parameter is missing');
        }
        Kronolith_Calendar::__construct($params);
    }

    /**
     * Returns a hash representing this calendar.
     *
     * @return array  A simple hash.
     */
    public function toHash()
    {
        global $calendar_manager, $conf, $injector, $registry;

        $owner = $registry->getAuth() &&
            $this->_share->get('owner') == $registry->getAuth();

        $hash = parent::toHash();
        $hash['name']  = Kronolith::getLabel($this->_share);
        $hash['desc']  = (string)$this->_share->get('desc');
        $hash['owner'] = $owner;
        $hash['users'] = Kronolith::listShareUsers($this->_share);
        $hash['fg']    = Kronolith::foregroundColor($this->_share);
        $hash['bg']    = Kronolith::backgroundColor($this->_share);
        $hash['show']  = in_array(
            'tasks/' . $this->_share->getName(),
            $calendar_manager->get(Kronolith::DISPLAY_EXTERNAL_CALENDARS)
        );
        $hash['edit']  = $this->_share->hasPermission(
            $registry->getAuth(),
            Horde_Perms::EDIT
        );
        try {
            $hash['caldav'] = Horde::url(
                $registry->get('webroot', 'horde')
                    . ($conf['urls']['pretty'] == 'rewrite'
                        ? '/rpc/calendars/'
                        : '/rpc.php/calendars/'),
                true,
                -1
            )
                . $registry->convertUsername($registry->getAuth(), false) . '/'
                . $injector->getInstance('Horde_Dav_Storage')
                    ->getExternalCollectionId($this->_share->getName(), 'tasks')
                . '/';
        } catch (Horde_Exception $e) {
        }
        $hash['sub'] = Horde::url(
            $registry->get('webroot', 'horde')
                . ($conf['urls']['pretty'] == 'rewrite'
                    ? '/rpc/nag/'
                    : '/rpc.php/nag/'),
            true,
            -1
        )
            . ($this->_share->get('owner')
                ? $registry->convertUsername($this->_share->get('owner'), false)
                : '-system-')
            . '/'
            . $this->_share->getName() . '.ics';
        if ($owner) {
            $hash['perms'] = Kronolith::permissionToJson($this->_share->getPermission());
        }

        return $hash;
    }
}
