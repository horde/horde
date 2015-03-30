#ifndef PHP_HORDE_LZ4_H
#define PHP_HORDE_LZ4_H

extern zend_module_entry horde_lz4_module_entry;
#define phpext_horde_lz4_ptr &horde_lz4_module_entry

#define HORDE_LZ4_EXT_VERSION "1.0.8"

#ifdef PHP_WIN32
#   define PHP_HORDE_LZ4_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#   define PHP_HORDE_LZ4_API __attribute__ ((visibility("default")))
#else
#   define PHP_HORDE_LZ4_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

PHP_MINFO_FUNCTION(horde_lz4);

PHP_FUNCTION(horde_lz4_compress);
PHP_FUNCTION(horde_lz4_uncompress);

#if PHP_MAJOR_VERSION < 7
#define HORDE_LZ4_RETSTRL(a,l) RETURN_STRINGL(a,l,1)
#else
typedef size_t strsize;
#define HORDE_LZ4_RETSTRL(a,l) RETURN_STRINGL(a,l)
#endif

#endif  /* PHP_HORDE_LZ4_H */
