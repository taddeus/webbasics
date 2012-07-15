all: test docs

test:
	phpunit

docs:
	phpdoc

upload: docs
	rsync -r --delete --force build/docs mv:tk.nl/docs/webbasics

clean:
	rm -rf build

.PHONY: test docs upload clean
