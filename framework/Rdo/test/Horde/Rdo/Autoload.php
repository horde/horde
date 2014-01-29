<?php
/**
 * Setup autoloading for the tests.
 *
 * PHP version 5
 *
 * Copyright 2012-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Rdo
 * @subpackage UnitTests
 * @author     Ralf Lang <lang@b1-systems.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/** Load Mapper definitions */
require_once __DIR__ . '/Objects/SomeLazyBaseObjectMapper.php';
require_once __DIR__ . '/Objects/SomeLazyBaseObject.php';
require_once __DIR__ . '/Objects/SomeEagerBaseObjectMapper.php';
require_once __DIR__ . '/Objects/SomeEagerBaseObject.php';
require_once __DIR__ . '/Objects/RelatedThingMapper.php';
require_once __DIR__ . '/Objects/RelatedThing.php';
require_once __DIR__ . '/Objects/ManyToManyA.php';
require_once __DIR__ . '/Objects/ManyToManyB.php';
require_once __DIR__ . '/Objects/ManyToManyAMapper.php';
require_once __DIR__ . '/Objects/ManyToManyBMapper.php';
