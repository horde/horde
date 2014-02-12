#ifndef PHP_HORDE_LZ4_H
#define PHP_HORDE_LZ4_H

#define HORDE_LZ4_EXT_VERSION "1.0.4"

extern char headerid;
extern zend_module_entry horde_lz4_module_entry;
#define phpext_horde_lz4_ptr &horde_lz4_module_entry

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

#ifdef ZTS
#define HORDE_LZ4_G(v) TSRMG(horde_lz4_globals_id, zend_horde_lz4_globals *, v)
#else
#define HORDE_LZ4_G(v) (horde_lz4_globals.v)
#endif

#endif  /* PHP_HORDE_LZ4_H */
