#!/bin/bash

## Test scrpit to create a database and import tables from a sql file
## This script is not meant to be run in production

$HOST = "localhost"
$USER = "root"
$DB = "import_eusdfjkvbsa"

mysql -h "${HOST}" -u "${USER}" -p -e "CREATE DATABASE ${DB};"
mysql -h "${HOST}" -u "${USER}" -p ${DB} < smtplatrine_database_tables.sql
mysql -h "${HOST}" -u "${USER}" -p -e "USE ${DB}; SHOW TABLES;"
mysql -h "${HOST}" -u "${USER}" -p -e "DROP DATABASE ${DB};"