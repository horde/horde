<?php
/**
 * The Horde_Kolab_Cli_Data_Ledger:: class deals with ledger data.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */

/**
 * The Horde_Kolab_Cli_Data_Ledger:: class deals with ledger data.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Kolab_Cli
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Cli
 */
class Horde_Kolab_Cli_Data_Ledger
implements Countable
{
    /**
     * The ledger entries.
     *
     * @var array
     */
    private $_entries = array();

    /**
     * Import ledger data from a file.
     *
     * @param string $ledger_file The file to import.
     *
     * @return NULL
     */
    public function importFile($ledger_file)
    {
        $data = simplexml_load_file($ledger_file);
        $this->_entries = $data->entry;
    }

    /**
     * Return the entries as XML strings.
     *
     * @return array An array of XML strings.
     */
    public function asXml()
    {
        $result = array();
        foreach ($this->_entries as $entry) {
            $entry->addAttribute('xmlns:en', 'http://newartisans.com/xml/ledger-en', 'http://www.w3.org/2000/xmlns/');
            $entry->addAttribute('xmlns:tr', 'http://newartisans.com/xml/ledger-tr', 'http://www.w3.org/2000/xmlns/');
            $result[] = strtr($entry->asXML(), array('xmlns:xmlns="http://www.w3.org/2000/xmlns/" ' => ''));
        }
        return $result;
    }

    /**
     * Returns the number of ledger entries.
     *
     * @return int The number of entries.
     */
    public function count()
    {
        return count($this->_entries);
    }
}