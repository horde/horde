<?php
/**
 * The Horde_SyncMl_DataStore class describes one of the possible datastores
 * (i.e. databases) of the device.
 *
 * Most important attributes are the preferred MIME Types for sending and
 * receiving data for this datastore: $Tx_Pref and $Rx_Pref.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package SyncMl
 */
class Horde_SyncMl_DataStore
{
    /**
     * The local URI of the datastore.
     *
     * @var string
     */
    public $SourceRef;

    /**
     * The display name of the datastore
     *
     * @var string
     */
    public $DisplayName;

    /**
     * The maximum size of a global unique identifier for the datastore in
     * bytes.
     *
     * @var integer
     */
    public $MaxGUIDSize;

    /**
     * The preferred types and versions of a content type received by the
     * device.
     *
     * The content types (CTType) are the keys, the versions (VerCT) are the
     * values.
     *
     * @var array
     */
    public $Rx_Pref = array();

    /**
     * The supported types and versions of a content type received by the
     * device.
     *
     * The content types (CTType) are the keys, the versions (VerCT) are the
     * values.
     *
     * @var array
     */
    public $Rx = array();

    /**
     * The preferred types and versions of a content type transmitted by the
     * device.
     *
     * The content types (CTType) are the keys, the versions (VerCT) are the
     * values.
     *
     * @var array
     */
    public $Tx_Pref = array();

    /**
     * The supported types and versions of a content type transmitted by the
     * device.
     *
     * The content types (CTType) are the keys, the versions (VerCT) are the
     * values.
     *
     * @var array
     */
    public $Tx = array();

    /**
     * The maximum memory and item identifier for the datastore.
     *
     * Not implemented yet.
     */
    public $DSMem;

    /**
     * The synchronization capabilities of the datastore.
     *
     * The synchronization types (SyncType) are stored in the keys of the
     * hash.
     *
     * @var array
     */
    public $SyncCap = array();

    /**
     * Returns the preferred content type the client wants to receive.
     *
     * @return string  The device's preferred content type or null if not
     *                 specified (which is not allowed).
     */
    public function getPreferredRXContentType()
    {
        reset($this->Rx_Pref);
        return key($this->Rx_Pref);
    }

    /**
     * Returns the version of the preferred content type the client wants to
     * receive.
     *
     * @return string  The device's preferred content type version or null if
     *                 not specified (which is not allowed).
     */
    public function getPreferredRXContentTypeVersion()
    {
        return reset($this->Rx_Pref);
    }
}
