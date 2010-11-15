<?php
/**
 * Test the Nonce system.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nonce
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Nonce
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Nonce system.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Nonce
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Nonce
 */
class Horde_Nonce_Integration_NonceTest
extends Horde_Nonce_StoryTestCase
{
    /**
     * @scenario
     */
    public function defaultLength()
    {
        $this->given('the default nonce setup')
            ->when('retrieving a nonce')
            ->then('the nonce has a length of 8 bytes');
    }

    /**
     * @scenario
     */
    public function nonceTimeOut()
    {
        $this->given('the default nonce setup')
            ->when('retrieving a nonce')
            ->and('waiting for two seconds')
            ->then('the nonce is invalid given a timeout of one second');
    }

    /**
     * @scenario
     */
    public function nonceWithoutTimeout()
    {
        $this->given('the default nonce setup')
            ->when('retrieving a nonce')
            ->and('waiting for two seconds')
            ->then('the nonce is valid given no timeout');
    }

    /**
     * @scenario
     */
    public function nonceCounterValue()
    {
        $this->given('the default nonce generator')
            ->when('splitting nonce', 'MABBCCDD')
            ->then('the extracted counter value (here: timestamp) is', 1296122434);
    }

    /**
     * @scenario
     */
    public function nonceRandomValue()
    {
        $this->given('the default nonce generator')
            ->when('splitting nonce', 'MABBCCDD')
            ->then('the extracted random part matches', array(1 => 17219, 2 => 17476));
    }

    /**
     * @scenario
     */
    public function nonceHashes()
    {
        $this->given('the default hash setup')
            ->when('hashing nonce', 'MABBCCDD')
            ->then('the hash representation provides the hashes', 62, 165, 118);
    }

    /**
     * @scenario
     */
    public function emptyFilter()
    {
        $this->given('the default filter setup')
            ->when('testing whether a nonce is unused if it has the following counter and hash values', 50, 3, 10, 47)
            ->then('the nonce is unused');
    }

    /**
     * @scenario
     */
    public function lowerCounter()
    {
        $this->given('the default filter setup')
            ->and('the following counter and hash values are marked', 10, 3, 10, 47)
            ->when('testing whether a nonce is unused if it has the following counter and hash values', 50, 3, 10, 47)
            ->then('the nonce is unused');
    }

    /**
     * @scenario
     */
    public function unusedElement()
    {
        $this->given('the default filter setup')
            ->and('the following counter and hash values are marked', 100, 3, 11, 47)
            ->when('testing whether a nonce is unused if it has the following counter and hash values', 50, 3, 10, 47)
            ->then('the nonce is unused');
    }

    /**
     * @scenario
     */
    public function used()
    {
        $this->given('the default filter setup')
            ->and('the following counter and hash values are marked', 100, 3, 10, 47)
            ->when('testing whether a nonce is unused if it has the following counter and hash values', 50, 3, 10, 47)
            ->then('the nonce is used');
    }
}