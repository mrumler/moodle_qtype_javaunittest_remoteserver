<?php

/**
 * This is the remote server for compiling and executing java and
 * the junit tests for the qtype_javaunittest module for moodle.
 *
 * @package 	qtype
 * @subpackage 	javaunittest
 * @author 		Michael Rumler, rumler@ni.tu-berlin.de, Berlin Institute of Technology
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once (dirname ( __FILE__ ) . '/config.php');
require_once (dirname ( __FILE__ ) . '/version.php');

// check if client is allowed
if (! in_array ( $_SERVER ["REMOTE_ADDR"], $whitelist )) {
	header ( "HTTP/1.1 403 Forbidden" );
	echo "REMOTE SERVER ERROR: Request from unauthorized " . $_SERVER ["REMOTE_ADDR"] .
			 ".\n Please contact your webmaster.";
	die ();
}

// check versions
if ($_POST ['clientversion'] != $version) {
	header ( "HTTP/1.1 422 Invalid Version" );
	echo "REMOTE SERVER ERROR: Moodle client and server version differ (" .
			 $_POST ['clientversion'] . " and " . $version . ")\n" .
			 "Please contact your webmaster for an update.\n";
	die ();
}

// handle request
if ($_POST ['compile']) {
	header ( "HTTP/1.1 222 Computing" );
	handle_compile_request ();
} else if ($_POST ['execute']) {
	header ( "HTTP/1.1 222 Computing" );
	handle_execute_request ();
} else {
	header ( "HTTP/1.1 200 OK" );
	echo "This is the remote server for compiling and executing java
			and the junit tests for the qtype_javaunittest module.
            <br>\nPlease send data for compiling or executing via moodle client.";
	die ();
}

/**
 * Handles a compile request, echos are catched by the client via CURL.
 */
function handle_compile_request() {
	
	// receive parameters
	$studentid = $_POST ['studentid'];
	$studentsclassname = $_POST ['studentsclassname'];
	$questionid = $_POST ['questionid'];
	$attemptid = $_POST ['attemptid'];
	$javaclassname = $_POST ['javaclassname'];
	$testclassname = $_POST ['testclassname'];
	$javacode = $_POST ['javacode'];
	$junitcode = $_POST ['junitcode'];
	
	// create unique directory
	$temp_folder = DATAROOT . 'javaunittest_temp_' . $studentid . '_' . $questionid .
			 '_' . $attemptid;
	if (file_exists ( $temp_folder )) {
		$this->delTree ( $temp_folder );
	}
	mkdir_recursive ( $temp_folder );
	
	// create java file
	$studentclass_path = $temp_folder . '/';
	$studentclass = $studentclass_path . $studentsclassname . '.java';
	touch ( $studentclass );
	$fh = fopen ( $studentclass, 'w' ) or
			 die ( "cannot open file" . $studentclass . "<br>\n" );
	fwrite ( $fh, $javacode );
	fclose ( $fh );
	
	// create junit file
	$testfile = $temp_folder . '/' . $testclassname . '.java';
	touch ( $testfile );
	$fh = fopen ( $testfile, 'w' ) or die ( 
			"cannot open file" . $testfile . "<br>\n" );
	fwrite ( $fh, $junitcode );
	fclose ( $fh );
	
	// compile the student's response
	$compileroutput = compile ( $studentclass, $temp_folder, $studentsclassname );
	$compileroutput = substr_replace ( $compileroutput, '', 0, 
			strlen ( $temp_folder ) + 1 );
	$compileroutput = addslashes ( $compileroutput );
	$compileroutput = str_replace ( $temp_folder, "\n", $compileroutput );
	if (strlen ( $compileroutput ) == 0) {
		echo "Compilation OK\n";
	} else {
		echo $compileroutput;
	}
}

/**
 * Handles an execute request, echos are catched by the client via CURL.
 */
function handle_execute_request() {
	
	// receive parameters
	$studentid = $_POST ['studentid'];
	$studentsclassname = $_POST ['studentsclassname'];
	$questionid = $_POST ['questionid'];
	$attemptid = $_POST ['attemptid'];
	$javaclassname = $_POST ['javaclassname'];
	$testclassname = $_POST ['testclassname'];
	
	// check unique directory
	$temp_folder = DATAROOT . 'javaunittest_temp_' . $studentid . '_' . $questionid .
			 '_' . $attemptid;
	if (! file_exists ( $temp_folder )) {
		die ( "no such directory: " . $temp_folder . "<br>\n" );
	}
	
	// check java file
	$studentclass_path = $temp_folder . '/';
	$studentclass = $studentclass_path . $studentsclassname . '.java';
	if (! file_exists ( $studentclass )) {
		die ( "no such java file: " . $studentclass . "<br>\n" );
	}
	
	// check javac file
	$studentclass_path = $temp_folder . '/';
	$studentclass = $studentclass_path . $studentsclassname . '.class';
	if (! file_exists ( $studentclass )) {
		die ( "no such class file: " . $studentclass . "<br>\n" );
	}
	
	// check junit file
	$testfile = $temp_folder . '/' . $testclassname . '.java';
	if (! file_exists ( $testfile )) {
		die ( "no such junit file: " . $testfile . "<br>\n" );
	}
	
	// execute JUnit test
	$executionoutput = execute ( $temp_folder, $testfile, $testclassname, 
			$studentclass, $studentsclassname );
	
	echo $executionoutput;
	
	delTree ( $temp_folder );
}

