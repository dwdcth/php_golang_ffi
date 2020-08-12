#define FFI_LIB "./libgoseg.so"
typedef long int ptrdiff_t;
typedef long unsigned int size_t;

typedef long long GoInt64;
typedef GoInt64 GoInt;
typedef signed char GoInt8;

typedef long unsigned int GoUintptr;

typedef struct { const char *p; ptrdiff_t n; } GoString;

typedef struct  { void *data; GoInt len; GoInt cap; } GoSlice;

extern void LoadDict(GoSlice p0);

extern char* CutChar(GoString p0);

extern GoUintptr CutPointer(GoString p0, GoInt* p1, GoInt* p2);

extern void CutSlice(GoString p0, GoSlice* p1);