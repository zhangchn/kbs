/* static.c : 上站人次统计 */
/*Modify By SmallPig*/
#include <time.h>
#include <stdio.h>
#define MAX_LINE        15

struct
{
  int no[24];                   /* 次数 */
  int sum[24];                  /* 总合 */
}      st;


char    *Ctime(date)
time_t  *date;
{
        static char buf[80];

        strcpy(buf, (char *)ctime(date));
        buf[strlen(buf)-1] = '\0';
        return buf;
}

main(argc, argv)
  char *argv[];
{
  char *progmode;
  FILE *fp;
  char buf[256], *p;
  char date[80];
  int now;
  int hour, max = 0, item, total = 0;
  int totaltime = 0;
  int i, j;
  struct tm * date_tm;
  char    *blk[10] =
  {
      "  ", "  ", "  ", "  ", "  ",
      "□", "□", "□", "□", "□",
  };

  if ((fp = fopen("/home0/bbs/usies", "r")) == NULL)
  {
    printf("cann't open usies\n");
    return 1;
  }

  now=time(0);
  date_tm = localtime(&now);
  sprintf(date,"%02u/%02u",date_tm->tm_mon+1,date_tm->tm_mday);

  while (fgets(buf, 256, fp))
  {
    hour = atoi(buf+7);
    if (hour < 0 || hour > 23)
    {
       printf("%s", buf);
       continue;
    }
    if(strncmp(buf+1,date,5))
        continue;
    if ( strstr(buf, "ENTER", 5))
    {
      st.no[hour]++;
      continue;
    }
    if ( p = (char *)strstr(buf+40, "Stay:"))
    {
      st.sum[hour] += atoi( p + 6);
      continue;
    }
  }
  fclose(fp);

  for (i = 0; i < 24; i++)
  {
    total += st.no[i];
    totaltime += st.sum[i];
    if (st.no[i] > max)
      max = st.no[i];
  }

  item = max / MAX_LINE + 1;

  if ((fp = fopen("/home0/bbs/0Announce/bbslists/countlogins", "w")) == NULL)
  {
    printf("Cann't open countlogins\n");
    return 1;
  }

  fprintf(fp,"\n[36m    ┌——————————— 超过 \033[01m\033[37m1000\033[00m\033[36m 将不显示千位数字 ———————————┐\n");
  for (i = max/item+1 ; i >= 0; i--)
  {
    fprintf(fp, "[34m%4d[36m│[33m",(i)*item);
    for (j = 0; j < 24; j++)
    {
      if ((item * (i) > st.no[j]) && (item * (i-1) <= st.no[j]) && st.no[j])
      {
        if(st.no[j]>=2000)
                /*fprintf(fp, "[35m###[33m"); Leeward 97.12.08 */
                fprintf(fp, "\033[1m[33m%-3d\033[m[33m", (st.no[j]) % 1000);
        else if (st.no[j] >= 1000) /* Leeward 98.02.27 */
                fprintf(fp, "\033[1m[37m%-3d\033[m[33m", (st.no[j]) % 1000);
        else
                fprintf(fp, "[35m%-3d[33m", (st.no[j]));
        continue;
      }
      if(st.no[j]-item*i<item && item*i<st.no[j])
              fprintf(fp,"%s ", blk[((st.no[j]-item * i)*10)/item]);
      else if(st.no[j]-item * i>=item)
              fprintf(fp,"%s ",blk[9]);
      else
           fprintf(fp,"   ");
    }
    fprintf(fp, "[36m│\n");
  }
  fprintf(fp, "   [36m"
    " └———[37m   BBS 水木清华站  上站人次表   [36m———[37m%s[36m——┘\n"
    /*"    [34m  0  1  2  3  4  5  6  7  8  9  10 11 [31m12 13 14 15 16 17 18 19 20 21 22 23 \n\n"*/ /* Leeward 98.02.27 */
    "    [34m  1  2  3  4  5  6  7  8  9  10 11 12 [31m13 14 15 16 17 18 19 20 21 22 23 24\n\n"
    "               [36m 1 [33m□[36m = [37m%-5d [36m总共上站人次：[37m%-9d[36m平均使用时间：[37m%d[m \n\n", Ctime(&now),item,total, totaltime / total + 1); /* Leeward 98.09.24 add the 2nd \n for SHARE MEM in ../main.c */

  fclose(fp);
}
