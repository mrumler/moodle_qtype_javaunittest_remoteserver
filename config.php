<?php

/**
 * The php configuration file for the remote part of 
 * moodle qtype_javaunittext.
 *
 * To use the remote server make sure USE_REMOTE is enabled in the clients config.
 *
 * @package 	qtype
 * @subpackage 	javaunittest
 * @author 		Michael Rumler, rumler@ni.tu-berlin.de, Berlin Institute of Technology
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * White list of all allowed moodle client domains for security reason.
 * Edit and add your moodle domain and/or ip here.
 * e.g. $whitelist = array (); $whitelist [0] = 'my.server.tld';
 *
 * @staticvar array whitelist
 */
$whitelist = array ();
$whitelist [0] = 'my.moodle.tld';
$whitelist [1] = '127.0.0.1';
$whitelist [2] = '123.456.789.0';

/**
 * Path to store files and compilation.
 * In the non remote version the moodle variable $CFG->dataroot is used.
 * Make sure the PHP/webserver user has enough rights here, we propose chmod 01700.
 * e.g. define ( 'DATAROOT', '/var/www/moodledataremote/' );
 *
 * @staticvar string DATAROOT
 */
define ( 'DATAROOT', '/var/www/moodledataremote/' );

/**
 * Configure local path settings here.
 *
 * @staticvar string PATH_TO_JAVAC
 * @staticvar string PATH_TO_JAVA
 * @staticvar string PATH_TO_JUNIT
 * @staticvar string PATH_TO_HAMCREST
 * @staticvar string PATH_TO_POLICY
 */
// e.g. define('PATH_TO_JAVAC', '/usr/lib/jvm/java-7-openjdk-amd64/bin/javac');
define ( 'PATH_TO_JAVAC', '/usr/lib/jvm/java-7-openjdk-amd64/bin/javac' );

// e.g. define ( 'PATH_TO_JAVA', '/usr/lib/jvm/java-7-openjdk-amd64/bin/java' );
define ( 'PATH_TO_JAVA', '/usr/lib/jvm/java-7-openjdk-amd64/bin/java' );

// e.g. define('PATH_TO_JUNIT', '/usr/share/java/junit.jar');
define ( 'PATH_TO_JUNIT', '/opt/junit/junit.jar' );

// e.g. define('PATH_TO_HAMCREST', '/usr/share/java/hamcrest.jar');
define ( 'PATH_TO_HAMCREST', '/opt/junit/hamcrest.jar' );

// e.g. define('PATH_TO_POLICY', dirname(__FILE__) . '/polfiles/defaultpolicy');
define ( 'PATH_TO_POLICY', dirname ( __FILE__ ) . '/polfiles/defaultpolicy' );

?>
