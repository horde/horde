<?php
/**
 * Pastie storage implementation for Horde's Rdo ORM Layer.
 *
 * Required values for $params:<pre>
 *      'db'       The Horde_Db adapter
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 * Based on the original Sql driver by Ben Klang
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author  Ralf Lang <lang@b1-systems.de>
 * @package Pastie
 */
class Pastie_Driver_Rdo extends Pastie_Driver
{
    /**
     * Handle for the database connection.
     * @var DB
     * @access protected
     */
    protected $_db;

    /**
     * The mapper factory
     * @var Horde_Rdo_Factory
     * @access protected
     */
    protected $_mappers;

    /**
     * This is the basic constructor for the Rdo driver.
     *
     * @param array $params  Hash containing the connection parameters.
     */
    public function __construct($params = array())
    {
        $this->_db = $params['db'];
        $this->_mappers = new Horde_Rdo_Factory($this->_db);
    }

    /**
     * Create a new paste in backend.
     * @param string $bin  the paste bin to fill
     * @param string $content  the actual paste content
     * @param string $syntax  the highlighting syntax keyword
     * @param string $title  the title line of the paste
     * @return string uuid of the new paste
     */
    public function savePaste($bin, $content, $syntax = 'none', $title = '')
    {
        $pm = $this->_mappers->create('Pastie_Entity_PasteMapper');
        $bin = 'default'; // FIXME: Allow bins to be Horde_Shares
        $uuid = new Horde_Support_Uuid();

        $paste = $pm->create(array(
                'paste_uuid' => $uuid,
                'paste_bin' => $bin,
                'paste_title' => $title,
                'paste_syntax' => $syntax,
                'paste_content' => $content,
                'paste_owner' => $GLOBALS['registry']->getAuth(), /* Should the driver handle this? */
                'paste_timestamp' => time()
            )

        );

        return $uuid;
    }

    /**
     * Retrieves the paste from the database.
     *
     * @param array $params  Array of selectors to find the paste.
     *
     * @return array  Array of paste information
     */
    public function getPaste($params)
    {
        $pm = $this->_mappers->create('Pastie_Entity_PasteMapper');
        $query = new Horde_Rdo_Query($pm);
        
        // Right now we will accept 'id' or 'uuid'
        if (isset($params['id'])) {
            $query->addTest('paste_id', '=', $params['id']);
        }
        if (isset($params['uuid'])) {
            $query->addTest('paste_uuid', '=', $params['uuid']);
        }
        if (!isset($params['id']) && !isset($params['uuid'])) {
            Horde::logMessage('Error: must specify some kind of unique id.', 'err');
            throw new Pastie_Exception(_("Internal error.  Details have been logged for the administrator."));
        }

        $paste = $pm->findOne($query);
        if ($paste) {
            return $this->_fromBackend($paste);
        } else {
            throw new Pastie_Exception(_("Invalid paste ID."));
        }
    }

    /**
     * get any number of pastes from a bin, ordered by date, narrowed by limit and offset
     * @param string  $bin  A paste bin to query
     * @param integer $limit  a maximum of pastes to retrieve (optional, default to null = all)
     * @param integer start  a number of pastes to skip before retrieving (optional, default to null = begin with first)
     * @return array  a list of pastes
     */
    public function getPastes($bin, $limit = null, $start = null)
    {
        $pm = $this->_mappers->create('Pastie_Entity_PasteMapper');
        $query = new Horde_Rdo_Query($pm);
        $query->sortBy('paste_timestamp DESC');
        if ($limit !== null) {
             if ($start === null) {
                 $start = 0;
             }
            $query->limit($limit, $start);
        }
        $pastes = array();
            foreach ($pm->find($query) as $paste) {
                $pastes[$paste['paste_uuid']] = $this->_fromBackend($paste);
            }
        return $pastes; 
    }

    /**
     * Convert a backend hash or object to an application context hash.
     * This is ugly and may be redesigned or refactored
     * @param array|Pastie_Entity_Paste $paste  A paste hash or Rdo object.
     * @return an application context hash
     */
    protected function _fromBackend($paste) {
        return array(
            'id' => $paste['paste_id'],
            'uuid' => $paste['paste_uuid'],
            'bin' => $paste['paste_bin'],
            'title' => $paste['paste_title'],
            'syntax' => $paste['paste_syntax'],
            'paste' => $paste['paste_content'],
            'owner' => $paste['paste_owner'],
            'timestamp' => new Horde_Date($paste['paste_timestamp'])
        );
    }
}
