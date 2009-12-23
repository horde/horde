#!/bin/sh
#
# Script to compare revisions and ignore files that should be different, and
# differences in revision numbers.
# Syntax: ./compare_revisions.sh FIRST_FOLDER SECOND_FOLDER ['ADDITIONAL PARAMETERS']

FIRST=$1
SECOND=$2
shift 2
diff -r -I "\$Horde" -I "\$Revision" -I "\$Date" -I "\$Id" --exclude version.php --exclude CHANGES --exclude CREDITS --exclude '*.po' --exclude '*.pot' --exclude locale --exclude CVS --exclude '.#*' --exclude '*~' --exclude '*.bak' --exclude '*.orig' --exclude '*.rej' "$@" $FIRST $SECOND | grep -v "config/.*\.php "
