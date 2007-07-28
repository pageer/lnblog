<?php
if (! defined("SIMPLE_TEST")) define("SIMPLE_TEST", "simpletest/");

require_once(SIMPLE_TEST."unit_tester.php");
require_once(SIMPLE_TEST."reporter.php");
require_once(SIMPLE_TEST."mock_objects.php");

set_include_path(get_include_path().PATH_SEPARATOR.realpath(".."));

$test =& new GroupTest("Full test suite");

$standard_prefix = "TestOf";

$test_cases = array();
# Each element of $test_cases is an array with the file name (minus .php extension) in 
# the first element and the name of the test class in the second.
$test_cases[] = array("path_test", "TestOfPath");
$test_cases[] = array("simplexmlwriter_test", "TestOfSimpleXMLWriter");
$test_cases[] = array("simplexmlreader_test", "TestOfSimpleXMLReader");
$test_cases[] = array("nativefs_test", "TestOfNativeFS");
$test_cases[] = array("server_test", "TestOfServer");
$test_cases[] = array("uri_test", "TestOfURI");
$test_cases[] = array("blog_test", "TestOfBlog");
$test_cases[] = array("blogentry_test", "TestOfBlogEntry");

if (isset($_GET['case'])) {
	foreach ($test_cases as $case) {
		if (strtolower($case[1]) == strtolower($standard_prefix.$_GET['case'])) {
			require_once($case[0].".php");
			$test->addTestCase(new $case[1]());
		}
	}
} else {
	foreach ($test_cases as $case) {
		require_once($case[0].".php");
		$test->addTestCase(new $case[1]());
	}
}

$test->run(new HtmlReporter());
?>