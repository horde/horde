<?php
/**
 * Ensures that all known style definitions have a hash entry.
 *
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
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
        $GLOBALS['registry']->pushApp('ansel');

        // Migrate existing data for share
        $sql = 'SELECT attribute_style FROM ansel_shares';
        $this->announce('Computing style hashes from ansel_shares.', 'cli.message');
        $styles = $this->_connection->selectValues($sql);
        $this->_ensureHashes($styles);

         // Migrate existing data for shareng
        $sql = 'SELECT attribute_style FROM ansel_sharesng';
        $this->announce('Computing style hashes from ansel_sharesng.', 'cli.message');
        $styles = $this->_connection->selectValues($sql);
        $this->_ensureHashes($styles);
    }

    /**
     * Downgrade, though all style information will be lost and reverted to
     * 'ansel_default'.
     */
    public function down()
    {
        // noop
    }

    protected function _ensureHashes($styles)
    {
        foreach ($styles as $style) {
            $style = unserialize($style);
            try {
                $GLOBALS['injector']
                    ->getInstance('Ansel_Storage')
                    ->ensureHash($style->getHash());
            } catch (Exception $e) {
                $this->announce('ERROR: ' . $e->getMessage());
            }
        }
    }
}