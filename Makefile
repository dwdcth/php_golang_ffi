lib:
	go build -buildmode=c-shared -o libgoseg.so goseg.go
go_test:
	go test  -v .
php_test:
	php demo.php
unfold:
	gcc -E -P libgoseg.h -o libgoseg_unfold.h