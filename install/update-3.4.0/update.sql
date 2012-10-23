ALTER TABLE tblFolders DROP FOREIGN KEY `tblFolders_owner`;
ALTER TABLE tblFolders ADD CONSTRAINT `tblFolders_owner` FOREIGN KEY (`owner`) REFERENCES `tblUsers` (`id`);

ALTER TABLE tblDocuments DROP FOREIGN KEY `tblDocuments_owner`;
ALTER TABLE tblDocuments ADD CONSTRAINT `tblDocuments_owner` FOREIGN KEY (`owner`) REFERENCES `tblUsers` (`id`);

ALTER TABLE tblDocuments DROP FOREIGN KEY `tblDocuments_folder`;
ALTER TABLE tblDocuments ADD CONSTRAINT `tblDocuments_folder` FOREIGN KEY (`folder`) REFERENCES `tblFolders` (`id`);

ALTER TABLE tblDocumentContent DROP FOREIGN KEY `tblDocumentDocument_document`;
ALTER TABLE tblDocumentContent ADD CONSTRAINT `tblDocumentContent_document` FOREIGN KEY (`document`) REFERENCES `tblDocuments` (`id`);

ALTER TABLE tblDocumentLinks DROP FOREIGN KEY `tblDocumentLinks_user`;
ALTER TABLE tblDocumentLinks ADD CONSTRAINT `tblDocumentLinks_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`);

ALTER TABLE tblDocumentFiles DROP FOREIGN KEY `tblDocumentFiles_user`;
ALTER TABLE tblDocumentFiles ADD CONSTRAINT `tblDocumentFiles_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`);

ALTER TABLE tblGroupMembers DROP PRIMARY KEY;
ALTER TABLE tblGroupMembers ADD UNIQUE(`groupID`,`userID`);
ALTER TABLE tblGroupMembers ADD CONSTRAINT `tblGroupMembers_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE;
ALTER TABLE tblGroupMembers ADD CONSTRAINT `tblGroupMembers_group` FOREIGN KEY (`groupID`) REFERENCES `tblGroups` (`id`) ON DELETE CASCADE;

CREATE TABLE `tblAttributeDefinitions` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) default NULL,
  `objtype` tinyint(4) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `multiple` tinyint(4) NOT NULL default '0',
  `minvalues` int(11) NOT NULL default '0',
  `maxvalues` int(11) NOT NULL default '0',
  `valueset` text default NULL,
	UNIQUE(`name`),
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `tblFolderAttributes` (
  `id` int(11) NOT NULL auto_increment,
  `folder` int(11) default NULL,
  `attrdef` int(11) default NULL,
  `value` text default NULL,
  PRIMARY KEY  (`id`),
	UNIQUE (folder, attrdef),
  CONSTRAINT `tblFolderAttributes_folder` FOREIGN KEY (`folder`) REFERENCES `tblFolders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblFolderAttributes_attrdef` FOREIGN KEY (`attrdef`) REFERENCES `tblAttributeDefinitions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `tblDocumentAttributes` (
  `id` int(11) NOT NULL auto_increment,
  `document` int(11) default NULL,
  `attrdef` int(11) default NULL,
  `value` text default NULL,
  PRIMARY KEY  (`id`),
	UNIQUE (document, attrdef),
  CONSTRAINT `tblDocumentAttributes_document` FOREIGN KEY (`document`) REFERENCES `tblDocuments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblDocumentAttributes_attrdef` FOREIGN KEY (`attrdef`) REFERENCES `tblAttributeDefinitions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE tblDocumentContent ADD COLUMN `id` int(11) NOT NULL auto_increment PRIMARY KEY FIRST;
CREATE TABLE `tblDocumentContentAttributes` (
  `id` int(11) NOT NULL auto_increment,
  `content` int(11) default NULL,
  `attrdef` int(11) default NULL,
  `value` text default NULL,
  PRIMARY KEY  (`id`),
	UNIQUE (content, attrdef),
  CONSTRAINT `tblDocumentContentAttributes_document` FOREIGN KEY (`content`) REFERENCES `tblDocumentContent` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tblDocumentContentAttributes_attrdef` FOREIGN KEY (`attrdef`) REFERENCES `tblAttributeDefinitions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
CREATE TABLE `tblUserPasswordHistory` (
  `id` int(11) NOT NULL auto_increment,
  `userID` int(11) NOT NULL default '0',
  `pwd` varchar(50) default NULL,
  `date` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  CONSTRAINT `tblUserPasswordHistory_user` FOREIGN KEY (`userID`) REFERENCES `tblUsers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE tblUsers ADD COLUMN `pwdExpiration` datetime NOT NULL default '0000-00-00 00:00:00';
ALTER TABLE tblUsers ADD COLUMN `loginfailures` tinyint(4) NOT NULL default '0';
ALTER TABLE tblUsers ADD COLUMN `disabled` smallint(4) NOT NULL default '0';
ALTER TABLE tblUsers ADD UNIQUE(`login`);
UPDATE tblVersion set major=3, minor=4, subminor=0;
