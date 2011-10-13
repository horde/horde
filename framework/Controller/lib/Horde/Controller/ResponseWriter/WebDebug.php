<?php
/**
 * @category Horde
 * @package  Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 */
class Horde_Controller_ResponseWriter_WebDebug implements Horde_Controller_ResponseWriter
{
    public function writeResponse(Horde_Controller_Response $response)
    {
        $headerHtml = '<div><strong>Headers:</strong><pre>';
        $headers = $response->getHeaders();
        foreach ($headers as $key => $value) {
            $headerHtml .= htmlspecialchars("$key: $value\n");
        }
        echo $headerHtml . '</pre></div>';

        if (isset($headers['Location'])) {
            echo '<p>Redirect To: <a href="' . htmlspecialchars($headers['Location']) . '">' . htmlspecialchars($headers['Location']) . '</a></p>';
        }

        $body = $response->getBody();
        if (is_resource($body)) {
            $body = stream_get_contents($body);
        }
        if (isset($headers['Content-Encoding']) && $headers['Content-Encoding'] == 'gzip') {
            // Strip off the header and inflate it
            echo gzinflate(substr($body, 10));
        } else {
            echo $body;
        }
    }
}
