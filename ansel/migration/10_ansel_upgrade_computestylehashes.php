<?php
/**
 * Ensures that all known style definitions have a hash entry.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Ansel
 */
class AnselUpgradeComputestylehashes extends Horde_Db_Migration_Base
{
    public function up()
    {
        // Migrate existing data for share
        $sql = 'SELECT attribute_style, share_id FROM ansel_shares';
        $this->announce('Computing style hashes from ansel_shares.', 'cli.message');
        $rows = $this->_connection->selectAll($sql);
        $this->_ensureHashes($rows);

         // Migrate existing data for shareng
        $sql = 'SELECT attribute_style, share_id FROM ansel_sharesng';
        $this->announce('Computing style hashes from ansel_sharesng.', 'cli.message');
        $rows = $this->_connection->selectAll($sql);
        $this->_ensureHashes($rows);
    }

    /**
     * Downgrade, though all style information will be lost and reverted to
     * 'ansel_default'.
     */
    public function down()
    {
        // noop
    }

    protected function _ensureHashes($rows)
    {
        foreach ($rows as $row) {
            $style = unserialize($row['attribute_style']);
            if (!$style instanceof Ansel_Style) {
                $this->announce('ERROR: Not a valid Ansel_Style object for gallery_id: ' . $row['share_id'] . ': ' . print_r($style, true));
                continue;
            }
            try {
                $this->_ensureHash($style->getHash());
            } catch (Exception $e) {
                $this->announce('ERROR: ' . $e->getMessage());
            }
        }
    }

    /**
     * Ensure the style hash is recorded in the database.
     *
     * @param string $hash  The hash to record.
     */
    protected function _ensureHash($hash)
    {
        $query = 'SELECT COUNT(*) FROM ansel_hashes WHERE style_hash = ?';
        $results = $this->_connection->selectValue($query, array($hash));

        if (!$results) {
            $this->_connection->insert('INSERT INTO ansel_hashes (style_hash) VALUES(?)', array($hash));
        }
    }
}