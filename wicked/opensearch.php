<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

// Check for a prior definition of HORDE_BASE (perhaps by an
// auto_prepend_file definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = new Horde_Registry();
$registry->pushApp('wicked');

// Url.
$url = Horde::applicationUrl('', true);

// Name.
$name = $registry->get('name', 'wicked') . ' (' . $url . ')';

// Icon.
$icon = base64_encode(file_get_contents($registry->get('themesfs', 'wicked') . '/graphics/wicked.png'));

// Charset.
$charset = Horde_Nls::getCharset();

header('Content-Type: text/xml; charset=' . $charset);
echo <<<PAYLOAD
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
  <ShortName>$name</ShortName>
  <SearchForm>$url</SearchForm>
  <Url type="text/html"
       method="get"
       template="${url}display.php">
    <Param name="page" value="Search"/>
    <Param name="params" value="{searchTerms}"/>
  </Url>
  <Image height="16" width="16">data:image/png;base64,$icon</Image>
  <InputEncoding>$charset</InputEncoding>

</OpenSearchDescription>
PAYLOAD;
