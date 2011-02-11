<?php
/**
 * NLS (National Language Support) configuration file.
 *
 * IMPORTANT: Local overrides should be placed in nls.local.php, or
 * nls-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$horde_nls_config = array(
    /* Defaults */
    'defaults' => array(
        /* The language to fall back on if we cannot determine one any other
         * way (user choice or preferences). If empty, we will try to
         * negotiate with the browser using HTTP_ACCEPT_LANGUAGE. */
        'language' => '',
    ),

    /* Languages */
    'languages' => array(
        'ar_OM' => '&#x202d;Arabic (Oman) &#x202e;(عربية)',
        'ar_SY' => '&#x202d;Arabic (Syria) &#x202e;(عربية)',
        'id_ID' => 'Bahasa Indonesia',
        'bs_BA' => 'Bosanski',
        'bg_BG' => '&#x202d;Bulgarian (Български)',
        'ca_ES' => 'Català',
        'cs_CZ' => 'Český',
        'zh_CN' => '&#x202d;Chinese (Simplified) (简体中文)',
        'zh_TW' => '&#x202d;Chinese (Traditional) (正體中文)',
        'da_DK' => 'Dansk',
        'de_DE' => 'Deutsch',
        'en_US' => '&#x202d;English (American)',
        'en_GB' => '&#x202d;English (British)',
        'en_CA' => '&#x202d;English (Canadian)',
        'es_ES' => 'Español',
        'et_EE' => 'Eesti',
        'eu_ES' => 'Euskara',
        'fa_IR' => '&#x202d;Farsi (Persian) &#x202e;(فارسی)',
        'fr_FR' => 'Français',
        'gl_ES' => 'Galego',
        'el_GR' => '&#x202d;Greek (Ελληνικά)',
        'he_IL' => '&#x202d;Hebrew &#x202e;(עברית)',
        'hr_HR' => 'Hrvatski',
        'is_IS' => 'Íslenska',
        'it_IT' => 'Italiano',
        'ja_JP' => '&#x202d;Japanese (日本語)',
        'km_KH' => '&#x202d;Khmer (ខមែរ)',
        'ko_KR' => '&#x202d;Korean (한국어)',
        'lv_LV' => 'Latviešu',
        'lt_LT' => 'Lietuvių',
        'mk_MK' => '&#x202d;Macedonian (Македонски)',
        'hu_HU' => 'Magyar',
        'nl_NL' => 'Nederlands',
        'nb_NO' => '&#x202d;Norsk (bokmål)',
        'nn_NO' => '&#x202d;Norsk (nynorsk)',
        'pl_PL' => 'Polski',
        'pt_PT' => 'Português',
        'pt_BR' => 'Português do Brasil',
        'ro_RO' => 'Română',
        'ru_RU' => '&#x202d;Russian (Русский)',
        'sk_SK' => 'Slovenský',
        'sl_SI' => 'Slovensko',
        'fi_FI' => 'Suomi',
        'sv_SE' => 'Svenska',
        'th_TH' => '&#x202d;Thai (ภาษาไทย)',
        'uk_UA' => '&#x202d;Ukrainian (Українська)',
    ),

    /* Aliases for languages with different browser and gettext codes */
    'aliases' => array(
        'ar' => 'ar_SY',
        'bg' => 'bg_BG',
        'bs' => 'bs_BA',
        'ca' => 'ca_ES',
        'cs' => 'cs_CZ',
        'da' => 'da_DK',
        'de' => 'de_DE',
        'el' => 'el_GR',
        'en' => 'en_US',
        'es' => 'es_ES',
        'et' => 'et_EE',
        'fa' => 'fa_IR',
        'fi' => 'fi_FI',
        'fr' => 'fr_FR',
        'gl' => 'gl_ES',
        'he' => 'he_IL',
        'hu' => 'hu_HU',
        'id' => 'id_ID',
        'is' => 'is_IS',
        'it' => 'it_IT',
        'ja' => 'ja_JP',
        'km' => 'km_KH',
        'ko' => 'ko_KR',
        'lt' => 'lt_LT',
        'lv' => 'lv_LV',
        'mk' => 'mk_MK',
        'nl' => 'nl_NL',
        'nn' => 'nn_NO',
        'no' => 'nb_NO',
        'pl' => 'pl_PL',
        'pt' => 'pt_PT',
        'ro' => 'ro_RO',
        'ru' => 'ru_RU',
        'sk' => 'sk_SK',
        'sl' => 'sl_SI',
        'sv' => 'sv_SE',
        'th' => 'th_TH',
        'uk' => 'uk_UA',
    ),

    /* Charsets. These differ somewhat on different systems; see below for
     * for a set of BSD charset names. */
     'charsets' => array(
        'ar_OM' => 'windows-1256',
        'ar_SY' => 'windows-1256',
        'bg_BG' => 'windows-1251',
        'bs_BA' => 'ISO-8859-2',
        'cs_CZ' => 'ISO-8859-2',
        'el_GR' => 'ISO-8859-7',
        'et_EE' => 'ISO-8859-13',
        'fa_IR' => 'UTF-8',
        'he_IL' => 'UTF-8',
        'hu_HU' => 'ISO-8859-2',
        'ja_JP' => 'SHIFT_JIS',
        'km_KH' => 'UTF-8',
        'ko_KR' => 'EUC-KR',
        'lt_LT' => 'ISO-8859-13',
        'lv_LV' => 'windows-1257',
        'mk_MK' => 'ISO-8859-5',
        'pl_PL' => 'ISO-8859-2',
        'ru_RU' => 'windows-1251',
        'ru_RU.KOI8-R' => 'KOI8-R',
        'sk_SK' => 'ISO-8859-2',
        'sl_SI' => 'ISO-8859-2',
        'th_TH' => 'TIS-620',
        'uk_UA' => 'windows-1251',
        'zh_CN' => 'GB2312',
        'zh_TW' => 'BIG5',
    ),


    /* Multibyte charsets */
    'multibyte' => array(
        'BIG5' => true,
        'EUC-KR' => true,
        'GB2312' => true,
        'SHIFT_JIS' => true,
        'UTF-8' => true,
    ),

    /* Right-to-left languages */
    'rtl' => array(
        'ar_OM' => true,
        'ar_SY' => true,
        'fa_IR' => true,
        'he_IL' => true,
    ),

    /* Preferred charsets for email traffic if not the languages' default
     * charsets. */
    'emails' => array(
        'ja_JP' => 'ISO-2022-JP',
    ),

    /* Available charsets for outgoing email traffic. */
    'encodings' => array(
        'windows-1256' => _("Arabic (Windows-1256)"),
        'ARMSCII-8' => _("Armenian (ARMSCII-8)"),
        'ISO-8859-13' => _("Baltic (ISO-8859-13)"),
        'ISO-8859-14' => _("Celtic (ISO-8859-14)"),
        'ISO-8859-2' => _("Central European (ISO-8859-2)"),
        'GB2312' => _("Chinese Simplified (GB2312)"),
        'BIG5' => _("Chinese Traditional (Big5)"),
        'KOI8-R' => _("Cyrillic (KOI8-R)"),
        'windows-1251' => _("Cyrillic (Windows-1251)"),
        'KOI8-U' => _("Cyrillic/Ukrainian (KOI8-U)"),
        'ISO-8859-7' => _("Greek (ISO-8859-7)"),
        'ISO-8859-8-I' => _("Hebrew (ISO-8859-8-I)"),
        'ISO-2022-JP' => _("Japanese (ISO-2022-JP)"),
        'EUC-KR' => _("Korean (EUC-KR)"),
        'ISO-8859-10' => _("Nordic (ISO-8859-10)"),
        'ISO-8859-3' => _("South European (ISO-8859-3)"),
        'TIS-620' => _("Thai (TIS-620)"),
        'ISO-8859-9' => _("Turkish (ISO-8859-9)"),
        'UTF-8' => _("Unicode (UTF-8)"),
        'VISCII' => _("Vietnamese (VISCII)"),
        'ISO-8859-1' => _("Western (ISO-8859-1)"),
        'ISO-8859-15' => _("Western (ISO-8859-15)"),
    ),

    /* Multi-language spelling support. */
    'spelling' => array(
        'cs_CZ' => '-T latin2 -d czech',
        'da_DK' => '-d dansk',
        'de_DE' => '-T latin1 -d deutsch',
        'el_GR' => '-T latin1 -d ellinika',
        'en_CA' => '-d canadian',
        'en_GB' => '-d british',
        'en_US' => '-d american',
        'es_ES' => '-d espanol',
        'fr_FR' => '-d francais',
        'it_IT' => '-T latin1 -d italian',
        'nl_NL' => '-d nederlands',
        'pl_PL' => '-d polish',
        'pt_BR' => '-d br',
        'pt_PT' => '-T latin1 -d portuguese',
        'ru_RU' => '-d russian',
        'sl_SI' => '-d slovensko',
        'sv_SE' => '-d svenska',
    )
);

