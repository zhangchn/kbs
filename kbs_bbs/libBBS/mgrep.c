/* Copyright (c) 1991 Sun Wu and Udi Manber.  All Rights Reserved. */
/* multipattern matcher */
#include <stdio.h>
#include <ctype.h>
#define MAXPAT  256
#define MAXLINE 1024
#define MAXSYM  256
#define MAXMEMBER1 4096
#define MAXPATFILE 260000
#define BLOCKSIZE  8192
#define MAXHASH    8192
#define mm 	   8191
#define max_num    30000
#define W_DELIM	   128
#define L_DELIM    10

extern int ONLYCOUNT, FNAME, SILENT, FILENAMEONLY, num_of_matched;
extern int INVERSE;
extern int WORDBOUND, WHOLELINE, NOUPPER;
extern unsigned char *CurrentFileName;
extern int total_line;

#ifdef BBSMAIN
#define printf prints
#define putchar outc
#endif
struct pat_list {
    int index;
    struct pat_list *next;
};
struct pattern_image {
	int LONG;
	int SHORT;
	int p_size;
	unsigned char SHIFT1[MAXMEMBER1];
	unsigned char tr[MAXSYM];
	unsigned char tr1[MAXSYM];
    struct pat_list *HASH[MAXHASH];
	unsigned char buf[MAXPATFILE + BLOCKSIZE];
	unsigned char pat_spool[MAXPATFILE + 2 * max_num + MAXPAT];
	unsigned char *patt[max_num];
	unsigned char pat_len[max_num];
};

int m_short(unsigned char* text,int start,int end,struct pattern_image* patt_img);

int releasepf(struct pattern_image* patt_img)
{
    int i;
    for (i = 0; i < MAXHASH; i++) {
        struct pat_list* curr;
        curr=patt_img->HASH[i];
        while (curr!=NULL) {
            struct pat_list* next;
            next=curr->next;
        	free((void*)curr);
        	curr=next;
        }
    }
    free((void*)patt_img);
}

int prepf(int fp,struct pattern_image** ppatt_img)
{
    int length = 0, i, p = 1, pdx = 0, num_pat;
    struct pattern_image *patt_img;
    unsigned char *pat_ptr , temp[10];
    unsigned Mask = 15;
    int num_read;

    *ppatt_img=malloc(sizeof(struct pattern_image));
    patt_img=*ppatt_img;
    pat_ptr=patt_img->pat_spool;
    patt_img->LONG = 0;
    patt_img->SHORT = 0;
    patt_img->p_size = 0;
    while ((num_read = read(fp, patt_img->buf + length, BLOCKSIZE)) > 0) {
        length = length + num_read;
        if (length > MAXPATFILE) {
#ifdef BBSMAIN
            prints("maximum pattern file size is %d\n", MAXPATFILE);
#endif
            bbslog("3error", "maximum pattern file size is %d\n", MAXPATFILE);
            return -1;
        }
    }
    patt_img->buf[length] = '\n';
    i = 0;
    p = 1;
    while (i < length) {
        patt_img->patt[p] = pat_ptr;
        if (WORDBOUND)
            *pat_ptr++ = W_DELIM;
        if (WHOLELINE)
            *pat_ptr++ = L_DELIM;
        while ((*pat_ptr = patt_img->buf[i++]) != '\n')
            pat_ptr++;
        if (WORDBOUND)
            *pat_ptr++ = W_DELIM;
        if (WHOLELINE)
            *pat_ptr++ = L_DELIM;       /* Can't be both on */
        *pat_ptr++ = 0;
        p++;
    }
    if (p > max_num) {
#ifdef BBSMAIN
        prints("maximum number of patterns is %d\n", max_num);
#endif
        bbslog("3error", "maximum number of patterns is %d\n", max_num);
        return -1;
    }
    for (i = 1; i < 20; i++)
        *pat_ptr = i;           /* boundary safety zone */
    for (i = 0; i < MAXSYM; i++)
        patt_img->tr[i] = i;
    if (NOUPPER) {
        for (i = 'A'; i <= 'Z'; i++)
            patt_img->tr[i] = i + 'a' - 'A';
    }
    if (WORDBOUND) {
        for (i = 0; i < 128; i++)
            if (!isalnum(i))
                patt_img->tr[i] = W_DELIM;
    }
    for (i = 0; i < MAXSYM; i++)
        patt_img->tr1[i] = patt_img->tr[i] & Mask;
    num_pat = p - 1;
    patt_img->p_size = MAXPAT;
    for (i = 1; i <= num_pat; i++) {
        p = strlen(patt_img->patt[i]);
        patt_img->pat_len[i] = p;
        if (p != 0 && p < patt_img->p_size)
            patt_img->p_size = p;
    }
    if (patt_img->p_size == 0) {
#ifdef BBSMAIN
        prints("the pattern file is empty\n");
#endif
        bbslog("3error", "the pattern file is empty\n");
        return -1;
    }
    if (length > 400 && patt_img->p_size > 2)
        patt_img->LONG = 1;
    if (patt_img->p_size == 1)
        patt_img->SHORT = 1;
    for (i = 0; i < MAXMEMBER1; i++)
        patt_img->SHIFT1[i] = patt_img->p_size - 2;
    for (i = 0; i < MAXHASH; i++) {
        patt_img->HASH[i] = 0;
    }
    for (i = 1; i <= num_pat; i++)
        f_prep(i, patt_img->patt[i],patt_img);
}

