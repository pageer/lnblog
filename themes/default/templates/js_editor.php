<div class="button_row">
<button id="bold" accesskey="b" onclick="one_parm('b');"><?php p_('Bold'); ?></button>
<button id="italic" accesskey="i" onclick="one_parm('i');"><?php p_('Italic'); ?></button>
<button id="underline" accesskey="u" onclick="one_parm('u');"><?php p_('Underline'); ?></button>
<button id="qt" accesskey="q" onclick="opt_parm('q');"><?php p_('Short Quote'); ?></button>
<button id="bq" accesskey="k" onclick="opt_parm('quote');"><?php p_('Blockquote'); ?></button>
</div>
<div class="button_row">
<button id="abr" accesskey="a" onclick="two_parm('ab');"><?php p_('Abbreviation'); ?></button>
<button id="acr" accesskey="c" onclick="two_parm('ac');"><?php p_('Acronym'); ?></button>
<button id="url" accesskey="l" onclick="two_parm('url');"><?php p_('Link'); ?></button>
<button id="img" accesskey="m" onclick="two_parm('img');"><?php p_('Image'); ?></button>
<button id="code" accesskey="o" onclick="one_parm('code');"><?php p_('Code'); ?></button>
</div>
<div class="editorbox">
<div><label for="desc_txt" title="<?php p_('Text to be displayed on page'); ?>"><?php p_('Text'); ?></label>
<input type="text" id="desc_txt" accesskey="t" title="<?php p_('Text to be displayed on page'); ?>" /></div>
<div><label for="meta_txt" title="<?php p_('Link/image URL or abbreviation title'); ?>"><?php p_('Attribute'); ?></label>
<input type="text" id="meta_txt" accesskey="r" title="<?php p_('Link/image URL or abbreviation title'); ?>" /></div>
<hr />
</div>
