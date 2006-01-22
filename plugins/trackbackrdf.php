<?php
class TrackbackRDF extends Plugin {
	
	function TrackbackRDF() {
		$this->plugin_desc = _("Add TrackBack auto-discovery RDF to entry pages.");
		$this->plugin_version = "0.1.0";
	}

	function add_rdf(&$ent) {
		if (! $ent->allow_tb) return false;
	?>
	<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="<?php echo $ent->permalink(); ?>"
    dc:identifier="<?php echo $ent->permalink(); ?>"
    dc:title="<?php echo $ent->subject; ?>"
    trackback:ping="<?php echo $ent->permalink() ?>trackback.php" />
</rdf:RDF>
-->
<?php		
	}
	
}
$plug = new TrackbackRDF();
$plug->registerEventHandler("blogentry", "OnOutput", "add_rdf");
?>
