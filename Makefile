all: test docs

test:
	phpunit

docs:
	phpdoc

upload: docs
	scp -r build/docs mv:tk.nl/docs/webbasics

clean:
	rm -rf build

.PHONY: test docs clean upload
