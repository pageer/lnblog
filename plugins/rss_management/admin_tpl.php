<?php
/**
 * @var RssManagement $this
 */
?>
<div id="rss-feed-formats">
    <h4><?php p_('Feed formats')?></h4>
    <?php
    $this->showField('use_rss1');
    $this->showField('use_rss2');
    $this->showField('use_atom');
    ?>
</div>

<div id="rss-feed-types">
    <h4><?php p_('Feed types')?></h4>
    <?php
    $this->showField('use_blog_feeds');
    $this->showField('use_tag_feeds');
    $this->showField('use_comment_feeds');
    ?>
</div>

<div id="fss-display-options">
    <h4><?php p_('Display options')?></h4>
    <?php
    $this->showField('max_entries');
    $this->showField('show_header_links');
    $this->showField('show_sidebar_links');
    $this->showField('sidebar_section_header');
    $this->showField('sidebar_use_icons');
    $this->showField('show_comment_links');
    $this->showField('show_tag_links');
    $this->showField('use_external_feed');
    ?>
    <div id="rss-external-feed-options">
        <?php
        $this->showField('feed_url');
        $this->showField('feed_format');
        $this->showField('feed_description');
        $this->showField('feed_widget');
        ?>
    </div>
</div>

<?php if (isset($BLOG) && $BLOG->isBlog()): ?>
<div id="rss-operations">
    <h4><?php p_('Operations')?></h4>
    <ul>
        <li>
            <a href="?action=plugin&plugin=rss_management&do=purgeblog" class="rss-ajax-link">
                <?php p_('Purge blog feeds')?>
            </a>
        </li>
        <li>
            <a href="?action=plugin&plugin=rss_management&do=purgeallcomment" class="rss-ajax-link">
                <?php p_('Purge all comment feeds')?>
            </a>
        </li>
        <li>
            <a href="?action=plugin&plugin=rss_management&do=regenblog" class="rss-ajax-link">
                <?php p_('Regenerate blog feeds')?>
            </a>
        </li>
        <li>
            <a href="?action=plugin&plugin=rss_management&do=regenallcomment" class="rss-ajax-link">
                <?php p_('Regenerate all comment feeds')?>
            </a>
        </li>
    </ul>
</div>
<?php endif; ?>

<script type="text/javascript">
    $(function() {
        var generate_feed_selectors = '#use_rss1, #use_rss2, #use_atom';
        var will_generate_feeds = function() {
            return $('#use_rss1').is(':checked')
                || $('#use_rss2').is(':checked')
                || $('#use_atom').is(':checked');
        };

        var show_feed_options = function() {
            return will_generate_feeds() || $('#use_external_feed').is(':checked');
        };

        var toggle_inputs = function(selector, enabled) {
            var $inputs = $(selector).find('input, textarea');
            $inputs.prop('disabled', !enabled);
            $inputs.change();
            $(selector).toggle(enabled);
        };

        // Pass a jQuery node or a boolean.  If a node, changed if 
        // enables if control is checked.  If boolean, indicates
        // directly if the control should be enabled.
        var toggle_single_input = function($source, target, hide) {
            var $ctrl = $(target);
            var disable = typeof $source === 'boolean' ? 
                !$source :
                (!$source.is(':checked') || $source.is(':disabled'));
            $ctrl.prop('disabled', disable);
            if (hide) {
                $ctrl.toggle(!disable);
            }
        }

        $(generate_feed_selectors).on('change', function() {
            if (will_generate_feeds()) {
                toggle_inputs('#rss-feed-types', true);
            } else {
                toggle_inputs('#rss-feed-types', false);
            }
        });

        $('#use_comment_feeds').on('change', function() {
            toggle_single_input($(this), '#show_comment_links');
        });
        $('#use_tag_feeds').on('change', function() {
            toggle_single_input($(this), '#show_tag_links');
        });
        $('#show_sidebar_links').on('change', function() {
            toggle_single_input($(this), '#sidebar_section_header', true);
        });
        $('#use_external_feed').on('change', function() {
            var enable = $(this).is(':checked') && !$(this).is(':disabled');
            toggle_inputs('#rss-external-feed-options', enable);
        });
        $(generate_feed_selectors + ', #use_external_feed').on('change', function() {
            var show_options = show_feed_options();
            toggle_single_input(show_options, '#show_header_links')
            toggle_single_input(show_options, '#show_sidebar_links')
            toggle_single_input(show_options, '#sidebar_use_icons')
        });

        $('#plugin_config').on('submit', function() {
            var use_external_feed = $('#use_external_feed').is(':checked');
            var external_feed_url = $('#feed_url').val().trim();
            console.log(use_external_feed, feed_url.length);
            if (use_external_feed && external_feed_url.length == 0) {
                alert("<?php p_('External feed URL not set')?>");
                return false;
            }

            return true;
        });

        $('input').change();
    });

    <?php include __DIR__ . DIRECTORY_SEPARATOR . 'shared_js_tpl.php';?>
</script>
