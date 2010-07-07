<?php
/**
 * Provides methods to retrieve free/busy data for resources on a Kolab server.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Retrieves free/busy data for an email address on a Kolab server.
 *
 * Copyright 2004-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL>=2.1). If you
 * did not receive this file,
 * see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Resource_Freebusy_Kolab extends Horde_Kolab_Resource_Freebusy
{
    /**
     * Retrieve Free/Busy URL for the specified resource id.
     *
     * @param string $resource The id of the resource (usually a mail address).
     *
     * @return string The Free/Busy URL for that resource.
     */
    protected function getUrl($resource)
    {
        $server = Horde_Kolab_Server::singleton();
        $uid    = $server->uidForIdOrMailOrAlias($resource);
        $result = $server->fetch($uid)->getServer('freebusy');
        return sprintf('%s/%s.xfb', $result, $resource);
    }

    /**
     * Retrieve Free/Busy data for the specified resource.
     *
     * @param string $resource Fetch the Free/Busy data for this resource.
     *
     * @return Horde_iCalendar_vfreebusy The Free/Busy data.
     */
    public function get($resource)
    {
        global $conf;

        $url = self::getUrl($resource);

        Horde::logMessage(sprintf('Freebusy URL for resource %s is %s',
                                  $resource, $url), 'DEBUG');

        list($user, $domain) = explode('@', $resource);
        if (empty($domain)) {
            $domain = $conf['kolab']['filter']['email_domain'];
        }

        /**
         * This section matches Kronolith_Freebusy and should be merged with it
         * again in a single Horde_Freebusy module.
         */
        $options = array(
            'method'         => 'GET',
            'timeout'        => 5,
            'allowRedirects' => true
        );

        if (!empty($conf['http']['proxy']['proxy_host'])) {
            $options = array_merge($options, $conf['http']['proxy']);
        }

        $http = new HTTP_Request($url, $options);
        $http->setBasicAuth($conf['kolab']['filter']['calendar_id'] . '@' . $domain,
                            $conf['kolab']['filter']['calendar_pass']);
        @$http->sendRequest();
        if ($http->getResponseCode() != 200) {
            throw new Horde_Kolab_Resource_Exception(sprintf('Unable to retrieve free/busy information for %s',
                                                           $resource),
                                                   Horde_Kolab_Resource_Exception::NO_FREEBUSY);
        }
        $vfb_text = $http->getResponseBody();

        // Detect the charset of the iCalendar data.
        $contentType = $http->getResponseHeader('Content-Type');
        if ($contentType && strpos($contentType, ';') !== false) {
            list(,$charset,) = explode(';', $contentType);
            $charset         = trim(str_replace('charset=', '', $charset));
        } else {
            $charset = 'UTF-8';
        }

        $iCal = new Horde_iCalendar;
        $iCal->parsevCalendar($vfb_text, 'VCALENDAR', $charset);

        $vfb = &$iCal->findComponent('VFREEBUSY');

        if ($vfb === false) {
            throw new Horde_Kolab_Resource_Exception(sprintf('Invalid or no free/busy information available for %s',
                                                           $resource),
                                                   Horde_Kolab_Resource_Exception::NO_FREEBUSY);
        }
        $vfb->simplify();

        return $vfb;
    }
}
