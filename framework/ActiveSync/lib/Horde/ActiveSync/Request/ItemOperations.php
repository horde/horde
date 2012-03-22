<?php
/**
 * Horde_ActiveSync_Request_ItemOperations
 *
 * PHP Version 5
 *
 * Contains portions of code from ZPush
 * Zarafa Deutschland GmbH, www.zarafaserver.de
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
/**
 * ActiveSync Handler for ItemOperations requests
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 * @copyright 2012 Horde LLC (http://www.horde.org/)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @link      http://pear.horde.org/index.php?package=ActiveSync
 * @package   ActiveSync
 */
class Horde_ActiveSync_Request_ItemOperations extends Horde_ActiveSync_Request_Base
{
    const ITEMOPERATIONS_ITEMOPERATIONS     = 'ItemOperations:ItemOperations';
    const ITEMOPERATIONS_FETCH              = 'ItemOperations:Fetch';
    const ITEMOPERATIONS_STORE              = 'ItemOperations:Store';
    const ITEMOPERATIONS_OPTIONS            = 'ItemOperations:Options';
    const ITEMOPERATIONS_RANGE              = 'ItemOperations:Range';
    const ITEMOPERATIONS_TOTAL              = 'ItemOperations:Total';
    const ITEMOPERATIONS_PROPERTIES         = 'ItemOperations:Properties';
    const ITEMOPERATIONS_DATA               = 'ItemOperations:Data';
    const ITEMOPERATIONS_STATUS             = 'ItemOperations:Status';
    const ITEMOPERATIONS_RESPONSE           = 'ItemOperations:Response';
    const ITEMOPERATIONS_VERSION            = 'ItemOperations:Version';
    const ITEMOPERATIONS_SCHEMA             = 'ItemOperations:Schema';
    const ITEMOPERATIONS_PART               = 'ItemOperations:Part';
    const ITEMOPERATIONS_EMPTYFOLDERCONTENT = 'ItemOperations:EmptyFolderContent';
    const ITEMOPERATIONS_DELETESUBFOLDERS   = 'ItemOperations:DeleteSubFolders';
    const ITEMOPERATIONS_USERNAME           = 'ItemOperations:UserName';
    const ITEMOPERATIONS_PASSWORD           = 'ItemOperations:Password';

    /**
     * Handle the request.
     *
     * @return boolean
     */
    protected function _handle()
    {

    }

}