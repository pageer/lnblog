<style>
    .ui-sortable-helper {
        display: table;
    }
    .move-handle {
        cursor: move;
    }
    .hide {
        display: none;
    }
</style>
<h2><?php p_("Plugin Loading Configuration"); ?></h2>
<?php if (isset($UPDATE_MESSAGE)) { ?>
<p><strong><?php echo $UPDATE_MESSAGE; ?></strong></p>
<?php } ?>
<form method="post" action="<?php echo current_file();?>">
<?php $this->outputCsrfField() ?>
<table id="plugin-loading-list">
    <thead>
        <tr>
            <th><?php p_("Plugin File")?></th>
            <th class="load-order"><?php p_("Load Order")?></th>
            <th><?php p_("Enabled")?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($PLUGIN_LIST as $file=>$val): ?>
    <tr>
        <td>
            <span class="move-handle hide"
                title="<?php p_('Drag and drop to change load order')?>">
                &#11137;
            </span>
            <?php echo $val["file"];?>
        </td>
        <td class="load-order">
            <input type="text" size="11" name="<?php echo $file."_ord"?>"
                id="<?php echo $file."_ord"?>"
                class="order-field"
                value="<?php echo $val["order"]?>" />
        </td>
        <td>
            <input type="checkbox" name="<?php echo $file."_en"?>"
                id="<?php echo $file."_en";?>"
                <?php if ($val["enabled"]) { ?>checked="checked"<?php } ?> />
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div>
    <input type="submit" value="<?php p_("Submit");?>" />
    <input type="reset" value="<?php p_("Reset");?>" />
</div>
</form>
<script type="application/javascript">
    $(document).ready(function() {
        $('#plugin-loading-list tbody').sortable({
            update: function(event, ui) {
                var $list = $(this).closest('tbody');
                $list.find('tr').each(function (index) {
                    var $item = $(this);
                    $item.find('.order-field').val(index + 1);
                });
            }
        });
        $('#plugin-loading-list tbody').disableSelection();
        $('#plugin-loading-list .load-order').hide();
        $('#plugin-loading-list .move-handle').removeClass('hide');
    });
</script>
