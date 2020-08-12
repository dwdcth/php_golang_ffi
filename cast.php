<?php

function makeGoInt(FFI $ffi, int $val): FFI\CData
{
    $goInt = $ffi->new('GoInt');
    $goInt->cdata = $val;
    return $goInt;
}

function makeGoStrSlice(FFI $ffi, array $strs): FFI\CData
{
    $goSlice = $ffi->new('GoSlice', 0);
    $size = count($strs);

    $goStrs = $ffi->new("GoString[$size]", 0);
    foreach ($strs as $i => $str) {
        $goStr = makeGoStr($ffi, $str);
        $goStrs[$i] = $goStr;
    }
    $goSlice->data = $goStrs;
    $goSlice->len = $size;
    $goSlice->cap = $size;
    return $goSlice;
}

function makeGoStr(FFI $ffi, string $str): FFI\CData
{
    $goStr = $ffi->new('GoString', 0);
    $size = strlen($str);
    $cStr = FFI::new("char[$size]", 0);

    FFI::memcpy($cStr, $str, $size);
    $goStr->p = $cStr;
    $goStr->n = strlen($str);
    return $goStr;
}

function fromGoSlicePointer(FFI $ffi, FFI\CData $pSlice, int $len, string $type, callable $castFunc = null): array
{
//    typedef struct  { void *data; GoInt len; GoInt cap; } GoSlice;
    $res = [];
    for ($i = 0; $i < $len; $i++) {
        $goDataPtr = $pSlice + $i * FFI::sizeof($ffi->type($type));
        $goData = $ffi->cast($type, $goDataPtr);
        if ($castFunc) {
            $res[] = $castFunc($goData, $ffi);
        } else {
            $res[] = $goData;
        }
    }
    return $res;
}

function fromGoSlice(FFI $ffi, FFI\CData $slice, string $type, callable $castFunc = null): array
{
    return fromGoSlicePointer($ffi, $slice->data, $slice->len, $type, $castFunc);
}


function fromGoStr(FFI\CData $goStr, FFI $ffi = null): string
{
//    typedef struct { const char *p; ptrdiff_t n; } GoString;
    return FFI::string($goStr->p, $goStr->n);
}

function formGoSlicePointer(FFI $ffi, int $pointer, int $len, int $cap): FFI\CData
{
    $slice = $ffi->cast('GoSlice*', $pointer);
    $slice->len = $len;
    $slice->cap = $cap;
    return $slice;
}

function makeCStr(string $str): FFI\CData
{
    $size = strlen($str);
    $cStr = FFI::new("char[$size+1]", 0);
    FFI::memcpy($cStr, $str, $size);
    return $cStr;
}


function makeCCharArr(array $strs): FFI\CData
{
    $size = count($strs);
    $cCharArr = FFI::new("char* [$size]", 0);
    foreach ($strs as $i => $str) {
        $cCharArr[$i] = makeCStr($str);
    }
    return $cCharArr;
}