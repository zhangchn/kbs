# The home directory for the user 'bbs'
BBSHOME=/home0/bbs
BBSSITE=smth

# The uid for the user 'bbs'
BBSUID=9999
#BBSUID=502

OS_DEF   = -DAIX -DSYSV 
#OS_DEF   = -DLINUX -DSYSV 

CC       = gcc
#CC       = cc
CFLAGS   = -W -g -Ilibesmtp
#CFLAGS   = -g -O3 -qstrict -qcompact -qarch=ppc -Ilibesmtp
#LIBS     = -lbsd -lcrypt
#CFLAGS   = -O3
LIBS     = -lbsd -lesmtp -Llibesmtp/.libs

INSTALL  = ./install.sh 

CSIE_DEF = -DSHOW_IDLE_TIME -DBBSGID=99 -D_DETAIL_UINFO_ -DOS_LACK_SOCKLEN
#CSIE_DEF = -DSHOW_IDLE_TIME -DBBSGID=99 -D_DETAIL_UINFO_
