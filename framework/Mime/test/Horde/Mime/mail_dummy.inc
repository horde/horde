<?php
/**
 * @package Mail
 */

require dirname(__FILE__) . '/../../lib/Horde/Mime/Mail.php';
$_SERVER['SERVER_NAME'] = 'mail.example.com';

class Mail_dummy extends Mail {
    function send($recipients, $headers, $body)
    {
        list(,$text_headers) = Mail::prepareHeaders($headers);
        return $text_headers . "\n\n" . $body;
    }
}