/**
 * Assistent function to compile students code and junit test code.
 *
 * @param string $studentclass
 *        	the response java code of the student
 * @param string $temp_folder
 *        	the temporary folder defined in grade_response() we use to store the
 *        	data
 * @param string $studentsclassname
 *        	the name of the class which has to be compiled
 * @return string $compileroutput the output of the compiler
 */
function compile($studentclass, $temp_folder, $studentsclassname) {
	
	// work out the compile command line
	$compileroutputfile = $temp_folder . '/' . $studentsclassname .
			 '_compileroutput.log';
	touch ( $compileroutputfile );
	
	$command = PATH_TO_JAVAC . ' -cp ' . PATH_TO_JUNIT . ' ' . $studentclass .
			 ' -Xstdout ' . $compileroutputfile;
	
	// execute the command
	$output = shell_exec ( escapeshellcmd ( $command ) );
	
	// get the content of the copiler output
	$compileroutput = file_get_contents ( $compileroutputfile );
	
	return $compileroutput;
}

/**
 * Assistent function to compile and execute the JUnit test.
 *
 * @param string $temp_folder
 *        	the temporary folder defined in grade_response() we use to store the
 *        	data
 * @param string $testfile
 *        	the JUnit test file
 * @param string $testfilename
 *        	the name of the JUnit test file
 * @param string $studentclass
 *        	the response of the student
 * @param string $studentsclassname
 *        	the name of the class which has to be tested
 * @return string $executionoutput the output of the JUnit test
 */
function execute($temp_folder, $testfile, $testfilename, $studentclass, 
		$studentsclassname) {
	
	// create the log file to store the output of the JUnit test
	$executionoutputfile = $temp_folder . '/' . $studentsclassname .
			 '_executionoutput.log';
	$testfilename = str_replace ( ".java", "", $testfilename );
	touch ( $studentclass );
	
	// work out the compile command line to compile the JUnit test
	$command = PATH_TO_JAVAC . ' -cp ' . PATH_TO_JUNIT . ' -sourcepath ' . $temp_folder .
			 ' ' . $testfile . ' > ' . $executionoutputfile . ' 2>&1';
	
	// execute the command
	$output = shell_exec ( $command );
	$commandWithSecurity = PATH_TO_JAVA . " -Djava.security.manager=default" .
			 " -Djava.security.policy=" . PATH_TO_POLICY . " ";
	$command = $commandWithSecurity . " -cp " . PATH_TO_JUNIT . ":" . PATH_TO_HAMCREST .
			 ":" . $temp_folder . " org.junit.runner.JUnitCore " . $testfilename .
			 " > " . $executionoutputfile . " 2>&1";
	
	// $command = PATH_TO_JAVA . " -cp " . PATH_TO_JUNIT . ":" . PATH_TO_HAMCREST .
	// ":" . $temp_folder . " org.junit.runner.JUnitCore " . $testfilename .
	// " > " . $executionoutputfile . " 2>&1";
	
	// execute the command
	$output = shell_exec ( $command );
	
	// get the execution log
	$executionoutput = file_get_contents ( $executionoutputfile );
	
	return $executionoutput;
}

/**
 * Assistent function to read the content of a file.
 *
 * @param array $fileinfo
 *        	the file which content will be readed
 * @return string $contents the content of the file
 */
function get_file_content($fileinfo) {
	$fs = get_file_storage ();
	
	// Get file
	$file = $fs->get_file ( $fileinfo ['contextid'], $fileinfo ['component'], 
			$fileinfo ['filearea'], $fileinfo ['itemid'], $fileinfo ['filepath'], 
			$fileinfo ['filename'] );
	
	// Read content
	if ($file) {
		$contents = $file->get_content ();
	} else {
		die ( "cannot read file " . $file . "<br>\n" );
	}
	return $contents;
}

/**
 * Assistent function to create a directory inclusive missing top directories.
 *
 * @param string $folder
 *        	the absolute path
 * @return boolean true on success
 */
function mkdir_recursive($folder) {
	if (is_dir ( $folder )) {
		return true;
	}
	if (! mkdir_recursive ( dirname ( $folder ) )) {
		return false;
	}
	$rc = mkdir ( $folder, 01700 );
	if (! $rc) {
		die ( "cannot create directory " . $folder . "<br>\n" );
	}
	return $rc;
}

/**
 * Assistent function to delete a directory tree.
 *
 * @param string $dir
 *        	the absolute path
 * @return boolean true on success, false else
 */
function delTree($dir) {
	$files = array_diff ( scandir ( $dir ), array (
			'.',
			'..' 
	) );
	foreach ( $files as $file ) {
		(is_dir ( "$dir/$file" )) ? delTree ( "$dir/$file" ) : unlink ( "$dir/$file" );
	}
	$rc = rmdir ( $dir );
	return $rc;
}

?>
