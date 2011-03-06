<?php
/**
 * Horde_SyncMl_DeviceInfo represents a device information set according to the
 * SyncML specification.
 *
 * A DeviceInfo object is created by Horde_SyncMl_Command_Put from an
 * appropriate XML message. Horde_SyncMl_Command_Put directly populates the
 * members variables.
 *
 * The current implementation should handle all DevInf 1.1 DTD elements
 * except <DSMem> entries.
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
class Horde_SyncMl_DeviceInfo
{
    /**
     * The major and minor version identifier of the Device Information DTD.
     *
     * @var string
     */
    public $VerDTD;

    /**
     * The name of the manufacturer of the device.
     *
     * @var string
     */
    public $Man;

    /**
     * The model name or model number of the device.
     *
     * @var string
     */
    public $Mod;

    /**
     * The OEM (Original Equipment Manufacturer) of the device.
     *
     * @var string
     */
    public $OEM;

    /**
     * The firmware version of the device.
     *
     * @var string
     */
    public $FwV;

    /**
     * The software version of the device.
     *
     * @var string
     */
    public $SwV;

    /**
     * The hardware version of the device.
     *
     * @var string
     */
    public $HwV;

    /**
     * The (globally unique) identifier of the source synchronization device.
     *
     * @var string
     */
    public $DevID;

    /**
     * The type of the source synchronization device.
     *
     * @var string
     */
    public $DevTyp;

    /**
     * Array of Horde_SyncMl_DataStore objects.
     *
     * @var array
     */
    public $DataStores = array();

    /**
     * Multidimensional array that specifies the content type capabilities of
     * the device.
     *
     * Example: array('text/x-vcard' => array('FN' => Horde_SyncMl_Property))
     *
     * @var array
     */
    public $CTCaps;

    /**
     * The non-standard, experimental extensions supported by the device.
     *
     * A hash with <XNam> elements as keys and arrays of <XVal> elements as
     * values.
     * Example: array('X-Foo-Bar' => array(1, 'foo'))
     *
     * @var array
     */
    public $Exts;

    /**
     * Whether the device supports UTC based time.
     *
     * @var boolean
     */
    public $UTC;

    /**
     * Whether the device supports handling of large objects.
     *
     * @var boolean
     */
    public $SupportLargeObjs;

    /**
     * Whether the device supports number of changes.
     *
     * @var boolean
     */
    public $SupportNumberOfChanges;

    /**
     * Returns a Horde_SyncMl_DataStore object for a certain source URI.
     *
     * @param string $source URI  A source URI.
     *
     * @return Horde_SyncMl_DataStore  A data store object or null if none found for
     *                           the source URI.
     */
    public function getDataStore($sourceURI)
    {
        foreach ($this->DataStores as $dataStore) {
            if ($dataStore->SourceRef == $sourceURI) {
                return $dataStore;
            }
        }
        return null;
    }
}
