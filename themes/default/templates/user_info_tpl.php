<?php
$name = empty($USER_NAME) ? $USER_ID : $USER_NAME;
?>
<h3><?php pf_('Profile for %s', $name)?></h3>
<ul>
    <?php if (! empty($USER_EMAIL) &&  empty($CUSTOM_VALUES['contacturl'])): ?>
        <li><?php p_('E-mail')?>: <a href="mailto:<?php echo $USER_EMAIL?>"><?php echo $USER_EMAIL?></a></li>
    <?php endif ?>
    <?php if (! empty($USER_HOMEPAGE)): ?>
        <li><?php p_('Homepage')?>: <a href="<?php echo $USER_HOMEPAGE?>"><?php echo $USER_HOMEPAGE?></a></li>
    <?php endif ?>
    <?php foreach ($CUSTOM_FIELDS as $key=>$val):
        if (! empty($CUSTOM_VALUES[$key])): ?>
            <li><?php echo $val; ?>: <?php echo $CUSTOM_VALUES[$key];?></li><?php
        endif;
    endforeach ?>
</ul>
