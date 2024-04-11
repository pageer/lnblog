<?php

$show_draft = function ($ent) {
    $del_uri = $ent->uri('delete');
    $edit_uri = $ent->uri('editDraft');
    $title = $ent->subject ? $ent->subject : $ent->prettyDate();
    $date = date("Y-m-d", $ent->post_ts);
    $edit_date = date("Y-m-d", $ent->timestamp);
    $pub_date = $ent->getAutoPublishDate();
    ?>
    <li class="draft" 
        data-id="<?php echo $this->escape($ent->entryID())?>"
        data-autopub="<?php echo $this->escape($pub_date)?>"
    >
        <a class="title" href="<?php echo $edit_uri?>"><?php echo $title?></a>
        <a class="delete" href="<?php echo $del_uri?>" title="<?php p_("Delete")?>">
            <img src="<?php echo getlink('cross.png')?>" alt="" />
        </a>
        <a class="updatepub" href="#" title="<?php p_("Update auto-publication status")?>">
            <img src="<?php echo getlink('page_edit.png')?>" alt="" />
        </a>
        <br />
        <?php if ($pub_date): ?>
            <span class="pub date"><?php pf_("Set to auto-publish at %s", $pub_date)?></span>
        <?php endif ?>
        <span class="create date"><?php pf_('Created %s', $date)?></span>
        <?php if ($date != $edit_date): ?>
            <span class="edit date"><?php pf_('Last edit %s', $edit_date)?></span>
        <?php endif ?>
    </li>
    <?php
};

$this->block('drafts.pubmodaljs', function () {
    ?>
    <script type="application/javascript">
        function show_pub_modal() {
            var $row = $(this).closest('.draft');
            var $modal = $('#publication-modal');
            var autopub_set = $row.data('autopub') ? true : false;

            $modal.find('[name="draft"]').val($row.data('id'));
            $modal.find('[name="autopublish"]').prop('checked', autopub_set);
            $modal.find('[name="autopublish_date"]').val($row.data('autopub'));
            if (autopub_set) {
                $modal.find('.pubdate-block').show();
            } else {
                $modal.find('.pubdate-block').hide();
            }

            $modal.dialog('open');

            return false;
        }

        $(function() {
            var submit_options = {
                success: function (response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $modal.dialog("close");
                        window.location.reload();
                    } else {
                        alert('<?php p_('Error updating auto-publish status!')?>');
                    }
                }
            };

            var $modal = $('#publication-modal').dialog({
                autoOpen: false,
                resizable: false,
                height: "auto",
                minWidth: "15em",
                modal: true,
                buttons: [
                    {
                        text: '<?php p_('Submit')?>',
                        click: function() {
                            $modal.find('form').ajaxSubmit(submit_options);
                        }
                    }, {
                        text: '<?php p_('Cancel')?>',
                        click: function() {
                            $(this).dialog("close");
                        }
                    }
                ]
            });

            $('.draft-list').on('click', '.updatepub', show_pub_modal);
            $('#autopublish').on('change', function () {
                var $this = $(this);
                var $pub_block = $('#publication-modal .pubdate-block');
                
                if ($this.prop('checked')) {
                    $pub_block.show();
                } else {
                    $pub_block.hide();
                }
            });

            var allowedTimes = [];
            for (var i = 1; i < 24; i++) {
                allowedTimes.push(i + ':00');
            }
            $('#autopublish_date').datetimepicker(
                {
                format: 'Y-m-d h:i a',
                hours12: true,
                allowTimes: allowedTimes,
                // FIXME: Last update messed up the selector so that the selected time in the UI is
                // decremented when you bring it back up.  Possibly related to time zones
                // and daylight savings time.  In any case, this keeps that from propagating
                // to the textbox and changing the time you JUST set to an hour earlier.
                // The UI is still wrong, but I don't care enough to fix that right now.
                validateOnBlur: false
                }
            );
        });
    </script>
    <?php
});

