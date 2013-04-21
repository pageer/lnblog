<div id="lbcode_editor">
<strong><a href="http://www.skepticats.com/lnblog/documentation/files/docs/lbcode-txt.html">
<?php p_("LBCode");?></a></strong><a onclick="lbcode_editor.toggle_show('editorbox', this);" href="#dummy" >(-)</a>
<div id="editorbox" style="display:inline">
<button type="button" id="bold" accesskey="b" title="<?php p_('Bold');?>" onclick="lbcode_editor.one_parm('b', '<?php p_('Bold text');?>');"><strong><?php p_("B");?></strong></button>
<button type="button" id="italic" accesskey="i" title="<?php p_('Italic');?>" onclick="lbcode_editor.one_parm('i', '<?php p_('Italicized text');?>');"><em><?php p_("I");?></em></button>
<button type="button" id="underline" accesskey="u" title="<?php p_('Underline');?>" onclick="lbcode_editor.one_parm('u', '<?php p_('Enter text to underline');?>');"><span style="text-decoration: underline"><?php p_("U");?></span></button>
<button type="button" id="abr" accesskey="a" title="<?php p_('Abbreviation');?>" onclick="lbcode_editor.two_parm('ab', '<?php p_("Abbreviation");?>', '<?php p_("Full phrase for abbreviation");?>');"><?php p_('abbr');?></button>
<button type="button" id="acr" accesskey="c" title="<?php p_('Acronym'); ?>" onclick="lbcode_editor.two_parm('ac', '<?php p_("Acronym/abbreviation");?>', '<?php p_("Full phrase for acronym");?>');"><?php p_('ac'); ?></button>
<button type="button" id="url" accesskey="l" title="<?php p_('Hyperlink'); ?>" onclick="lbcode_editor.two_parm('url', '<?php p_("Link text");?>', '<?php p_("Link URL");?>', {meta: 'http://'});"><span style="color: blue; text-decoration: underline"><?php p_('link');?></span></button>
<button type="button" id="code" accesskey="o" title="<?php p_('Code');?>" onclick="lbcode_editor.one_parm('code', '<?php p_("Text to mark as code");?>');"><code><?php p_('code');?></code></button>
<button type="button" id="color" accesskey="c" title="<?php p_('Colored text'); ?>" onclick="lbcode_editor.set_color('<?php p_("Colored text");?>', '<?php p_("Color as a name, hex code, or rgb value");?>');">
<span style="color:red"><?php p_('color');?></span>
</button>&nbsp;<select id="colorselect">
<option value="custom"><?php p_("Custom")?></option>
<option value="red"><?php p_("Red");?></option>
<option value="blue"><?php p_("Blue");?></option>
<option value="yellow"><?php p_("Yellow");?></option>
<option value="green"><?php p_("Green");?></option>
<option value="purple"><?php p_("Purple");?></option>
</select>
<button type="button" id="img" accesskey="m" title="<?php p_('Image'); ?>" onclick="lbcode_editor.set_img('<?php p_("Enter the image title");?>', '<?php p_("Enter the image URL");?>');">
<?php p_('image');?>
</button>&nbsp;<select id="imgalign">
<option value="center"><?php p_('Center');?></option>
<option value="left"><?php p_('Left');?></option>
<option value="right"><?php p_('Right');?></option>
</select>
<!--
<button type="button" id="qt" accesskey="q" title="<?php p_('Short Quote');?>" onclick="lbcode_editor.opt_parm('q', '<?php p_('Quoted text');?>', '<?php p_("Optional citation");?>');">"<?php p_("quote");?>"</button>
<button type="button" id="bq" accesskey="k" title="<?php p_('Blockquote'); ?>" onclick="lbcode_editor.opt_parm('quote', '<?php p_('Block quotation text');?>', '<?php p_("Optional citation");?>');">"<?php p_("bq");?>"</button>
-->
</div>
</div>
