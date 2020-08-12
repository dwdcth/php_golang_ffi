[上一篇文章](https://github.com/dwdcth/phpjieba_ffi)中用PHP的FFI成功了调用了cjieba，但是速度实在是慢，4个函数循环调用20次，用了居然1分50多秒，而且C版本只比PHP快一点点，看来是cjieba本身慢了。

这次发现了一个golang的分词库[gse](github.com/go-ego/gse)，试试导出为动态库，用FFI加载。

# 碰到的问题

## 不能导出go指针

由于之前对cgo不熟悉，以为go可以很方便的导出到C，没想到一开始就把我难倒。

> panic: runtime error: cgo result has Go pointer

##  不能导出go结构体

一开始直接在go里返回了[]string，没想到报错了，原来go不允许导出含有指针的数据结构

>  Go type not supported in export: struct

后来想，要不导出[]string 的指针，但是如果只有指针地址，没有长度，遍历肯定会出错，于是构造了一个结构体，保存指针地址和长度，没想到还是不行。

这期间，由于工作忙（主要是懒），断断续续的看了一下cgo相关的内容，先跑通了C调用go，于是再试着用FFI，很快也跑通了。

# go导出C动态库的简单说明

在go里，导出一个函数到C动态库，其实非常简单，需要在`import C` 包，并在导出的函数加上`export  函数名` ，加上一个空的main 函数即可，如：

```go
package main

import (
	"C"
)
//必须和函数同名
//export PlusOne
func PlusOne(num int) int {
	return num + 1
}
func main() {
	
}

```

编译方法如下：

`go build -buildmode=c-shared -o libdemo.so demo.go`

就会自动生成 so 和 libdemo.h 头文件，打开libdemo.h，可以看到里面是各种go 数据类型的定义，摘除部分如下：

```c
typedef signed char GoInt8;
typedef unsigned char GoUint8;
typedef short GoInt16;
typedef unsigned short GoUint16;
typedef int GoInt32;
typedef unsigned int GoUint32;
typedef long long GoInt64;
typedef unsigned long long GoUint64;
```

可以看到还包含了一个另外的头文件`#include <stddef.h>` ，可以使用`gcc -E -P  libdemo.h -o libdemo_unfold.h`  展开stddef.h合并到一个到头文件，然后复制我们需要的类型定义即可。

# PHP FFI调用go

# ## PHP如何初始化go类型变量

由于go的string，slice导出后，都是一个结构体，不是一个简单类型，这里我们先看看string。

`typedef struct { const char *p; ptrdiff_t n; } GoString;`

可以看到string有一个char* 指针，和一个表示长度的n，可以说明go的string是不带'\n'的，和C的字符串不同。然而一开始，我居然还特意加了'\n'，然后给n也加1，结果发现不对，在go那边加上输出后，才发现出错了。

对于这种结构体，用加载动态库的FFI实例调用 new 方法。即`$goStr = $ffi->new('GoString',0)`，注意new的第二个参数要传0，表示这个对象PHP不用管理内存。在这个地方，我又掉坑里了。

然后要给p和n赋值，对于n，比较简单，直接给字符串长度，但是对于p，就比较麻烦。 

翻看PHP文档，发现有个memcpy方法，于是试了一下，成功的实现了PHP和go之间传string。

完整的代码如下：

```php
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
```

## FFI 静态方法和FFI实例方法的区别

在上面的代码里，既有FFI的静态方法，也有实例方法，它们之间的区别在于，静态方法只有常用的数据类型，如果int，char；实例方法，才能调用加载的so里面的类型。



## FFI的三种调用思路

下面我说一下三种调用思路，建议第一种，这里就不贴代码了，完整的代码看github。

### 1 通过 C.char

由于go不能返回slice string，那么换个思路，把数组拼接成字符串，然后返回C.char。这种方式最简单，而且在后面的跑分测试里发现，也是最有效率的。

### 2 通过slice 指针传参数

既然不能返回，那么我们修改传入的参数是否可以呢。通过测试发现确实可行。

### 3 返回指针的地址

这就是一开始我的想法，这种方法有点麻烦，而且速度也不占优。



# 跑分测试

可以下载我github的代码，对于go需要开启go mod。

先`make lib`，生成go的动态库，然后`make php_test` 和`make go_test` 查看对比。

go的

>  TestCut: goseg_test.go:18: CutChar 2000 次用时：41.511794ms
>  TestCut: goseg_test.go:26: CutPointer 2000 次用时：45.24684ms
>  TestCut: goseg_test.go:34: CutSlice 2000 次用时：42.537337ms

php的

> CutChar 2000 次用时：0.027052 s
> CutSlice 2000 次用时：0.038451 s
> CutPointer 2000 次用时：0.038257 s

可以发现php居然比go的还快，比cjieba快了不知道多少倍，看来以后一些耗CPU的方式，可以用go来开发动态库，给PHP用，比通过接口调用可以快很多。
假如go的接口5ms，PHP这边收到请求解析1ms，这样2000次就是 6s了。可以发现FFI是接口调用的0.03/6 = 0.005，是几百倍数量级的提升。

当然前提是选择一个性能高的FFI才行，如果比PHP还慢，那就不必了。

另外FFI可以预加载，鸟哥的博客写的很详细了，大家可以去[看看](https://www.laruence.com/2020/03/11/5475.html)。



