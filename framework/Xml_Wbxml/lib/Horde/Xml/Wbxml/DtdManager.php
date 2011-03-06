<?php
/**
 * From Binary XML Content Format Specification Version 1.3, 25 July 2001
 * found at http://www.wapforum.org
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anthony Mills <amills@pyramid6.com>
 * @package Xml_Wbxml
 */
class Horde_Xml_Wbxml_DtdManager
{
    /**
     * @var array
     */
    protected $_strDTD = array();

    /**
     * @var array
     */
    protected $_strDTDURI = array();

    /**
     */
    public function __construct()
    {
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::SYNCML_1_0, 'syncml:syncml1.0', new Horde_Xml_Wbxml_Dtd_SyncMl(0));
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::SYNCML_1_1, 'syncml:syncml1.1', new Horde_Xml_Wbxml_Dtd_SyncMl(1));
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::SYNCML_1_2, 'syncml:syncml1.2', new Horde_Xml_Wbxml_Dtd_SyncMl(2));

        $this->registerDTD(Horde_Xml_Wbxml_Dtd::METINF_1_0, 'syncml:metinf1.0', new Horde_Xml_Wbxml_Dtd_SyncMlMetInf(0));
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::METINF_1_1, 'syncml:metinf1.1', new Horde_Xml_Wbxml_Dtd_SyncMlMetInf(1));
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::METINF_1_2, 'syncml:metinf1.2', new Horde_Xml_Wbxml_Dtd_SyncMlMetInf(2));

        $this->registerDTD(Horde_Xml_Wbxml_Dtd::DEVINF_1_0, 'syncml:devinf1.0', new Horde_Xml_Wbxml_Dtd_SyncMlDevInf(0));
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::DEVINF_1_1, 'syncml:devinf1.1', new Horde_Xml_Wbxml_Dtd_SyncMlDevInf(1));
        $this->registerDTD(Horde_Xml_Wbxml_Dtd::DEVINF_1_2, 'syncml:devinf1.2', new Horde_Xml_Wbxml_Dtd_SyncMlDevInf(2));
    }

    /**
     */
    public function getInstance($publicIdentifier)
    {
        $publicIdentifier = strtolower($publicIdentifier);
        if (isset($this->_strDTD[$publicIdentifier])) {
            return $this->_strDTD[$publicIdentifier];
        }
        return null;
    }

    /**
     */
    public function getInstanceURI($uri)
    {
        $uri = strtolower($uri);

        // some manual hacks:
        if ($uri == 'syncml:syncml') {
            $uri = 'syncml:syncml1.0';
        }
        if ($uri == 'syncml:metinf') {
            $uri = 'syncml:metinf1.0';
        }
        if ($uri == 'syncml:devinf') {
            $uri = 'syncml:devinf1.0';
        }

        if (isset($this->_strDTDURI[$uri])) {
            return $this->_strDTDURI[$uri];
        }
        return null;
    }

    /**
     */
    public function registerDTD($publicIdentifier, $uri, $dtd)
    {
        $dtd->setDPI($publicIdentifier);

        $publicIdentifier = strtolower($publicIdentifier);

        $this->_strDTD[$publicIdentifier] = $dtd;
        $this->_strDTDURI[strtolower($uri)] = $dtd;
    }
}
