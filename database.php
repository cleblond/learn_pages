<?php

// The SQL to uninstall this tool
$DATABASE_UNINSTALL = array(
"drop table if exists {$CFG->dbprefix}tsugi_sample_module"
);

// The SQL to create the tables if they don't exist
$DATABASE_INSTALL = array(
array( "{$CFG->dbprefix}eo_learn_pages",
"create table {$CFG->dbprefix}eo_learn_pages (
    link_id     INTEGER NOT NULL,
    user_id     INTEGER NOT NULL,
    page_path   TEXT NOT NULL,
    updated_at  DATETIME NOT NULL,

    CONSTRAINT `{$CFG->dbprefix}eo_learn_pages_ibfk_1`
        FOREIGN KEY (`link_id`)
        REFERENCES `{$CFG->dbprefix}lti_link` (`link_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `{$CFG->dbprefix}eo_learn_pages_ibfk_2`
        FOREIGN KEY (`user_id`)
        REFERENCES `{$CFG->dbprefix}lti_user` (`user_id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE(link_id, user_id)
) ENGINE = InnoDB DEFAULT CHARSET=utf8")
);

