<?php
/**
 * Copyright 2007-2008 Maintainable Software, LLC
 * Copyright 2008-2009 The Horde Project (http://www.horde.org)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Controller
 */
class Horde_Controller_StatusCodes
{
    /**
     * All known status codes and their messages
     * @var array
     */
    public static $statusCodes = array(
      100 => "Continue",
      101 => "Switching Protocols",
      102 => "Processing",

      200 => "OK",
      201 => "Created",
      202 => "Accepted",
      203 => "Non-Authoritative Information",
      204 => "No Content",
      205 => "Reset Content",
      206 => "Partial Content",
      207 => "Multi-Status",
      226 => "IM Used",

      300 => "Multiple Choices",
      301 => "Moved Permanently",
      302 => "Found",
      303 => "See Other",
      304 => "Not Modified",
      305 => "Use Proxy",
      307 => "Temporary Redirect",

      400 => "Bad Request",
      401 => "Unauthorized",
      402 => "Payment Required",
      403 => "Forbidden",
      404 => "Not Found",
      405 => "Method Not Allowed",
      406 => "Not Acceptable",
      407 => "Proxy Authentication Required",
      408 => "Request Timeout",
      409 => "Conflict",
      410 => "Gone",
      411 => "Length Required",
      412 => "Precondition Failed",
      413 => "Request Entity Too Large",
      414 => "Request-URI Too Long",
      415 => "Unsupported Media Type",
      416 => "Requested Range Not Satisfiable",
      417 => "Expectation Failed",
      422 => "Unprocessable Entity",
      423 => "Locked",
      424 => "Failed Dependency",
      426 => "Upgrade Required",

      500 => "Internal Server Error",
      501 => "Not Implemented",
      502 => "Bad Gateway",
      503 => "Service Unavailable",
      504 => "Gateway Timeout",
      505 => "HTTP Version Not Supported",
      507 => "Insufficient Storage",
      510 => "Not Extended"
    );

    /**
     * Given a status parameter, determine whether it needs to be converted
     * to a string. If it is an integer, use the $statusCodes hash to lookup
     * the default message. If it is a string, build $symbolToStatusCode
     * and convert it.
     *
     *   interpret(404)         => "404 Not Found"
     *   interpret("notFound")  => "404 Not Found"
     *
     * Differences from Rails:
     *   - $status is camelized, not underscored.
     *   - an unknown status raises an exception
     *
     * @param  string|integer  Status code or "symbol"
     * @return string          Header
     */
    public static function interpret($status)
    {
        // Status from integer or numeric string
        if (is_numeric($status)) {
            if (isset(self::$statusCodes[$status])) {
                return $status . ' ' . self::$statusCodes[$status];
            } else {
                $msg = 'Unknown status code: ' . $status;
                throw new InvalidArgumentException($msg);
            }

        // Status from string
        } elseif (is_string($status)) {
            // Build a string-to-integer lookup for converting a symbol (like
            // 'created' or 'notImplemented') into its corresponding HTTP status
            // code (like 200 or 501).
            static $symbolToStatusCode = array();
            $inflector = new Horde_Support_Inflector();
            if (empty($symbolToStatusCode)) {
                foreach (self::$statusCodes as $code => $message) {
                    $symbol = $inflector->camelize($message, $first='lower');
                    $symbolToStatusCode[$symbol] = $code;
                }
            }

            // Convert status symbol to integer code, return header
            if (isset($symbolToStatusCode[$status])) {
                return self::interpret($symbolToStatusCode[$status]);
            }

            // Error: Status symbol could not be converted to an integer code
            // Try to help if the developer mixed up underscore/camel
            $msg = 'Unknown status: \'' . $status . '\'';
            if (strpos($status, '_')) {
                $status = $inflector->camelize($status, $first='lower');
                if (isset($symbolToStatusCode[$status])) {
                    $msg .= ' (underscore), did you mean \'' . $status . '\' (camel)?';
                }
            }
            throw new InvalidArgumentException($msg);

        // Status is an unknown type
        } else {
            $msg = '$status must be numeric or string, got '
                 . gettype($status);
            throw new InvalidArgumentException($msg);
        }

    }

}
