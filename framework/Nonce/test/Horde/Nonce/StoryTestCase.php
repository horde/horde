<?php
/**
 * Base for story based package testing.
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
 * Base for story based package testing.
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
class Horde_Nonce_StoryTestCase
extends PHPUnit_Extensions_Story_TestCase
{
    /**
     * Handle a "given" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runGiven(&$world, $action, $arguments)
    {
        switch($action) {
        case 'the default nonce setup':
            $world['nonce_handler'] = new Horde_Nonce(
                new Horde_Nonce_Generator(),
                new Horde_Nonce_Hash()
            );
            break;
        case 'the default hash setup':
            $world['nonce_hash'] = new Horde_Nonce_Hash();
        case 'the default nonce generator':
            $world['nonce_generator'] = new Horde_Nonce_Generator();
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Handle a "when" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runWhen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'retrieving a nonce':
            $world['nonce'] = $world['nonce_handler']->create();
            break;
        case 'waiting for two seconds':
            sleep(2);
            break;
        case 'splitting nonce':
            list($timestamp, $random) = $world['nonce_generator']->split($arguments[0]);
            $world['timestamp'] = $timestamp;
            $world['random'] = $random;
            break;
        case 'hashing nonce':
            list($timestamp, $random) = $world['nonce_generator']->split($arguments[0]);
            $world['hashes'] = $world['nonce_hash']->hash($random);
            break;
        default:
            return $this->notImplemented($action);
        }
    }

    /**
     * Handle a "then" step.
     *
     * @param array  &$world    Joined "world" of variables.
     * @param string $action    The description of the step.
     * @param array  $arguments Additional arguments to the step.
     *
     * @return mixed The outcome of the step.
     */
    public function runThen(&$world, $action, $arguments)
    {
        switch($action) {
        case 'the nonce has a length of 8 bytes':
            $this->assertEquals(8, strlen($world['nonce']));
            break;
        case 'the nonce is invalid given a timeout of one second':
            $this->assertFalse($world['nonce_handler']->isValid($world['nonce'], 1));
            break;
        case 'the nonce is valid given no timeout':
            $this->assertTrue($world['nonce_handler']->isValid($world['nonce']));
            break;
        case 'the extracted counter value (here: timestamp) is':
            $this->assertEquals(
                $world['timestamp'],
                $arguments[0]
            );
            break;
        case 'the extracted random part matches':
            $this->assertEquals(
                $world['random'],
                $arguments[0]
            );
            break;
        case 'the hash representation provides the hashes':
            $this->assertEquals(
                $world['hashes'],
                $arguments
            );
            break;
        default:
            return $this->notImplemented($action);
        }
    }

}