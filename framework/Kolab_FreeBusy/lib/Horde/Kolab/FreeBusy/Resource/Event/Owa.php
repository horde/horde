<?php
/**
 * This class allows fetching free/busy information from a Microsoft Exchange
 * server via OWA.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Mathieu Parent <math.parent@gmail.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * This class allows fetching free/busy information from a Microsoft Exchange
 * server via OWA.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Mathieu Parent <math.parent@gmail.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Resource_Event_Owa
implements Horde_Kolab_FreeBusy_Resource_Event
{
    /**
     * The owner of the free/busy data.
     *
     * @var Horde_Kolab_FreeBusy_Owner_Freebusy
     */
    private $_owner;

    /**
     * The HTTP client for fetching the free/busy data.
     *
     * @var Horde_Http_Client
     */
    private $_client;

    /**
     * The owner of the free/busy data.
     *
     * @var Horde_Kolab_FreeBusy_Owner_Freebusy
     */
    private $_params;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Owner_Freebusy $owner  The resource owner.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Owner $owner, $params = array()
    )
    {
        if (!isset($params['url'])) {
            throw new Horde_Kolab_FreeBusy_Exception(
                'The URL for the exchange server has been left undefined!'
            );
        }
        if (!isset($params['interval'])) {
            $params['interval'] = 30;
        }
        if (!isset($params['client'])) {
            $this->_client = new Horde_Http_Client();
        } else {
            $this->_client = $params['client'];
        }
        $this->_owner  = $owner;
        $this->_params = $params;
    }

    /**
     * Return the owner of the resource.
     *
     * @return Horde_Kolab_FreeBusy_Owner The resource owner.
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Return the name of the resource.
     *
     * @return string The name for the resource.
     */
    public function getName()
    {
        return $this->_owner->getOwner() . '@' . $this->_params['url'];
    }

    /**
     * Return for whom this resource exports relevant data.
     *
     * @return string The user type the exported data of this resource is
     *                relevant for.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the relevance
     *                                        information failed.
     *
     * @todo It would be nice if we would not only have the free/busy specific
     * relevance but a generic way of setting the relevance of resources.
     */
    public function getRelevance()
    {
        return 'admins';
    }

    /**
     * Fetch the resource ACL.
     *
     * @return array ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the ACL information
     *                                        failed.
     */
    public function getAcl()
    {
        return array();
    }

    /**
     * Fetch the access controls on specific attributes of this
     * resource.
     *
     * @return array Attribute ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the attribute ACL
     *                                        information failed.
     *
     * @todo It would be nice if we would not only have the free/busy specific
     * attribute acls but a generic way of setting attribute ACL for resources.
     */
    public function getAttributeAcl()
    {
        return array();
    }

    /**
     * Lists all events in the given time range.
     *
     * @param Horde_Date $startDate Start of range date object.
     * @param Horde_Date $endDate   End of range data object.
     *
     * @return array Events in the given time range.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the events failed.
     */
    public function listEvents(Horde_Date $startDate, Horde_Date $endDate)
    {
        $url = $this->_params['url'] . '/public/?cmd=freebusy'.
            '&start=' . $startDate->format('c') .
            '&end=' . $endDate->format('c') .
            '&interval=' . $this->_params['interval'] .
            '&u=SMTP:' . $this->_owner->getOwner();
        
        $response = $this->_client->get(
            $url,
            array(
                'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.2; .NET CLR 1.1.4322)'
            )
        );
        if ($response->code !== 200) { 
            throw new Horde_Kolab_FreeBusy_Exception_NotFound(
                sprintf('Unable to fetch free/busy information from %s', $url)
            );
        }
        $owa = new Horde_Kolab_FreeBusy_Freebusy_Helper_Owa(
            $response->getStream()
        );
        $result = $owa->convert(
            $startDate, $endDate, $this->_params['interval']
        );
        if (!isset($result[$this->_owner->getOwner()])) {
            return array();
        }
        $events = array();
        foreach ($result[$this->_owner->getOwner()] as $item) {
            $events[] = new Horde_Kolab_FreeBusy_Object_Event($item);
        }
        return $events;
    }
}
