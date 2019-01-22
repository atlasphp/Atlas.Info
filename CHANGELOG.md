# CHANGELOG

## 1.2.0

Adds a WHERE clause to ensure that Info is only fetching table names for user-
created tables, and not views or system tables.

All column 'default' values for integer and float/double/real database types are
now retained as integer and float values, instead of as strings.

## 1.1.0

Fixes issue #3 so as not to overwrite the 'primary' column information if the
column is both a primary key and a foreign key in MySQL.

Recognizes UNSIGNED integer types in MySQL and reports them as part of the
column information 'type' element.

Adds a new element to the column information, 'options', to list ENUM value
options. The element appears for all databases, but is implemented only for
MySQL.

## 1.0.0

Update docs.

## 1.0.0-beta1

Fixes PgsqlInfo::fetchAutoincSequence() method for columns with no default value (#2).

## 1.0.0-alpha1

First release.
