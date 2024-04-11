<script type="application/javascript">
$(document).ready(function () {
    var default_path = '<?php echo $DEFAULT_PATH?>';
    var default_url = '<?php echo $DEFAULT_URL?>';
    var path_sep = '<?php echo $PATH_SEP?>';

    $('#register').on('change', function () {
        var path = default_path + path_sep + $(this).val().replace('/', path_sep) + path_sep;
        $('#register_path').val(path);
        $('#register_url').val(default_url + $(this).val() + '/');
    });
});
</script>
<div id="regbox" class="registerbox slide-down">
    <form method="post" action="<?php echo $ACTION ?>">
        <?php $this->outputCsrfField() ?>
        <div>
            <?php echo $FIELDS['register']->render(
                $PAGE, [
                    'id' => 'register',
                    'label' => _('Blog ID'),
                    'sep_label' => true,
                    'noerror' => true,
                ]
            ) ?>
        </div>
        <div>
            <?php echo $FIELDS['register_path']->render(
                $PAGE, [
                    'id' => 'register_path',
                    'label' => _('Blog path'),
                    'sep_label' => true,
                    'noerror' => true,
                ]
            ) ?>
        <div>
        </div>
            <?php echo $FIELDS['register_url']->render(
                $PAGE, [
                    'id' => 'register_url',
                    'label' => _('Blog URL'),
                    'sep_label' => true,
                    'noerror' => true,
                ]
            ) ?>
        </div>
        <input type="submit" id="register_btn" 
               name="register_btn" 
               value="<?php p_("Register") ?>" />
    </form>
</div>
<?php if ($REGISTER_STATUS): ?>
    <p>
        <?php pf_("Registration Status: %s", "<strong>".$REGISTER_STATUS."</strong>") ?>
    </p>
<?php endif;
