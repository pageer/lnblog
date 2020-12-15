<?php
# Template: blogcomment_tpl.php
# Template for the full markup to a comment, including the subject, body,
# metadata (name, e-mail, and so forth).  This also includes the administrative
# links for managing the comment.
#
# This is included whenever a comment is displayed on the page.
?>

<?php
$this->block(
    'blogcomment.subject', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    if (isset($SUBJECT)): ?>
        <h3 class="comment header">
            <a id="<?php echo $ANCHOR ?>" href="#<?php echo $ANCHOR ?>">
                <?php echo $SUBJECT ?>
            </a>
        </h3>
    <?php else: ?>
        <a id="<?php echo $ANCHOR ?>" href="#<?php echo $ANCHOR ?>"></a>
    <?php endif;
    }
);

$this->block(
    'blogcomment.content', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    ?>
        <div class="comment data">
            <?php echo $BODY ?>
        </div>
    <?php
    }
);

$this->block(
    'blogcomment.footer', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    ?>
    <ul class="comment footer">
        <li><?php $this->showBlock('blogcomment.footer.postinfo'); ?></li>
        <?php $this->showBlock('blogcomment.footer.controlbar'); ?>
    </ul>
    <?php
    }
);

$this->block(
    'blogcomment.footer.postinfo', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    $name = $this->showBlock('blogcomment.footer.username');
    $link = $this->showBlock('blogcomment.footer.usernamelink');
    if (isset($USER_ID) && $link) {
        $show_name = sprintf('<a href="%s"><strong>%s</strong></a>', $link, $name);
    } elseif (isset($USER_ID)) {
        $show_name = sprintf('<strong>%s</strong>', $name);
    } elseif ($link) {
        $show_name = sprintf('<a href="%s">%s</a>', $link, $name);
    } else {
        $show_name = $name;
    }
    $show_url = $this->showBlock('blogcomment.footer.userurl');
    pf_("Posted by %s %s at %s", $show_name, $show_url, $DATE);
    }
);

$this->block(
    'blogcomment.footer.username', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    return isset($USER_ID) ? $USER_DISPLAY_NAME : $NAME;
    }
);

$this->block(
    'blogcomment.footer.usernamelink', function ($vars) {
    extract($vars, EXTR_OVERWRITE);

    $link = '';
    if (isset($USER_ID)) {
        if (isset($NO_USER_PROFILE)) {
            $link = isset($USER_EMAIL) ? ("mailto:" . $USER_EMAIL) : '';
        } else {
            $link = $PROFILE_LINK;
        }
    } else {
        if ($EMAIL && $SHOW_MAIL) {
            $link = "mailto:$EMAIL";
        }
    }
    return $link;
    }
);

$this->block(
    'blogcomment.footer.userurl', function ($vars) {
    extract($vars, EXTR_OVERWRITE);

    $show_url = '';
    if (isset($USER_ID) && isset($USER_HOMEPAGE)) {
        $show_url = "(<a href=\"$USER_HOMEPAGE\">$USER_HOMEPAGE</a>)";
    } elseif ($URL) {
        $show_url = "(<a href=\"$URL\">$URL</a>)";
    }
    return $show_url;
    }
);

$this->block(
    'blogcomment.footer.controlbar', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    # Show the administrative links.
    if ($SHOW_CONTROLS): ?>
        <li>
            <ul class="controlbar">
                <?php foreach ($CONTROL_BAR as $item): ?>
                    <li><?php echo $item ?></li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php
    endif;
    }
);

$this->block(
    'main', function ($vars) {
    $this->showBlock('blogcomment.subject');
    $this->showBlock('blogcomment.content');
    $this->showBlock('blogcomment.footer');
    }
);
