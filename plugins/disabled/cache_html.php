<?
class CacheHTML extends Plugin {

	function CacheHTML() {
		$this->plugin_desc = "Generate and show flat HTML files for pages.";
		$this->plugin_version = "0.1.0";
	}

	function start_buffer() {
		ob_start();
	}

	function make_cache($ref) {
		$content = ob_get_clean();
		$fs = NewFS();
		$fs->write_file("
	}
}

?>
