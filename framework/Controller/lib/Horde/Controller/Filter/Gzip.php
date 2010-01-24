<?php
/**
 * Filter to gzip content before being served
 *
 * @category Horde
 * @package  Horde_Controller
 * @author   James Pepin <james@bluestatedigital.com>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 */
class Horde_Controller_Filter_Gzip implements Horde_Controller_PostFilter
{
    public function processResponse(Horde_Controller_Request $request, Horde_Controller_Response $response, Horde_Controller $controller)
    {
        $body = $response->getBody();
        $body = gzencode($body);

        $response->setHeader('Content-Encoding', 'gzip');
        $response->setHeader('Content-Length', $this->_byteCount($body));
        $response->setBody($body);

        return $response;
    }

    /**
     * If mbstring is set to overload str* function then we could be counting
     * multi-byte chars as single bytes so we need to treat the string like its
     * 8-bit encoded to get an accurate byte count.
     */
    protected function _byteCount($string)
    {
        if (ini_get('mbstring.func_overload') > 0) {
            return mb_strlen($string, '8bit');
        }

        return strlen($string);
    }
}
