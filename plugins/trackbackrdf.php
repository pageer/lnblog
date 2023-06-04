<?php

use LnBlog\Plugins\PluginTemplate;

# Plugin: TrackbackRDF
# This simple little plugin just injects some TrackBack auto-discovery RDF code into
# your blog entries.  This code will allow blogging software that supports auto-detecting
# TrackBack URLs to automatically send TrackBacks to your entries.  Note that the code
# will only be inserted for entries that have TrackBacks enabled.
class TrackbackRDF extends Plugin
{
    public const TEMPLATE = 'rdf_tpl.php';
    private const PLUGIN_DIR = 'trackbackrdf';

    /**
     * @codeCoverageIgnore
     */
    public static function getLoader(): callable {
        return function (): Plugin {
            return new self();
        };
    }

    function __construct() {
        $this->plugin_desc = _("Add TrackBack auto-discovery RDF to entry pages.");
        $this->plugin_version = "0.2.0";

        parent::__construct();

        $this->registerEventHandler("blogentry", "OnOutput", "addRdf");
        $this->registerEventHandler("article", "OnOutput", "addRdf");
    }

    function addRdf($ent) {
        if ($ent->allow_tb) {
            $template = new PluginTemplate(self::PLUGIN_DIR, self::TEMPLATE);
            $template->set('ENTRY', $ent);
            $template->output();
        }
    }
}

return TrackbackRDF::getLoader();
