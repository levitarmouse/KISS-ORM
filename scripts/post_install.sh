#!/bin/bash
mkdir -p config/kissorm;
cp -rp ./vendor/levitarmouse/kiss_orm/tables.ini.dist ./tables.ini;
ln -s ./vendor/levitarmouse/kiss_orm/kissGen.php .;
cd ./config/kissorm/;
ln -s ../../vendor/levitarmouse/kiss_orm/config/Bootstrap.php .;
cp -rp ../../vendor/levitarmouse/kiss_orm/config/database.ini.dist ./database.ini;
