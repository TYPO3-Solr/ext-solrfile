
#
# Table structure for table 'tx_solr_indexqueue_file'
#
CREATE TABLE tx_solr_indexqueue_file (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	root int(11) DEFAULT '0' NOT NULL,
	changed int(11) DEFAULT '0' NOT NULL,
	indexed int(11) DEFAULT '0' NOT NULL,
	errors text NOT NULL,

	access varchar(255) DEFAULT 'c:0' NOT NULL,
	additional_fields text NOT NULL,

	reference_type varchar(255) DEFAULT '' NOT NULL,
	reference_uid int(11) unsigned DEFAULT '0' NOT NULL,
	reference_document longtext NOT NULL,
	reference_document_id varchar(255) DEFAULT '' NOT NULL,
	reference_sys_language_uid int(11) DEFAULT '0' NOT NULL,

	file_link varchar(255) DEFAULT '' NOT NULL,
	file_path varchar(255) DEFAULT '' NOT NULL,
	file_name varchar(255) DEFAULT '' NOT NULL,
	file_sha1 varchar(40) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	KEY reference (reference_type,reference_uid),
	KEY reference_document (reference_document_id)
) ENGINE=InnoDB;

