#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "horde_lz4.h"

/* lz4 */
#include "lz4.h"
#include "lz4hc.h"


ZEND_BEGIN_ARG_INFO_EX(arginfo_horde_lz4_compress, 0, 0, 1)
    ZEND_ARG_INFO(0, data)
    ZEND_ARG_INFO(0, high)
ZEND_END_ARG_INFO()

ZEND_BEGIN_ARG_INFO_EX(arginfo_horde_lz4_uncompress, 0, 0, 1)
    ZEND_ARG_INFO(0, data)
ZEND_END_ARG_INFO()


const zend_function_entry horde_lz4_functions[] = {
    PHP_FE(horde_lz4_compress, arginfo_horde_lz4_compress)
    PHP_FE(horde_lz4_uncompress, arginfo_horde_lz4_uncompress)
    PHP_FE_END
};


zend_module_entry horde_lz4_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
    STANDARD_MODULE_HEADER,
#endif
    "horde_lz4",
    horde_lz4_functions,
    NULL,
    NULL,
    NULL,
    NULL,
    PHP_MINFO(horde_lz4),
#if ZEND_MODULE_API_NO >= 20010901
    HORDE_LZ4_EXT_VERSION,
#endif
    STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_HORDE_LZ4
ZEND_GET_MODULE(horde_lz4)
#endif


PHP_MINFO_FUNCTION(horde_lz4)
{
    php_info_print_table_start();
    php_info_print_table_row(2, "Horde LZ4 support", "enabled");
    php_info_print_table_row(2, "Extension Version", HORDE_LZ4_EXT_VERSION);
    php_info_print_table_end();
}


char horde_lz4_headerid = 'H';


PHP_FUNCTION(horde_lz4_compress)
{
    zval *data;
    char *output;
    int data_len, output_len;
    int header_offset = (sizeof(horde_lz4_headerid) + sizeof(data_len));
    zend_bool high = 0;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z|b", &data, &high) == FAILURE) {
        RETURN_FALSE;
    }

    if (Z_TYPE_P(data) != IS_STRING) {
        zend_error(E_WARNING,
                   "horde_lz4_compress: uncompressed data must be a string.");
        RETURN_FALSE;
    }

    data_len = Z_STRLEN_P(data);

    output = (char *)emalloc(LZ4_compressBound(data_len) + header_offset);
    if (!output) {
        zend_error(E_WARNING, "horde_lz4_compress: memory error");
        RETURN_FALSE;
    }

    output[0] = horde_lz4_headerid;
    memcpy(output + sizeof(horde_lz4_headerid), &data_len, sizeof(data_len));

    if (high) {
        output_len = LZ4_compressHC(Z_STRVAL_P(data), output + header_offset, data_len);
    } else {
        output_len = LZ4_compress(Z_STRVAL_P(data), output + header_offset, data_len);
    }

    if (output_len <= 0) {
        RETVAL_FALSE;
    } else {
        RETVAL_STRINGL(output, output_len + header_offset, 1);
    }

    efree(output);
}


PHP_FUNCTION(horde_lz4_uncompress)
{
    zval *data;
    int data_len = 0;
    int output_len;
    int header_offset = (sizeof(horde_lz4_headerid) + sizeof(data_len));
    char *output, *p;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
                              "z", &data) == FAILURE) {
        RETURN_FALSE;
    }

    if (Z_TYPE_P(data) != IS_STRING) {
        zend_error(E_WARNING,
                   "horde_lz4_uncompress: compressed data must be a string.");
        RETURN_FALSE;
    }

    p = Z_STRVAL_P(data);

    /* Check for header information. */
    if (p[0] == horde_lz4_headerid) {
        memcpy(&data_len, p + sizeof(horde_lz4_headerid), sizeof(data_len));
    }

    /* Header information not found. */
    if (data_len <= 0) {
        RETURN_FALSE;
    }

    output = (char *)emalloc(data_len + 1);
    if (!output) {
        RETURN_FALSE;
    }

    output_len = LZ4_uncompress(p + header_offset, output, data_len);

    if (output_len <= 0) {
        RETVAL_FALSE;
    } else {
        RETVAL_STRINGL(output, data_len, 1);
    }

    efree(output);
}
