package main

import (
	"C"
	"fmt"
	"github.com/go-ego/gse"
	"reflect"
	"strings"
	"unsafe"
)

type cutFunc func(text string, hmm ...bool) []string

var (
	segmenter gse.Segmenter
)

//export LoadDict
func LoadDict(files []string) {
	fmt.Println("dict path:", files)
	segmenter = gse.New(files...)
}

func cut(text string) []string {
	return segmenter.CutAll(text)
}

//export CutChar
func CutChar(text string) *C.char {
	res := cut(text)
	return C.CString(strings.Join(res[:], " "))
}

//export CutPointer
func CutPointer(text string, pLen *int, pCap *int) uintptr {
	res := cut(text)
	hdr := (*reflect.SliceHeader)(unsafe.Pointer(&res))
	*pLen = len(res)
	*pCap = cap(res)
	return hdr.Data
}

//export CutSlice
func CutSlice(text string, res *[]string)  {
	_res := cut(text)
	*res = _res
}

func main() {
}


