<?php
$this->extends('blogcomment_tpl.php');

$this->block(
    'blogcomment.footer', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    $name_class = isset($USER_ID) && !isset($NO_USER_PROFILE) ? 'bloguser' : 'comment name';
    $show_url = $this->showBlock('blogcomment.footer.userurl');
    ?>
    <div class="comment footer">
        <ul>
            <li class="comment date"><?php pf_("Comment posted %s", $DATE) ?></li>
            <li class="<?php echo $name_class?>">
                <?php $this->showBlock('blogcomment.footer.postinfo') ?>
            </li><?php
            if ($show_url): ?>
                 <li class="comment url"><?php echo $show_url?></li>
            <?php endif;
            $this->showBlock('blogcomment.footer.controlbar');
            ?>
        </ul>
    </div>
    <?php
    }
);

$this->block(
    'blogcomment.footer.postinfo', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    $name = $this->showBlock('blogcomment.footer.username');
    $link = $this->showBlock('blogcomment.footer.usernamelink');
    if ($link) {
        $show_name = sprintf('<a href="%s">%s</a>', $link, $name);
    } else {
        $show_name = $name;
    }
    $show_url = $this->showBlock('blogcomment.footer.userurl');
    pf_("By %s", $show_name);
    }
);

$this->block(
    'blogcomment.footer.controlbar', function ($vars) {
    extract($vars, EXTR_OVERWRITE);
    # Show the administrative links.
    if ($SHOW_CONTROLS):
        foreach ($CONTROL_BAR as $item):
            ?>
            <li class="admin"><?php echo $item ?></li>
            <?php
        endforeach;
    endif;
    }
);
