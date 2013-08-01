#ifndef PHP_VERDEP_H
#define PHP_VERDEP_H

#ifndef ZED_FE_END
#define ZEND_FE_END { NULL, NULL, NULL, 0, 0 }
#endif

#ifndef Z_SET_REFCOUNT_P
#define Z_SET_REFCOUNT_P(pz, rc) ((pz)->refcount = rc)
#endif

#ifndef Z_UNSET_ISREF_PP
#define Z_UNSET_ISREF_PP(ppz) Z_UNSET_ISREF_P(*(ppz))
#endif

#ifndef Z_UNSET_ISREF_P
#define Z_UNSET_ISREF_P(pz) ((pz)->is_ref = 0)
#endif

#ifndef Z_ISREF_PP
#define Z_ISREF_PP(ppz) Z_ISREF_P(*(ppz))
#endif

#ifndef Z_ISREF_P
#if ZEND_MODULE_API_NO >= 20090626
#define Z_ISREF_P(pz) zval_isref_p(pz)
#else
#define Z_ISREF_P(pz) ((pz)->is_ref)
#endif
#endif

#ifndef Z_ADDREF_PP
#define Z_ADDREF_PP(ppz) Z_ADDREF_P(*(ppz))
#endif

#ifndef Z_ADDREF_P
#if ZEND_MODULE_API_NO >= 20090626
#define Z_ADDREF_P(pz) zval_addref_p(pz)
#else
#define Z_ADDREF_P(pz) (++(pz)->refcount)
#endif
#endif

#ifndef Z_SET_ISREF_PP
#define Z_SET_ISREF_PP(ppz) Z_SET_ISREF_P(*(ppz))
#endif

#ifndef Z_SET_ISREF_P
#if ZEND_MODULE_API_NO >= 20090626
#define Z_SET_ISREF_P(pz) zval_set_isref_p(pz)
#else
#define Z_SET_ISREF_P(pz) ((pz)->is_ref = 1)
#endif
#endif

#ifndef array_init_size
#define array_init_size(arg, size) _array_init((arg) ZEND_FILE_LINE_CC)
#endif

#ifndef zend_parse_parameters_none
#define zend_parse_parameters_none() \
    zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "")
#endif

#endif  /* PHP_VERDEP_H */
