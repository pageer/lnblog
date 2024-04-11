<?php if (!empty($INCLUDE_SCRIPT_TAGS)): ?>
<script type="text/javascript">
<?php endif?>
$(function () {
    $('.rss-ajax-link').off('click').on('click', function () {
        var $self = $(this);
        var result = window.confirm($self.text() + '?');

        if (!result) {
            return false;
        }

        $.ajax($self.attr('href'), {method: 'POST'})
            .done(function () {
                window.alert('<?php p_('Done')?>');
            })
            .fail(function () {
                window.alert('<?php p_('Request failed!')?>');
            });

        return false;
    });
});
<?php if (!empty($INCLUDE_SCRIPT_TAGS)): ?>
</script>
<?php endif;
