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
    public function aDefaultNonceHasADefinedLengthOf()
    {
        $this->given('the default nonce setup')
            ->when('retrieving a nonce')
            ->then('the nonce has a length of 8 bytes');
    }
}