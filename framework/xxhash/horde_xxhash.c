#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "horde_xxhash.h"

/* xxhash */
#include "xxhash.h"


ZEND_BEGIN_ARG_INFO_EX(arginfo_horde_xxhash, 0, 0, 1)
    ZEND_ARG_INFO(0, data)
ZEND_END_ARG_INFO()


const zend_function_entry horde_xxhash_functions[] = {
    PHP_FE(horde_xxhash, arginfo_horde_xxhash)
    PHP_FE_END
};


zend_module_entry horde_xxhash_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    "horde_xxhash",
    horde_xxhash_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_MINFO(horde_xxhash),
#if ZEND_MODULE_API_NO >= 20010901
    HORDE_XXHASH_EXT_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_HORDE_XXHASH
ZEND_GET_MODULE(horde_xxhash)
#endif


PHP_MINFO_FUNCTION(horde_xxhash)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "Horde xxHash support", "enabled");
    php_info_print_table_row(2, "Extension Version", HORDE_XXHASH_EXT_VERSION);
    php_info_print_table_end();
}

#if PHP_MAJOR_VERSION < 7

PHP_FUNCTION(horde_xxhash)
{
    char *data;
    char *hash = emalloc(9);
    unsigned int data_len;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "s", &data, &data_len) == FAILURE) {
        RETURN_FALSE;
    }

    sprintf(hash, "%08x", XXH32(data, data_len, 0));

    RETURN_STRINGL(hash, 8, 0);
}

#else

PHP_FUNCTION(horde_xxhash)
{
    char *data = NULL;
    size_t data_len;
    zend_string *hash;

    if (zend_parse_parameters(ZEND_NUM_ARGS(), "s", &data, &data_len) == FAILURE) {
        RETURN_FALSE;
    }

    hash = strpprintf(8, "%08x", XXH32(data, data_len, 0));

    RETURN_STR(hash);
}

#endif
