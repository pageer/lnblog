<?php
# Plugin: TrackbackRDF
# This simple little plugin just injects some TrackBack auto-discovery RDF code into
# your blog entries.  This code will allow blogging software that supports auto-detecting
# TrackBack URLs to automatically send TrackBacks to your entries.  Note that the code
# will only be inserted for entries that have TrackBacks enabled.
class TrackbackRDF extends Plugin {

    function __construct() {
        $this->plugin_desc = _("Add TrackBack auto-discovery RDF to entry pages.");
        $this->plugin_version = "0.1.2";
        parent::__construct();
    }

    function add_rdf(&$ent) {
        if (! $ent->allow_tb) {
            return false;
        }
    ?>
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
<rdf:Description
    rdf:about="<?php echo $ent->permalink(); ?>"
    dc:identifier="<?php echo $ent->permalink(); ?>"
    dc:title="<?php echo $ent->subject; ?>"
    trackback:ping="<?php echo $ent->uri('trackback');?>" />
</rdf:RDF>
-->
<?php
    }

}
$plug = new TrackbackRDF();
$plug->registerEventHandler("blogentry", "OnOutput", "add_rdf");
$plug->registerEventHandler("article", "OnOutput", "add_rdf");
