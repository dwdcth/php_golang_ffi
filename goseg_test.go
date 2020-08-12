package main

import (
	"testing"
	"time"
)

func TestCut(t *testing.T) {
	LoadDict([]string{"dict/zh/dict.txt"})
	text := "大数据存储与管理软件自适应技术计算摄像人工智能高并发高可用"
	cutSum := 2000

	start := time.Now()
	for i := 1; i <= cutSum; i++ {
		CutChar(text)
	}
	end := time.Now()
	t.Logf("CutChar %d 次用时：%v\n", cutSum, end.Sub(start))

	tLen, tCap := 0, 0
	start = time.Now()
	for i := 1; i <= cutSum; i++ {
		CutPointer(text, &tLen, &tCap)
	}
	end = time.Now()
	t.Logf("CutPointer %d 次用时：%v\n", cutSum, end.Sub(start))

	res := make([]string, 0)
	start = time.Now()
	for i := 1; i <= cutSum; i++ {
		CutSlice(text, &res)
	}
	end = time.Now()
	t.Logf("CutSlice %d 次用时：%v\n", cutSum, end.Sub(start))

}
