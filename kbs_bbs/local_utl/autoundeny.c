/*    自动解封系统    KCN 1999.7.26 		  */

#include "bbs.h"
struct userec deliveruser;

unsigned long atoul(char* p)
{
	unsigned long s;
	char* t;
	t=p;
	s=0;
	while ((*t>='0')&&(*t<='9')) {
		s=s*10+*t-'0';
		t++;
	}
	return s;
}

int canundeny(char* linebuf,unsigned long nowtime)
{
	char* p;
	unsigned long time2;
	
	p=linebuf;
	while ((*p!=0)&&(*p!=0x1b)) p++;
	if (*p==0) return 0;
	p++;
	if (*p==0) return 0;
	p++;
	if (*p==0) return 0;
	time2=atoul(p);
	return nowtime>time2;
}

int sgetline(char* buf,char* linebuf,int *idx,int maxlen)
{
	int len=0;	
	while (len<maxlen) {
		char ch;
		linebuf[len]=buf[*idx];
		ch = buf[*idx];
		(*idx)++;
		if (ch==0x0d) {
			linebuf[len]=0;
			if (buf[*idx]==0x0a) (*idx)++;
			break;
		}
		if (ch==0x0a) {
			linebuf[len]=0;
			break;
		}
		if (ch==0)
			break;
		len++; 
	}
	return len;
}

int undenyboardy(struct boardheader* bh)
{
	int d_fd;
	char denyfile[256];
	char* buf;
	int bufsize;
	int size;
	struct stat st;
	time_t nowtime;
	int idx1,idx2;
	char linebuf[256];

	nowtime=time(NULL);
	if (bh->filename[0]) {
//			if (strcmp(bh.filename,"test")) continue;
		sprintf(denyfile,"boards/%s/deny_users",bh->filename);
		if (stat(denyfile,&st)==0) {
			if (st.st_size!=0) {
				//printf("process %s ...\n",bh.filename);
				if (bufsize<st.st_size+1) {
					if (buf) free(buf);
					buf=malloc(st.st_size+1);
					buf[st.st_size]=0;
				}
				if((d_fd = open(denyfile,O_RDWR)) != -1) {
					flock(d_fd,LOCK_EX);
					if (read(d_fd,buf,st.st_size) == st.st_size) {
						idx1=0;idx2=0;
						while (idx2<st.st_size) {
							int len=sgetline(buf,linebuf,&idx2,255);
							puts(linebuf);
							if (!canundeny(linebuf,nowtime)) {
								if (idx1!=0) {
									buf[idx1]=0x0a;
									idx1++;
								}
								memcpy(buf+idx1,linebuf,len);
								idx1+=len;
							}
							else {
								char uid[IDLEN+1],*p;
								memcpy(uid,linebuf,IDLEN);
								uid[IDLEN]=0;
								for (p=uid;*p;p++) 
									if (*p==' ') 
									{
										*p=0;
										break;
									}
							    deldeny(&deliveruser,bh->filename,uid);
							}
						}
						buf[idx1]=0x0a;
						idx1++;
						lseek(d_fd,0,SEEK_SET);
						write(d_fd,buf,idx1);
						ftruncate(d_fd,idx1);
					}
					flock(d_fd,LOCK_UN);
					close(d_fd);
				}
			}
		}
	}
}

main()
{
	resolve_boards();
	resolve_ucache();
	bzero(&deliveruser,sizeof(struct userec));
	strcpy(deliveruser.userid,"deliver");
	strcpy(deliveruser.username,"自动发信系统");
	strcpy(fromhost,"127.0.0.1");
	apply_boards(undenyboardy);
}

