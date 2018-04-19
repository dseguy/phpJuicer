# Exakat PHPJuicer

The PHPJuicer is a utility tool that extract classes informations from code bases.

## Features

* Extract 
  + Classes
  + Interfaces
  + Traits
  + Namespaces
  + Functions
  + Constants
  + Properties
  + Methods
  + Typehints
  + Default values
* Compares two versions for migration purposes
* Provides stats for the application

## Installation

## Community


Join our Slack channels are on exakat.slack.com : [#phpjuicer](https://www.exakat.io/wp-login.php?action=slack-invitation)

Please note that this project is released with a
[Contributor Code of Conduct](http://contributor-covenant.org/version/1/4/).
By participating in this project and its community you agree to abide by those terms.


## Running

First, run phpjuicer on the code 

`php phpjuicer extract <path to source> <destination>`

Then, grab some stats, in CSV format

`php phpjuicer stats <destination>`

Get list of versions available

`php phpjuicer list <destination>`

Get diff between versions, as MD text. Use version from above

`php phpjuicer diff <destination> <version1> <version2>`

Get phpJuicer version

`php phpjuicer version`


## Options

There are no options at the moment. That will come. 

## Limitations

* Alpha works : 'This works here' 
* No options of any kind
* Manual tests (doh)
* Includes every folder in the proved path, including test and vendor
* Works only with Git
* Requires the PHP 7.0, sqlite3 ext/ast

## Code of conduct 

Please note that this project is released with a
[Contributor Code of Conduct](http://contributor-covenant.org/version/1/4/).
By participating in this project and its community you agree to abide by those terms.