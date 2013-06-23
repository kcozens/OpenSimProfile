#This file updates the tables used by OpenSimProfile to the latest schema.
#Use this file if you are updating an existing installation of the search
#module. If you are doing a first time install, use the osprofile.sql file.

#SVN revision 69
BEGIN;
ALTER TABLE `userprofile` ADD `profileImage` VARCHAR( 36 ) NOT NULL AFTER `profileLanguages`,
ADD `profileAboutText` TEXT NOT NULL AFTER `profileImage` ;
COMMIT;

#SVN revision 78
BEGIN;
ALTER TABLE `usernotes` DROP PRIMARY KEY ;
ALTER TABLE `usernotes` ADD UNIQUE ( `useruuid` , `targetuuid` );
COMMIT;
