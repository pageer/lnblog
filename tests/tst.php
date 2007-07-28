<?php
error_reporting(E_ALL);
class foo {
	function foo() { $this->foo = "1234"; }
	function bar($a="0987") {
		if (isset($this)) {
			echo $this->foo."\n";
			$this->baz = "queep";
		} else echo "$a\n";
	}
}

class bar {
	function bar() { $this->baz = "qwer"; }
	function fizz() {
		$f = new foo();
		$f->bar();
		foo::bar();
		echo $this->baz."\n";
	}
}

$b = new bar();
$b->fizz();
?>
