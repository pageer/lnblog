<?php if (isset($FORM_MESSAGE)) { ?>
<p><strong style="color: red"><?php echo $FORM_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo $FORM_ACTION; ?>">
<fieldset>
<legend style="font-weight: bold"><?php p_("Path Information"); ?></legend>
<h3><?php p_("Document Root"); ?></h3>
<p><?php pf_('To compute <abbr title="Uniform Resource Locator">URL</abbr>s, %s needs to know the full path to your document root on the web server.',
PACKAGE_NAME);?></p>
<div>
<label for="docroot"><?php p_("Web document root directory"); ?></label>
<input type="text" name="docroot" id="docroot" <?php if (isset($DOC_ROOT)) { echo 'value="'.$DOC_ROOT.'"'; } ?> />
</div>
<h3><?php p_("Subdomain root and main domain (optional)");?></h3>
<p>
<?php pf_("If your host does not support subdomains, or if you do not plan to use them with %s, then skip this section.", PACKAGE_NAME);?>
</p>
<p id="subdom_desc">
<?php pf_("If you plan to put blogs on subdomains, %s also needs to know your domain name and your subdomain root directory.  Normally, your host will create a directory for each subdomain you have, and the subdomain root is the directory in which those directories are stored.  For examlpe, if you have subdomains 'bob.example.com' and 'jeff.example.com', which are stored in the directories /home/whatever/www/bob and /home/whatever/www/jeff, then your subdomain root would be /home/whatever/www.", PACKAGE_NAME);?>
</p>
<div>
<label for="subdomroot"><?php p_("Subdomain root directory");?></label>
<input type="text" name="subdomroot" id="subdomroot" <?php if (isset($SUBDOM_ROOT)) { echo 'value="'.$SUBDOM_ROOT.'"'; } ?> />
</div>
<div>
<label for="domain"><?php p_("Domain name (e.g. mydomain.com)");?></label>
<input type="text" name="domain" id="domain" <?php if (isset($SUBDOM_ROOT)) { echo 'value="'.$SUBDOM_ROOT.'"'; } ?> />
</div>
</fieldset>
<div>
<span class="basic_form_submit"><input type="submit" value="<?php p_('Submit'); ?>" /></span>
<span class="basic_form_clear"><input type="reset" value="<?php p_('Clear'); ?>" /></span>
</div>
</form>