int mgrep_str(char *text, int num,struct pattern_image* patt_img)
{
    register char r_newline = '\n';
    unsigned char buf_text[MAXLINE];
    register int buf_end, num_read, i = 0, j, start, end, residue = 0;


    if (INVERSE && ONLYCOUNT)
        countline(text, num);

    start = 0;

    while (text[start] != r_newline && start < num)
        start++;
    if (start > MAXLINE - 2)
        return -1;

    buf_text[0] = '\n';         /* initial case */
    strncpy(buf_text + 1, text, start);
    buf_text[start + 1] = '\n'; /* initial case */

    if (patt_img->SHORT)
        m_short(buf_text, 0, start + 1,patt_img);
    else
        monkey1(buf_text, 0, start + 1,patt_img);

    if (FILENAMEONLY && num_of_matched) {
        return num_of_matched;
    }

    if (start == num)           /*如果都等于总长度了，显然就一行 */
        return 0;

    buf_end = end = num - 1;
    while (text[end] != r_newline && end >= 0)
        end--;                  /*最后一行无\n的 */

    residue = buf_end - end + 1;
    /*text[start - 1] = r_newline;*/
    if (patt_img->SHORT)
        m_short(text, start, end, patt_img);
    else
        monkey1(text, start, end, patt_img);
    if (FILENAMEONLY && num_of_matched) {
        return num_of_matched;
    }

    if (residue > MAXLINE - 2)
        return -1;
    /*
     * end of while(num_read = ... 
     */
    if (residue > 1) {
        strncpy(buf_text + 1, text + end, residue);
        text[residue] = '\n';
        if (patt_img->SHORT)
            m_short(buf_text, 0, residue,patt_img);
        else
            monkey1(buf_text, 0, residue,patt_img);
    }
    return num_of_matched;
}                               /* end mgrep */

mgrep(fd,patt_img)
int fd;
struct pattern_image *patt_img;
{
    register char r_newline = '\n';
    unsigned char text[2 * BLOCKSIZE + MAXLINE];
    register int buf_end, num_read, i = 0, j, start, end, residue = 0;

    text[MAXLINE - 1] = '\n';   /* initial case */
    start = MAXLINE - 1;

    while ((num_read = read(fd, text + MAXLINE, BLOCKSIZE)) > 0) {
        if (INVERSE && ONLYCOUNT)
            countline(text + MAXLINE, num_read);
        buf_end = end = MAXLINE + num_read - 1;
        while (text[end] != r_newline && end > MAXLINE)
            end--;
        residue = buf_end - end + 1;
        text[start - 1] = r_newline;
        if (patt_img->SHORT)
            m_short(text, start, end,patt_img);
        else
            monkey1(text, start, end,patt_img);
        if (FILENAMEONLY && num_of_matched) {
            return num_of_matched;
        }
        start = MAXLINE - residue;
        if (start < 0) {
            start = 1;
        }
        strncpy(text + start, text + end, residue);
    }                           /* end of while(num_read = ... */
    text[MAXLINE] = '\n';
    text[start - 1] = '\n';
    if (residue > 1) {
        if (patt_img->SHORT)
            m_short(text, start, end,patt_img);
        else
            monkey1(text, start, end,patt_img);
    }
    return;
}                               /* end mgrep */

static countline(text, len)
unsigned char *text;
int len;
{
    int i;

    for (i = 0; i < len; i++)
        if (text[i] == '\n')
            total_line++;
}


monkey1(text, start, end,patt_img)
int start, end;
register unsigned char *text;
struct pattern_image* patt_img;
{
    register unsigned char *textend;
    register unsigned hash, i;
    register unsigned char shift;
    register int m1, j, Long = patt_img->LONG;
    int pat_index, m = patt_img->p_size;
    int MATCHED = 0;
    register unsigned char *qx;
    register struct pat_list *p;
    unsigned char *lastout;
    int OUT = 0;

