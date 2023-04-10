##  ********************************************
##  Coppermine Photo Gallery
##  ************************
##  v1.0 originally written by Gregory Demar
##
##  @copyright  Copyright (c) 2003-2023 Coppermine Dev Team
##  @license    GNU General Public License version 3 or later; see LICENSE
##
##  ********************************************
##  sql/update.sql
##  @since  1.7.02
##  ********************************************

# The following line has to be removed when the moderator group feature will be re-enabled!
UPDATE CPG_albums SET moderator_group = 0;

# add new fields to PICTURES table
ALTER TABLE CPG_pictures ADD mime varchar(255) NOT NULL default 'image/*';
ALTER TABLE CPG_pictures ADD ftype varchar(32) NOT NULL default 'image';

# new config values
INSERT INTO CPG_config VALUES ('thumbs_per', '20');