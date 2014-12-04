OUT=mime.mapping.php
HOST=`hostname -f`

all:  ${OUT}
${OUT}:
	HOST=${HOST} php convert.php > ${OUT}
clean:
	rm -f ${OUT}