    textend = text + end;
    m1 = m - 1;
    lastout = text + start + 1;
    text = text + start + m1;
    while (text <= textend) {
        hash = patt_img->tr1[*text];
        hash = (hash << 4) + (patt_img->tr1[*(text - 1)]);
        if (Long)
            hash = (hash << 4) + (patt_img->tr1[*(text - 2)]);
        shift = patt_img->SHIFT1[hash];
        if (shift == 0) {
            hash = 0;
            for (i = 0; i <= m1; i++) {
                hash = (hash << 4) + (patt_img->tr1[*(text - i)]);
            }
            hash = hash & mm;
            p = patt_img->HASH[hash];
            while (p != 0) {
                pat_index = p->index;
                p = p->next;
                qx = text - m1;
                j = 0;
                while (patt_img->tr[patt_img->patt[pat_index][j]] == patt_img->tr[*(qx++)])
                    j++;
                if (j > m1) {
                    if (patt_img->pat_len[pat_index] <= j) {
                        if (text > textend)
                            return;
                        num_of_matched++;
                        if (FILENAMEONLY || SILENT)
                            return;
                        MATCHED = 1;
                        if (ONLYCOUNT) {
                            while (*text != '\n')
                                text++;
                        } else {
                            if (!INVERSE) {
                                if (FNAME)
                                    printf("%s: ", CurrentFileName);
                                while (*(--text) != '\n');
                                while (*(++text) != '\n')
                                    putchar(*text);
                                printf("\n");
                            } else {
                                if (FNAME)
                                    printf("%s: ", CurrentFileName);
                                while (*(--text) != '\n');
                                if (lastout < text)
                                    OUT = 1;
                                while (lastout < text)
                                    putchar(*lastout++);
                                if (OUT) {
                                    putchar('\n');
                                    OUT = 0;
                                }
                                while (*(++text) != '\n');
                                lastout = text + 1;
                            }
                        }
/*
				else {
			  		if(FNAME) printf("%s: ",CurrentFileName);
                          		while(*(--text) != '\n');
                          		while(*(++text) != '\n') putchar(*text);
			  		printf("\n");
				}
*/
                    }
                }
                if (MATCHED)
                    break;
            }
            if (!MATCHED)
                shift = 1;
            else {
                MATCHED = 0;
                shift = m1;
            }
        }
        text = text + shift;
    }
    if (INVERSE && !ONLYCOUNT)
        while (lastout <= textend)
            putchar(*lastout++);
}

int m_short(unsigned char* text,int start,int end,struct pattern_image* patt_img)
{
    register unsigned char *textend;
    register unsigned i;
    register int j;
    register struct pat_list *p, *q;
    register int pat_index;
    int MATCHED = 0;
    int OUT = 0;
    unsigned char *lastout;
    unsigned char *qx;

    textend = text + end;
    lastout = text + start + 1;
    text = text + start - 1;
    while (++text <= textend) {
        p = patt_img->HASH[*text];
        while (p != 0) {
            pat_index = p->index;
            p = p->next;
            qx = text;
            j = 0;
            while (patt_img->tr[patt_img->patt[pat_index][j]] == patt_img->tr[*(qx++)])
                j++;
            if (patt_img->pat_len[pat_index] <= j) {
                if (text >= textend)
                    return 0;
                num_of_matched++;
                if (FILENAMEONLY || SILENT)
                    return 0;
                if (ONLYCOUNT) {
                    while (*text != '\n')
                        text++;
                } else {
                    if (FNAME)
                        printf("%s: ", CurrentFileName);
                    if (!INVERSE) {
                        while (*(--text) != '\n');
                        while (*(++text) != '\n')
                            putchar(*text);
                        printf("\n");
                        MATCHED = 1;
                    } else {
                        while (*(--text) != '\n');
                        if (lastout < text)
                            OUT = 1;
                        while (lastout < text)
                            putchar(*lastout++);
                        if (OUT) {
                            putchar('\n');
                            OUT = 0;
                        }
                        while (*(++text) != '\n');
                        lastout = text + 1;
                        MATCHED = 1;
                    }
                }
            }
            if (MATCHED)
                break;
        }
        MATCHED = 0;
    }                           /* while */
    if (INVERSE && !ONLYCOUNT)
        while (lastout <= textend)
            putchar(*lastout++);
}

f_prep(pat_index, Pattern,patt_img)
unsigned char *Pattern;
int pat_index;
struct pattern_image* patt_img;
{
    int i, j, m;
    register unsigned hash, Mask = 15;
	struct pat_list *pt, *qt;

    m = patt_img->p_size;
    for (i = m - 1; i >= (1 + patt_img->LONG); i--) {
        hash = (Pattern[i] & Mask);
        hash = (hash << 4) + (Pattern[i - 1] & Mask);
        if (patt_img->LONG)
            hash = (hash << 4) + (Pattern[i - 2] & Mask);
        if (patt_img->SHIFT1[hash] >= m - 1 - i)
            patt_img->SHIFT1[hash] = m - 1 - i;
    }
    if (patt_img->SHORT)
        Mask = 255;             /* 011111111 */
    hash = 0;
    for (i = m - 1; i >= 0; i--) {
        hash = (hash << 4) + (patt_img->tr[Pattern[i]] & Mask);
    }
/*
	if(INVERSE) hash = Pattern[1];
*/
    hash = hash & mm;
    qt = (struct pat_list *) malloc(sizeof(struct pat_list));
    qt->index = pat_index;
    pt = patt_img->HASH[hash];
    qt->next = pt;
    patt_img->HASH[hash] = qt;
}
