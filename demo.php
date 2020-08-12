<?php
$loadffi = microtime(true);
$goseg = FFI::load("libgoseg_tiny.h");
$endloadffi = microtime(true);
printf("load: " . ($endloadffi - $loadffi) . "\n");
require_once 'cast.php';


$files = makeGoStrSlice($goseg, ['dict/zh/dict.txt']);
$goseg->LoadDict($files);

$text = makeGoStr($goseg, '大数据存储与管理软件自适应技术计算摄像人工智能高并发高可用');

$result = $goseg->CutChar($text);
$words = FFI::string($result);
FFI::free($result);
printf("CutChar:%s\n", $words);


$result = makeGoStrSlice($goseg, []);
$goseg->CutSlice($text, FFI::addr($result));
$words = fromGoSlice($goseg, $result, 'GoString', 'fromGoStr');
printf("CutSlice:%s\n", implode(" ", $words));
FFI::free($result);


$len = makeGoInt($goseg, 0);
$cap = makeGoInt($goseg, 0);
$pointer = $goseg->CutPointer($text, FFI::addr($len), FFI::addr($cap));
$pSlice = $goseg->cast('void*', $pointer);
$words = fromGoSlicePointer($goseg, $pSlice, $len->cdata, 'GoString', 'fromGoStr');
printf("CutPointer:%s\n", implode(" ", $words));


$cutSum = 2000;

$startCut = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $result = $goseg->CutChar($text);
    $words = FFI::string($result);
}
$endCut = microtime(true);
printf("CutChar $cutSum 次用时：%f s\n", $endCut - $startCut);

$startCut = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $result = makeGoStrSlice($goseg, []);
    $goseg->CutSlice($text, FFI::addr($result));
    $words = fromGoSlice($goseg, $result, 'GoString', 'fromGoStr');
}
$endCut = microtime(true);
printf("CutSlice $cutSum 次用时：%f s\n", $endCut - $startCut);


$startCut = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $len = makeGoInt($goseg, 0);
    $cap = makeGoInt($goseg, 0);
    $pointer = $goseg->CutPointer($text, FFI::addr($len), FFI::addr($cap));
    $pSlice = $goseg->cast('void*', $pointer);
    $words = fromGoSlicePointer($goseg, $pSlice, $len->cdata, 'GoString', 'fromGoStr');
}
$endCut = microtime(true);
printf("CutPointer $cutSum 次用时：%f s\n", $endCut - $startCut);
