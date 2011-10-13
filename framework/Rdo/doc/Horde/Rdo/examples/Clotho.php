<?php
/**
 * @package Rdo
 * @subpackage UnitTests
 */

require_once 'Horde/Autoloader.php';

@include './conf.php';
if (empty($conf)) {
    die("No configuration found\n");
}

/* additional things to handle:
-- clotho_resource_availability has a one to many from resources to availabilities
-- clotho_resources.resource_base_calendar is a foreign key to clotho_calendars.calendar_id

    item_parent     INTEGER NOT NULL,           -- item_id of WBS parent in
                                                -- heirarchy

    dependency_lhs_item     INTEGER NOT NULL, -- clotho_wbs_items.item_id
    dependency_rhs_item     INTEGER NOT NULL, -- clotho_wbs_items.item_id
*/


/**
 * Items
 */
class Item extends Horde_Rdo_Base
{
}

/**
 * Item Mapper
 */
class ItemMapper extends Horde_Rdo_Mapper
{
    protected $_relationships = array(
        'parent' => array('type' => Horde_Rdo::ONE_TO_ONE,
                          'foreignKey' => 'item_parent',
                          'mapper' => 'ItemMapper'),
    );

    protected $_lazyRelationships = array(
        'resources' => array('type' => Horde_Rdo::MANY_TO_MANY,
                             'mapper' => 'ResourceMapper',
                             'through' => 'clotho_wbs_resources'),
    );

    protected $_table = 'clotho_wbs_items';
}

/**
 * Dependencies
 */
class Dependency extends Horde_Rdo_Base
{
}

/**
 * Dependency Mapper.
 */
class DependencyMapper extends Horde_Rdo_Mapper
{
    protected $_table = 'clotho_wbs_dependencies';
}

/**
 * Calendars
 */
class Calendar extends Horde_Rdo_Base
{
}

/**
 * Calendar Mapper.
 */
class CalendarMapper extends Horde_Rdo_Mapper
{
    protected $_table = 'clotho_calendars';
}

/**
 * Resources
 */
class Resource extends Horde_Rdo_Base
{
}

/**
 * Resource Mapper.
 */
class ResourceMapper extends Horde_Rdo_Mapper
{
    protected $_lazyRelationships = array(
        'availabilities' => array('type' => Horde_Rdo::ONE_TO_MANY,
                                  'foreignKey' => 'resource_id',
                                  'mapper' => 'ResourceAvailabilityMapper'),
        'items' => array('type' => Horde_Rdo::MANY_TO_MANY,
                         'mapper' => 'ItemMapper',
                         'through' => 'clotho_wbs_resources'),
        );

    protected $_table = 'clotho_resources';
}

/**
 * ResourceAvailability
 */
class ResourceAvailability extends Horde_Rdo_Base
{
}

/**
 * ResourceAvailability Mapper.
 */
class ResourceAvailabilityMapper extends Horde_Rdo_Mapper
{
    protected $_relationships = array(
        'resource' => array('type' => Horde_Rdo::MANY_TO_ONE,
                            'foreignKey' => 'resource_id',
                            'mapper' => 'ResourceMapper'),
        );

    protected $_table = 'clotho_resource_availability';
}
