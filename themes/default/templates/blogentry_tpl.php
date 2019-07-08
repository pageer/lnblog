<?php
# Template: blogentry_tpl.php
# Contains the markup and display logic for fuill blog entries, including
# replies.  Most of the tricky stuff here is for conditional display.

$this->block('blogentry.header', function ($vars) {
    extract($vars, EXTR_OVERWRITE); ?>
    <h2 class="header"><?php echo $SUBJECT?></h2>
    <?php
});

$this->block('blogentry.body', function ($vars) {
    extract($vars, EXTR_OVERWRITE); ?>
    <div class="body">
    <?php echo $BODY?>
    </div>
    <?php
});

$this->block('blogentry.footer', function ($vars) {
    extract($vars, EXTR_OVERWRITE); ?>
    <div class="footer">
        <?php
        $this->showBlock('blogentry.enclosure');
        $this->showBlock('blogentry.postdata');
        $this->showBlock('blogentry.controlbar');
        ?>
    </div>
    <?php
});

$this->block('blogentry.enclosure', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    # If there is an enclosure/podcast URL for this entry, this block will display a
    # link to it with the file name, type, and size.
    if (! empty($ENCLOSURE_DATA)):
        $is_podcast =
            strpos($ENCLOSURE_DATA['type'], "audio") !== false ||
            strpos($ENCLOSURE_DATA['type'], "video") !== false;
        $caption = $is_podcast ?
            _("Download this podcast") :
            _("Download attached file");
        ?>
        <p>
            <?php echo $caption ?>:
            <a href="<?php echo $ENCLOSURE_DATA['url'];?>">
                <?php echo basename($ENCLOSURE_DATA['url'])?>
            </a>
            <?php pf_(
                "(Type: %s, size: %d bytes)",
                $ENCLOSURE_DATA['type'],
                $ENCLOSURE_DATA['length']
            );?>
        </p><?php
    endif;
});

$this->block('blogentry.postdata', function ($vars) {
    extract($vars, EXTR_OVERWRITE); ?>
    <ul class="postdata">
        <?php $this->showBlock('blogentry.tags'); ?>
        <li class="blogdate"><?php pf_("Posted %s", $POSTDATE); ?></li>
        <?php
        if (! isset($NO_USER_PROFILE)):
            $name_link = sprintf('<a href="%s">%s</a>', $PROFILE_LINK, $USER_DISPLAY_NAME);
        elseif (isset($USER_EMAIL)):
            $name_link = sprintf('<a href="mailto:%s">%s</a>', $USER_EMAIL, $USER_DISPLAY_NAME);
        else:
            $name_link = $USER_DISPLAY_NAME;
        endif;
        ?>
        <li class="bloguser"><?php pf_("By %s", $name_link); ?></li>
        <?php if (isset($USER_HOMEPAGE)): ?>
            <li class="bloguserurl">
                (<a href="<?php echo $USER_HOMEPAGE; ?>">
                    <?php echo $USER_HOMEPAGE; ?>
                </a>)
            </li>
        <?php endif; ?>
    </ul>
    <?php
});

$this->block('blogentry.tags', function ($vars) {
    extract($vars, EXTR_OVERWRITE);

    if (! empty($TAGS)): ?>
        <li><?php p_("Topics")?>:
        <?php
            $out = array();
            foreach ($TAG_URLS as $tag=>$url):
                $out[] = sprintf('<a href="%s">%s</a>', $url, $tag);
            endforeach;
            echo implode(', ', $out);
        ?>
        </li>
    <?php endif;
});

