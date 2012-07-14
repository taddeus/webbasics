all: test docs

test:
	phpunit

docs:
	phpdoc

clean:
	rm -rf build

.PHONY: test docs clean
