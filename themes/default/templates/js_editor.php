<div id="lbcode_editor">
<strong><a href="http://www.skepticats.com/lnblog/documentation/files/docs/lbcode-txt.html">
<?php p_("LBCode");?></a></strong><a onclick="toggle_show('editorbox', this);" href="#dummy" >(-)</a>
<div id="editorbox" style="display:inline">
<button type="button" id="bold" accesskey="b" title="<?php p_('Bold');?>" onclick="one_parm('b', '<?php p_('Bold text');?>');"><strong><?php p_("B");?></strong></button>
<button type="button" id="italic" accesskey="i" title="<?php p_('Italic');?>" onclick="one_parm('i', '<?php p_('Italicized text');?>');"><em><?php p_("I");?></em></button>
<button type="button" id="underline" accesskey="u" title="<?php p_('Underline');?>" onclick="one_parm('u', '<?php p_('Enter text to underline');?>');"><span style="text-decoration: underline"><?php p_("U");?></span></button>
<button type="button" id="abr" accesskey="a" title="<?php p_('Abbreviation');?>" onclick="two_parm('ab', '<?php p_("Abbreviation");?>', '<?php p_("Full phrase for abbreviation");?>');"><?php p_('abbr');?></button>
<button type="button" id="acr" accesskey="c" title="<?php p_('Acronym'); ?>" onclick="two_parm('ac', '<?php p_("Acronym/abbreviation");?>', '<?php p_("Full phrase for acronym");?>');"><?php p_('ac'); ?></button>
<button type="button" id="url" accesskey="l" title="<?php p_('Hyperlink'); ?>" onclick="two_parm('url', '<?php p_("Link text");?>', '<?php p_("Link URL");?>');"><span style="color: blue; text-decoration: underline"><?php p_('link');?></span></button>
<button type="button" id="code" accesskey="o" title="<?php p_('Code');?>" onclick="one_parm('code', '<?php p_("Text to mark as code");?>');"><code><?php p_('code');?></code></button>
<button type="button" id="color" accesskey="c" title="<?php p_('Colored text'); ?>" onclick="set_color('<?php p_("Colored text");?>', '<?php p_("Color as a name, hex code, or rgb value");?>');">
<span style="color:red"><?php p_('color');?></span>
</button>&nbsp;<select id="colorselect">
<option value="custom"><?php p_("Custom")?></option>
<option value="red"><?php p_("Red");?></option>
<option value="blue"><?php p_("Blue");?></option>
<option value="yellow"><?php p_("Yellow");?></option>
<option value="green"><?php p_("Green");?></option>
<option value="purple"><?php p_("Purple");?></option>
</select>
<button type="button" id="img" accesskey="m" title="<?php p_('Image'); ?>" onclick="set_img('<?php p_("Enter the image title");?>', '<?php p_("Enter the image URL");?>');">
<?php p_('image');?>
</button>&nbsp;<select id="imgalign">
<option value="center"><?php p_('Center');?></option>
<option value="left"><?php p_('Left');?></option>
<option value="right"><?php p_('Right');?></option>
</select>
<!--
<button type="button" id="qt" accesskey="q" title="<?php p_('Short Quote');?>" onclick="opt_parm('q', '<?php p_('Quoted text');?>', '<?php p_("Optional citation");?>');">"<?php p_("quote");?>"</button>
<button type="button" id="bq" accesskey="k" title="<?php p_('Blockquote'); ?>" onclick="opt_parm('quote', '<?php p_('Block quotation text');?>', '<?php p_("Optional citation");?>');">"<?php p_("bq");?>"</button>
-->
</div>
<?php if (EDITOR_SHOW_INLINE_BOXES) { /* Hide the edit boxes. */ ?>
<p onclick="toggle_show('editboxes', this);"><strong><?php p_("Inline editor boxes");?></strong><a href="#dummy" >(+)</a></p>
<div id="editboxes">
<label for="desc_txt" title="<?php p_('Text to be displayed on page'); ?>"><?php p_('Text'); ?></label>
<input type="text" id="desc_txt" accesskey="t" title="<?php p_('Text to be displayed on page'); ?>" />
<label for="meta_txt" title="<?php p_('Link/image URL or abbreviation title'); ?>"><?php p_('Attribute'); ?></label>
<input type="text" id="meta_txt" accesskey="r" title="<?php p_('Link/image URL or abbreviation title'); ?>" />
</div>
<?php } /* End edit box hiding */ ?>
<?php if (EDITOR_SHOW_SYMBOLS) { /* Hide the math entity buttons. */ ?>
<h4 onclick="toggle_show('mathent', this);"><?php p_("Special symbols");?><a href="#dummy" >(+)</a></h4>
<div id="mathent">
<?php
$math_entities = array();
$math_entities[_("Set Theory")] = array("weierp"=>_("Power set"), "isin"=>_("Set element"), 
	"notin"=>_("Not set element"), "ni"=>_("Contains element"), "sub"=>_("Proper subset"), 
	"sube"=>_("Subset"), "sup"=>_("Proper superset"), "supe"=>_("Superset"),
	"nsub"=>_("Not subset"), "cap"=>_("Intersection"), "cup"=>_("Union"), "empty"=>_("Empty set"));
$math_entities[_("Predicate Logic")] = array("and"=>_("And"), "or"=>_("Or"), "rArr"=>_("Implies"),
	"equiv"=>_("Equivalence"), "there4"=>_("Therefore symbol"), "forall"=>_("Universal quantification"),
	"exist"=>_("Existential quantification"), "mu"=>_("Mu"), "lambda"=>_("Lambda"));
$math_entities[_("Comparison")] = array("cong"=>_("Approximately equal"), "ne"=>_("Not equal"), 
	"le"=>_("Less than or equal to"), "ge"=>_("Greater than or equal to")); 
$math_entities[_("General")] = array("bull"=>_("Bullet"), "sdot"=>_("Dot operator"), 
	"lceil"=>_("Left ceiling"), "rceil"=>_("Right ceiling"), 
	"lfloor"=>_("Left floor"), "rfloor"=>_("Right floor"));

foreach ($math_entities as $type=>$vals) { ?>
<p><strong><?php echo $type;?>:</strong>
<?php	foreach ($vals as $ent=>$desc) { ?>
<input type="button" title="<?php echo $desc;?>" onclick="insertEntity('<?php echo $ent;?>')" value="&<?php echo $ent;?>;" />
<?php	} ?>
</p>
<?php } ?>
</div>
<?php } /* End math entity hiding */ ?>
</div>
