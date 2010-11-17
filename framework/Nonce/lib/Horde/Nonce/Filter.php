<?php
/**
 * Generates nonces.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Nonce
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Nonce
 */

/**
 * Generates nonces.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Nonce
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Nonce
 */
class Horde_Nonce_Filter
{
    private $_filter = array();

    public function isUsed($counter, $hashes)
    {
        $unused_checks = 0;
        foreach ($hashes as $hash) {
            if (!isset($this->_filter[$hash]) || $counter > $this->_filter[$hash]) {
                $unused_checks++;
            }
        }
        foreach ($hashes as $hash) {
            if (!isset($this->_filter[$hash]) || $counter > $this->_filter[$hash]) {
                $this->_filter[$hash] = $counter;
            }
        }
        if ($unused_checks > 0) {
            return false;
        } else {
            return true;
        }
    }
}