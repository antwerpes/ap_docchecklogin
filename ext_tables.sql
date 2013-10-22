CREATE TABLE fe_users (
	tx_apdocchecklogin_prof int(11) DEFAULT '',
	tx_apdocchecklogin_disc int(11) DEFAULT ''
);

-- expand list_type field for typo3 4.x compat
CREATE TABLE tt_content (
	list_type varchar(255) DEFAULT '0' NOT NULL
);