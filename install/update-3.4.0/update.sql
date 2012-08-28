CREATE TABLE `tblUserPasswordHistory` (
  `id` int(11) NOT NULL auto_increment,
  `userID` int(11) NOT NULL default '0',
  `pwd` varchar(50) default NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) DEFAULT CHARSET=utf8;
ALTER TABLE tblUsers ADD COLUMN `pwdExpiration` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE tblUsers ADD COLUMN `loginfailures` tinyint(4) NOT NULL default '0';
ALTER TABLE tblUsers ADD COLUMN `disabled` smallint(4) NOT NULL default '0';
UPDATE tblVersion set major=3, minor=4, subminor=0;
