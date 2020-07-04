# DEPRECATED

***NOTE***<br />
***THIS PLUGIN DOES NOT WORK IN RC 1.4.x AND ITS DEVELOPMENT IS DICONTINUED***<br />
***all functionalities will be migrated into the new plugin [roundcube-toolbox](https://github.com/gianlucagiacometti/roundcube-toolbox)***<br />


AUTHOR

Gianluca Giacometti (php@gianlucagiacometti.it)



VERSION

1.3.6



RELEASE DATE

10-05-2018



INSTALL

Requirements :
- jQuery UI.

To install this plugin, copy all files into /plugin/aliases folder and add it to the plugin array in config/main.inc.php:

    // List of active plugins (in plugins/ directory)
    
    $rcmail_config['plugins'] = array('aliases');



CONFIGURATION

Copy 'config.inc.php.dist' to 'config.inc.php'.

Edit the plugin configuration file 'config.inc.php' and choose the appropriate options:

$rcmail_config['alias_driver'] = 'sql';

    so far only sql is available

$rcmail_config['alias_sql_dsn'] = value;

    example value: 'pgsql://username:password@host/database'
    example value: 'mysql://username:password@host/database'

$rcmail_config['alias_sql_aliases'] = query;

    query used to select all mailbox aliases
    default mailbox alias to itself is excluded and managed by forward plugin
    the query depends upon your postfixadmin database structure
    placeholders %goto and %address must be kept unchanged

    default query: 'SELECT * FROM alias WHERE goto = %goto AND domain = %domain AND address != %goto ORDER BY address'
    example query: 'SELECT * FROM aliases WHERE forwardto = %goto AND domain = %domain AND address != %goto ORDER BY address'

$rcmail_config['alias_sql_allaliases'] = query;

    query used to select all domain aliases but user's
    need to avoid alias duplicates in the domain
    the query depends upon your postfixadmin database structure
    placeholders %domain, %goto and %address must be kept unchanged

    default query: 'SELECT * FROM alias WHERE domain = %domain AND goto != %goto ORDER BY address'
    example query: 'SELECT * FROM aliases WHERE domain = %domain AND forwardto != %goto ORDER BY address'

$rcmail_config['alias_sql_read'] = query;

    query used to select an alias
    the query depends upon your postfixadmin database structure
    placeholders $goto and %address must be kept unchanged

    default query: 'SELECT * FROM alias WHERE goto = %goto AND address = %address'
    example query: 'SELECT * FROM aliases WHERE forwardto = %goto AND address = %address'

$rcmail_config['alias_sql_update'] = query;

    query used to update an alias
    the query depends upon your postfixadmin database structure
    placeholders %newalias, %goto, %address and %active must be kept unchanged

    default query: 'UPDATE alias SET address = %newalias, modified = %modified, active = %active WHERE goto = %goto AND address = %address'
    example query: 'UPDATE aliases SET address = %newalias, active = %active WHERE forwardto = %goto AND address = %address'

$rcmail_config['alias_sql_delete'] = query;

    query used to delete an alias
    the query depends upon your postfixadmin database structure
    placeholders %goto and %address must be kept unchanged

    default query: 'DELETE FROM alias WHERE address = %address AND goto = %goto'
    example query: 'DELETE FROM aliases WHERE address = %address AND forwardto = %goto'

$rcmail_config['alias_sql_create'] = query;

    query used to create a new an alias
    the query depends upon your postfixadmin database structure
    placeholders %goto, %address, %domain, %created, %modified and %active must be kept unchanged

    default query: 'INSERT INTO alias (address, goto, domain, created, modified, active) VALUES (%address, %goto, %domain, %created, %modified, %active)'
    example query: 'INSERT INTO aliases (address, forwardto, domain, created, updated, active) VALUES (%address, %goto, %domain, %created, %modified, %active)'



LICENCE

Licensed under GNU GPL2 licence.



NOTE

The code is based on SieveRules plugin (sieverules) by Philip Weir.
Thank you Philip.