$this->block('blogentry.controlbar', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if ($SHOW_CONTROLS): ?>
        <ul class="controlbar">
            <li class="ping">
                <a href="<?php echo $PING_LINK; ?>"><?php p_("Send TrackBack Ping"); ?></a>
            </li>
            <li class="upload">
                <a href="<?php echo $UPLOAD_LINK; ?>"><?php p_("Upload file"); ?></a>
            </li>
            <li class="edit">
                <a href="<?php echo $EDIT_LINK; ?>"><?php p_("Edit"); ?></a>
            </li>
            <li class="delete">
                <a href="<?php echo $DELETE_LINK; ?>"><?php p_("Delete"); ?></a>
            </li>
            <li class="replies">
                <a href="<?php echo $MANAGE_REPLY_LINK; ?>"><?php p_("Manage replies"); ?></a>
            </li>
        </ul>
<?php endif;
});

$this->block('blogentry.commentrules', function ($vars) {
    extract($vars, EXTR_OVERWRITE); ?>
    <p class="comment-desc">
        <?php
        if ($ALLOW_COMMENTS):
            pf_('You can reply to this entry by <a href="%s">leaving a comment</a> below.  ',
                $COMMENT_LINK);
        endif;
        if ($ALLOW_TRACKBACKS):
            pf_('You can <a href="%s">send TrackBack pings to this <acronym title="Uniform Resource Locator">URL</acronym></a>.  ',
                $TRACKBACK_LINK);
        endif;
        if ($ALLOW_PINGBACKS):
            pf_('This entry accepts Pingbacks from other blogs.  ');
        endif;
        if ($COMMENT_RSS_ENABLED):
            pf_('You can follow comments on this entry by subscribing to the <a href="%s">RSS feed</a>.',
                $COMMENT_FEED_LINK);
        endif; ?>
    </p><?php
});

$this->block('blogentry.localpingback', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if (! empty($LOCAL_PINGBACKS)): ?>
    <h4><?php p_("Related entries");?></h4>
    <ul>
        <?php foreach ($LOCAL_PINGBACKS as $p): ?>
            <li><?php echo $p->get($SHOW_CONTROLS) ?></li>
        <?php endforeach ?>
    </ul>
    <?php endif;
});

$this->block('blogentry.trackback', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if (! empty($TRACKBACKS)): ?>
    <h3>
        <?php p_("TrackBacks");?>
        <?php if (isset($SHOW_TRACKBACK_LINK)): ?>
            <a href="<?php echo $SHOW_TRACKBACK_LINK; ?>" title="<?php p_("TrackBack page");?>">#</a>
        <?php endif; ?>
    </h3>
    <ul>
        <?php
        foreach ($TRACKBACKS as $p):
            echo $p->get($SHOW_CONTROLS);
        endforeach;
        ?>
    </ul>
    <?php endif;
});

$this->block('blogentry.pingback', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if (! empty($PINGBACKS)): ?>
    <h3><?php p_("Pingbacks");?>
        <?php if (isset($SHOW_PINGBACK_LINK)): ?>
            <a href="<?php echo $SHOW_PINGBACK_LINK; ?>" title="<?php p_("PingBack page");?>">#</a>
        <?php endif; ?>
    </h3>
    <ul>
        <?php
        foreach ($PINGBACKS as $p):
           echo $p->get($SHOW_CONTROLS);
        endforeach;
        ?>
    </ul>
    <?php endif;
});

$this->block('blogentry.comment', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if (! empty($COMMENTS)): ?>
    <h3><?php p_("Comments");?>
    <a href="<?php echo $COMMENT_LINK; ?>" title="<?php p_("Comment page");?>">#</a></h3>
    <ul>
        <?php
        foreach ($COMMENTS as $p):
            echo $p->get($SHOW_CONTROLS);
        endforeach; ?>
    </ul>
    <?php endif;
});

$this->block('main', function ($vars) { ?>
    <div class="blogentry">
        <?php
        $this->showBlock('blogentry.header');
        $this->showBlock('blogentry.body');
        $this->showBlock('blogentry.footer');
        $this->showBlock('blogentry.commentrules');
        $this->showBlock('blogentry.localpingback');
        $this->showBlock('blogentry.trackback');
        $this->showBlock('blogentry.pingback');
        $this->showBlock('blogentry.comment');
        ?>
    </div>
    <?php
});
