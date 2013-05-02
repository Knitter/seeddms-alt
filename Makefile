VERSION=4.2.1
SRC=CHANGELOG inc conf utils index.php languages views op out README.md README.Notification README.Ubuntu drop-tables-innodb.sql styles js TODO LICENSE Makefile webdav install
#restapi webapp

dist:
	mkdir -p tmp/seeddms-$(VERSION)
	cp -a $(SRC) tmp/seeddms-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../seeddms-$(VERSION).tar.gz seeddms-$(VERSION))
	rm -rf tmp

pear:
	(cd SeedDMS_Core/; pear package)
	(cd SeedDMS_Lucene/; pear package)
	(cd SeedDMS_Preview/; pear package)

webdav:
	mkdir -p tmp/seeddms-webdav-$(VERSION)
	cp webdav/* tmp/seeddms-webdav-$(VERSION)
	(cd tmp; tar --exclude=.svn -czvf ../seeddms-webdav-$(VERSION).tar.gz seeddms-webdav-$(VERSION))
	rm -rf tmp

doc:
	phpdoc -d SeedDMS_Core --ignore 'getusers.php,getfoldertree.php,config.php,reverselookup.php' -t html

.PHONY: webdav