/* BSD charsets. */
if (strpos(PHP_OS, 'BSD') !== false) {
    $horde_nls_config['charsets'] = array_merge($horde_nls_config['charsets'], array(
        'bs_BA' => 'ISO8859-2',
        'cs_CZ' => 'ISO8859-2',
        'el_GR' => 'ISO8859-7',
        'et_EE' => 'ISO8859-13',
        'hu_HU' => 'ISO8859-2',
        'ja_JP' => 'SHIFT_JIS',
        'ko_KR' => 'EUC-KR',
        'lt_LT' => 'ISO8859-13',
        'lv_LV' => 'windows-1257',
        'mk_MK' => 'ISO8859-5',
        'pl_PL' => 'ISO8859-2',
        'sk_SK' => 'ISO8859-2',
        'sl_SI' => 'ISO8859-2',
    ));
}

/* Turkish locales. */
if (version_compare(PHP_VERSION, '6', 'ge')) {
    $horde_nls_config['aliases']['tr'] = 'tr_TR';
    $horde_nls_config['charsets']['tr_TR'] = (strpos(PHP_OS, 'BSD') === false) ? 'ISO-8859-9' : 'ISO8859-9';
    $horde_nls_config['languages']['tr_TR'] = 'T&#xfc;rk&#xe7;e';
    $horde_nls_config['spelling']['tr_TR'] = '-d tr';
}

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/nls.local.php')) {
    include dirname(__FILE__) . '/nls.local.php';
}
