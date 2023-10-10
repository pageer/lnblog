<?php

class LBCodeEditor extends Plugin
{
    public function __construct() {
        $this->plugin_desc = _("Include editor controls for editing LBCode markup.");
        $this->plugin_version = "0.1.0";
        parent::__construct();
    }

    public function show_editor() {
        ?>
<div id="lbcode_editor" style="display: none">
    <strong><a href="<?php echo DOCUMENTATION_URL?>#File:docs/lbcode.txt" target="_blank">
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
        </button>&nbsp;
        <select id="colorselect">
            <option value="custom"><?php p_("Custom")?></option>
            <option value="red"><?php p_("Red");?></option>
            <option value="blue"><?php p_("Blue");?></option>
            <option value="yellow"><?php p_("Yellow");?></option>
            <option value="green"><?php p_("Green");?></option>
            <option value="purple"><?php p_("Purple");?></option>
        </select>
        <button type="button" id="img" accesskey="m" title="<?php p_('Image'); ?>" onclick="lbcode_editor.set_img('<?php p_("Enter the image title");?>', '<?php p_("Enter the image URL");?>');">
        <?php p_('image');?>
        </button>&nbsp;
        <select id="imgalign">
            <option value="center"><?php p_('Center');?></option>
            <option value="left"><?php p_('Left');?></option>
            <option value="right"><?php p_('Right');?></option>
        </select>
    </div>
</div>
<script type="text/javascript">
function LBCodeEditor() {

    this.short_forms = [];
    this.abbrev_phrases = [];
    this.acronym_phrases = [];

    this.add_acr = function (shrt, lng) {
        var len = this.short_forms.push(shrt);
        this.acronym_phrases[len-1] = lng;
    };

    this.add_abbr = function (shrt, lng) {
        var len = this.short_forms.push(shrt);
        this.abbrev_phrases[len-1] = lng;
    };

    this.get_abbr = function (shrt) {
        var i=0;
        for (i=0; i < this.short_forms.length; i++) {
            if (this.short_forms[i] == shrt) {
                if (this.abbrev_phrases[i]) {
                    return this.abbrev_phrases[i];
                } else {
                    return '';
                }
            }
        }
        return '';
    };

    this.get_acr = function (shrt) {
        var i=0;
        for (i=0; i < this.short_forms.length; i++) {
            if (this.short_forms[i] == shrt) {
                if (this.acronym_phrases[i]) {
                    return this.acronym_phrases[i];
                } else {
                    return '';
                }
            }
        }
        return '';
    };

    this.one_parm = function (type, prompt_text) {
        /*globals lnblog */
        var text_str;
        if (lnblog.getSelection($("#body").get(0))) {
            text_str = lnblog.getSelection($("#body").get(0));
        } else {
            text_str = window.prompt(prompt_text);
        }
        if (! text_str) {
            return false;
        }
        lnblog.insertAtCursor(
            $("#body").get(0),
            '[' + type + ']' + text_str + '[/' + type + ']'
        );
        return false;
    };

    this.two_parm = function (type, prompt_text, meta_text, defaults) {

        var desc_str,  meta_str,
            desc_default = (defaults || {}).desc || '',
            meta_default = (defaults || {}).meta || '';

        if (lnblog.getSelection($("#body").get(0))) {
            desc_str = lnblog.getSelection($("#body").get(0));
        } else {
            desc_str = window.prompt(prompt_text, desc_default);
        }

        if (! desc_str) {
            return false;
        }

        if (type == 'ab' || type == 'ac') {

            if (type == 'ab') {
                meta_str = this.get_abbr(desc_str);
            } else {
                meta_str = this.get_acr(desc_str);
            }

            if (! meta_str) {
                meta_str = window.prompt(meta_text, meta_default);
                if (! meta_str) {
                    return false;
                }
                if (type == 'ab') {
                    this.add_abbr(desc_str, meta_str);
                } else {
                    this.add_acr(desc_str, meta_str);
                }
            }

        } else {
            meta_str = window.prompt(meta_text, meta_default);
        }

        if (! meta_str) {
            return false;
        }

        lnblog.insertAtCursor(
            document.getElementById("body"),
            '[' + type + '=' + meta_str + ']' +    desc_str + '[/' + type + ']'
        );

        return false;
    };

    this.opt_parm = function (type, prompt_text, meta_text, defaults) {

        var desc_str, meta_str, data_str,
            prompt_default = (defaults || {}).prompt || '',
            meta_default = (defaults || {}).meta || '';

        if (lnblog.getSelection($("#body").get(0))) {
            desc_str = lnblog.getSelection($("#body").get(0));
        } else {
            desc_str = window.prompt(prompt_text, prompt_default);
        }

        if (! desc_str) {
            return false;
        }

        meta_str = window.prompt(meta_text, meta_default);

        if (! meta_str) {
            meta_str = '';
        }

        data_str = '[' + type;
        if (meta_str === '') {
            data_str = '[' + type + ']' + desc_str + '[/' + type + ']';
        } else {
            data_str = '[' + type + '=' + meta_str + ']' + desc_str + '[/' + type + ']';
        }

        lnblog.insertAtCursor(
            $("#body").get(0), data_str
        );
        return false;
    };

    this.set_color = function (text_prompt, attr_prompt) {

        var text_str, color_str, data_str;

        if (lnblog.getSelection($("#body").get(0))) {
            text_str = lnblog.getSelection($("#body").get(0));
        } else {
            text_str = window.prompt(text_prompt);
        }

        if (! text_str) {
            return false;
        }

        var color = document.getElementById('colorselect');
        if (color.value == 'custom') {
            color_str = window.prompt(attr_prompt);
        } else {
            color_str = color.value;
        }

        if (!color_str) {
            return false;
        }

        data_str = '[color='+color_str+']'+text_str+'[/color]';

        lnblog.insertAtCursor(document.getElementById("body"), data_str);
        return false;
    };

    this.set_img = function (text_prompt, attr_prompt) {

        var text_str, url_str, tag_str, data_str;

        if (lnblog.getSelection(document.getElementById("body"))) {
            text_str = lnblog.getSelection(document.getElementById("body"));
        } else {
            text_str = window.prompt(text_prompt);
        }

        if (! text_str) {
            return false;
        }

        url_str = window.prompt(attr_prompt);
        if (! url_str) {
            return false;
        }

        var alignbox = document.getElementById('imgalign');
        if (alignbox.value == 'center') {
            tag_str = 'img';
        } else {
            tag_str = 'img-'+alignbox.value;
        }

        data_str = '['+tag_str+'='+url_str+']'+text_str+'[/'+tag_str+']';

        lnblog.insertAtCursor(document.getElementById("body"), data_str);
        return false;
    };

    this.toggle_show = function (ctrl, caller) {
        var cnt = document.getElementById(ctrl);
        var block_hidden = false;

        if (! cnt.style.display && caller.innerHTML == "(+)") {
            block_hidden = true;
        }

        if (cnt.style.display == "none" ||  block_hidden) {
            cnt.style.display = "inline";
            caller.innerHTML = "(-)";
        } else {
            cnt.style.display = "none";
            caller.innerHTML = "(+)";
        }

        return false;
    };

    function toggle_lbcode_editor(ev) {
        var $lbcode = $('#lbcode_editor');
        var $dropdown = $('#input_mode');
        $lbcode.toggle($dropdown.val() == <?php echo MARKUP_BBCODE?>)

        return true;
    }

    // Toggle the LBCode editor on or off depending on the markup setting.
    $('input_mode').on('change', toggle_lbcode_editor);
    toggle_lbcode_editor();
}

var lbcode_editor;
$(function() {
    lbcode_editor = new LBCodeEditor();
});
</script>
        <?php
    }
}

$plug = new LBCodeEditor();
$plug->registerEventHandler("posteditor", "ShowControls", "show_editor");
