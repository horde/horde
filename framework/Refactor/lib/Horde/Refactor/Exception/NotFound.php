<?php
/**
 * Copyright 2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Refactor
 */

namespace Horde\Refactor\Exception;
use Horde\Refactor\Exception;
use Horde\Refactor\Translation;

/**
 * Exception thrown if an expected token wasn't found.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Refactor
 */
class NotFound extends Exception
{
    /**
     * Constructor.
     *
     * @param integer|string $token  The expected token.
     */
    public function __construct($token)
    {
        if (is_array($token)) {
            $token = $token[0];
        }
        if (is_int($token)) {
            $name = token_name($token);
        } else {
            $name = $token;
        }
        $message = sprintf(
            Translation::t("Token \"%s\" Not Found"), $name
        );
        parent::__construct($message);
        $this->details = $token;
    }
}