$this->block('drafts.deletemodaljs', function () {
    ?>
    <script type="application/javascript">
        var delete_entry_submit_options = {};

        function show_delete_modal() {
            var base_uri = '?action=delentry&draft=';
            var $row = $(this).closest('.draft');
            var $modal = $('#delete-modal');
            var title = $row.find('.title').text();
            delete_entry_submit_options = {
                success: function (response) {
                    var result = JSON.parse(response);
                    if (result.success) {
                        $row.remove();
                        $modal.dialog("close");
                    } else {
                        alert('<?php p_('Error deleting draft!')?>');
                    }
                },
                url: base_uri + $row.data('id')
            };

            $modal.find('.delete-confirm-name').text(title);
            $modal.dialog('open');

            return false;
        }

        $(function() {
            var $modal = $('#delete-modal').dialog({
                autoOpen: false,
                resizable: false,
                height: "auto",
                width: "auto",
                modal: true,
                buttons: [
                    {
                        text: '<?php p_('Cancel')?>',
                        click: function() {
                            $(this).dialog("close");
                        }
                    }, {
                        text: '<?php p_('Delete')?>',
                        click: function() {
                            $modal.find('form').ajaxSubmit(delete_entry_submit_options);
                        }
                    }
                ]
            });

            $('.draft-list').on('click', '.delete', show_delete_modal);
        });
    </script>
    <?php
});

$this->block('drafts.pubmodal', function ($vars) {
    extract($vars);
    ?>
    <div id="publication-modal" class="dialog" title="<?php p_('Schedule Publication')?>">
        <form action="?action=queuepub" method="post">
            <?php $this->outputCsrfField() ?>
            <input type="hidden" name="draft" value="" />
            <p>
                <label>
                    <input type="checkbox" id="autopublish" name="autopublish"/>
                    <?php p_('Set to auto-publish')?>
                </label>
            </p>
            <p class="pubdate-block">
                <label>
                    <?php p_('Publication date')?>
                    <br />
                    <input type="datetime" id="autopublish_date" name="autopublish_date"/>
                </label>
            </p>
        </form>
    </div>
    <?php
});

$this->block('drafts.deletemodal', function ($vars) {
    ?>
    <div id="delete-modal" class="dialog" title="<?php p_('Delete draft?')?>">
        <p><?php pf_('Really delete draft "%s"?', '<span class="delete-confirm-name"></span>')?></p>
        <form method="post" action="">
            <?php $this->outputCsrfField() ?>
            <input type="hidden" name="OK" value="OK"/>
            <input type="hidden" name="ajax" value="1" />
        </form>
    </div>
    <?php
});

$this->block('main', function ($vars) use ($show_draft) {
    extract($vars);

    $this->pages->getPage()->addPackage('jquery-ui');
    $this->pages->getPage()->addPackage('jquery-form');
    $this->pages->getPage()->addPackage('jquery-datetime-picker');
    ?>
    <h3><?php p_('Publication queue')?></h3>
    <?php if (empty($PUBLISH_QUEUE)): ?>
        <p><?php p_('No drafts are scheduled for publication')?></p>
    <?php else: ?>
        <ul class="draft-list">
            <?php foreach ($PUBLISH_QUEUE as $draft): ?>
                <?php $show_draft($draft)?>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <h3><?php p_('Draft entries')?></h3>
    <?php if (empty($DRAFTS)): ?>
        <p><?php p_('There are no saved draft entries')?></p>
    <?php else: ?>
        <ul class="draft-list">
            <?php foreach ($DRAFTS as $draft): ?>
                <?php $show_draft($draft)?>
            <?php endforeach ?>
        </ul>
    <?php endif;

    $this->showBlock('drafts.pubmodal');
    $this->showBlock('drafts.deletemodal');
    $this->showBlock('drafts.pubmodaljs');
    $this->showBlock('drafts.deletemodaljs');
});
