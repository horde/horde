<?php
/**
 * The Pastie_Entity_PasteMapper class contains all functions related to handling
 * paste object mapping in Pastie.
 *
 * Copyright 2012-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Pastie
 * @license  http://www.horde.org/licenses/bsd BSD
 */

/**
 * The Pastie_Entity_PasteMapper class contains all functions related to handling
 * paste object mapping in Pastie.
 *
 * Copyright 2012-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Ralf Lang <lang@b1-systems.de>
 * @category Horde
 * @package  Pastie
 * @license  http://www.horde.org/licenses/bsd BSD
 */

class Pastie_Entity_PasteMapper extends Horde_Rdo_Mapper
{
    /**
     * Inflector doesn't support Horde-style tables yet
     * @var string
     * @access protected
     */
    protected $_table = 'pastie_pastes';

    /**
     * Relationships loaded on-demand
     * @var array
     * @access protected
     */
    protected $_lazyRelationships = array();

}

