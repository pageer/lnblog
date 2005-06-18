<h1>Create site map</h1>
<?php if (isset($SITEMAP_ERROR)) { ?>
<h3><?php echo $SITEMAP_ERROR; ?></h3>
<p><?php echo $ERROR_MESSAGE; ?></p>
<?php } ?>
<p>This page will help you create a site map to display in the navigation
bar at the top of your blog.  This file is stored under the name 
<?php echo SITEMAP_FILE; ?> in the root directory of your weblog for a personal sitemap
or in the <?php echo PACKAGE_NAME; ?> installation directory for the system
default.  This file in simply a series of 
<abbr title="Hypertext Markup Language">HTML</abbr> links, each on it's own
line, which the template will process into a list.  If you require a more 
complicated menu bar, you will have to create a custom template.</p>
<p><strong>Note:</strong> You must have Javascript enabled for this page
to work correctly.</p>
<fieldset>
<div>
<label for="linktext">Regular text</label>
<input type="text" id="linktext" name="linktext" />
</div>
<div>
<label for="linktitle">Hover text ("tooltip" text)</label>
<input type="text" id="linktitle" name="linktitle" />
</div>
<div>
<label for="linktarget">Link <abbr title="Unirofm Resource Locator">URL</abbr></label>
<input type="text" id="linktarget" name="linktarget" />
</div>
<div>
<button id="addlink" onclick="addLink();" />Add link</button>
<button id="clear" onclick="addLink();" />Clear</button>
</div>
<!--
<div>
<label for="testlink">Preview current link</label>
<a id="testlink"></a>
</div>
-->
<form>
<textarea id="output" name="output" rows="10"><?php if (isset($CURRENT_SITEMAP)) echo $CURRENT_SITEMAP; ?></textarea>
<div><input type="submit" value="Save sitemap" />
</form>
</fieldset>
