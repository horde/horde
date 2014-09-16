dnl config.m4 for extension horde_lz4

dnl Check PHP version:
AC_MSG_CHECKING(PHP version)
if test ! -z "$phpincludedir"; then
    PHP_VERSION=`grep 'PHP_VERSION ' $phpincludedir/main/php_version.h | sed -e 's/.*"\([[0-9\.]]*\).*".*/\1/g' 2>/dev/null`
elif test ! -z "$PHP_CONFIG"; then
    PHP_VERSION=`$PHP_CONFIG --version 2>/dev/null`
fi

if test x"$PHP_VERSION" = "x"; then
    AC_MSG_WARN([none])
else
    PHP_MAJOR_VERSION=`echo $PHP_VERSION | sed -e 's/\([[0-9]]*\)\.\([[0-9]]*\)\.\([[0-9]]*\).*/\1/g' 2>/dev/null`
    PHP_MINOR_VERSION=`echo $PHP_VERSION | sed -e 's/\([[0-9]]*\)\.\([[0-9]]*\)\.\([[0-9]]*\).*/\2/g' 2>/dev/null`
    PHP_RELEASE_VERSION=`echo $PHP_VERSION | sed -e 's/\([[0-9]]*\)\.\([[0-9]]*\)\.\([[0-9]]*\).*/\3/g' 2>/dev/null`
    AC_MSG_RESULT([$PHP_VERSION])
fi

if test $PHP_MAJOR_VERSION -lt 5; then
    AC_MSG_ERROR([need at least PHP 5 or newer])
fi

PHP_ARG_ENABLE(horde_lz4, whether to enable horde_lz4 support,
[  --enable-horde_lz4           Enable horde_lz4 support])

PHP_ARG_WITH(liblz4, whether to use system liblz4,
[  --with-liblz4                Use system liblz4], no, no)

if test "$PHP_HORDE_LZ4" != "no"; then

  sources=horde_lz4.c

  if test "$PHP_LIBLZ4" != "no"; then
    AC_PATH_PROG(PKG_CONFIG, pkg-config, no)
    AC_MSG_CHECKING(liblz4 version)
    if test -x "$PKG_CONFIG" && $PKG_CONFIG --exists liblz4; then
      LIBLZ4_INCLUDE=`$PKG_CONFIG liblz4 --cflags`
      LIBLZ4_LIBRARY=`$PKG_CONFIG liblz4 --libs`
      LIBLZ4_VERSION=`$PKG_CONFIG liblz4 --modversion`
    fi

    if test -z "$LIBLZ4_VERSION"; then
      AC_MSG_RESULT(liblz4.pc not found)
      AC_CHECK_HEADERS([lz4.h])
      PHP_CHECK_LIBRARY(lz4, LZ4_decompress_fast,
        [PHP_ADD_LIBRARY(lz4, 1, HORDE_LZ4_SHARED_LIBADD)],
        [AC_MSG_ERROR(lz4 library not found)])
    else
      AC_MSG_RESULT($LIBLZ4_VERSION)
      PHP_EVAL_INCLINE($LIBLZ4_INCLUDE)
      PHP_EVAL_LIBLINE($LIBLZ4_LIBRARY, HORDE_LZ4_SHARED_LIBADD)
    fi
  else
    PHP_ADD_INCLUDE([${srcdir}/lib])
    sources="$sources lib/lz4.c lib/lz4hc.c"
  fi
  PHP_NEW_EXTENSION(horde_lz4, $sources, $ext_shared)
  PHP_SUBST(HORDE_LZ4_SHARED_LIBADD)

  ifdef([PHP_INSTALL_HEADERS],
  [
    PHP_INSTALL_HEADERS([ext/horde_lz4/], [horde_lz4.h])
  ], [
    PHP_ADD_MAKEFILE_FRAGMENT
  ])
fi
