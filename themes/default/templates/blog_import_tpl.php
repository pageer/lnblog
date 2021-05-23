<?php
# File: blog_import_tpl.php
# Display the blog import admin page

$this->block(
    'import.blog.newbox', function ($vars) {
        extract($vars, EXTR_OVERWRITE); ?>
        <div id="newblog-options" class="optionsbox">
            <p><?php p_('Import file as a new blog')?></p>
            <div>
                <input type="text" id="new_blog_id" name="new_blog_id"
                    placeholder="<?php p_('Blog ID')?>" 
                    title="<?php p_('Blog ID')?>"
                    size="20"
                    value="<?php echo $DATA['new_blog_id'] ?? '' ?>" />
            </div>
            <div>
                <input type="text" id="new_blog_path" name="new_blog_path"
                    placeholder="<?php p_('Blog path')?>" 
                    title="<?php p_('Blog path')?>"
                    size="50"
                    value="<?php echo $DATA['new_blog_path'] ?? '' ?>" />
            </div>
            <div>
                <input type="text" id="new_blog_url" name="new_blog_url"
                    placeholder="<?php p_('Blog URL')?>" 
                    title="<?php p_('Blog URL')?>"
                    size="50"
                    value="<?php echo $DATA['new_blog_url'] ?? '' ?>" />
            </div>
        </div>
        <?php
    }
);

$this->block(
    'import.blog.existingbox', function ($vars) {
        extract($vars, EXTR_OVERWRITE); ?>
        <div id="existingblog-options" class="optionsbox">
            <p><?php p_('Import file into an already existing blog')?></p>
            <label>
                <?php p_('Select blog')?>
                <select name="import_to">
                    <?php foreach ($BLOGS as $blog): ?>
                    <option value="<?php echo $blog->blogid?>"
                        <?php if ($blog->blogid == ($DATA['import_to'] ?? '')): ?>
                        selected="selected"
                        <?php endif ?>
                    >
                        <?php echo $blog->name?>
                    </option>
                    <?php endforeach ?>
                </select>
            </label>
        </div>
        <?php
    }
);

$this->block(
    'import.blog.styles', function ($vars) {
        ?>
        <style>
            #import_text {
                height: 20em;
            }

            .error {
                color: red;
            }

            .import-type {
                display: inline-block;
            }

            .import-type.new {
                margin-right: 2em;
            }

            .optionsbox {
                padding: 10px;
                border: thin solid gray;
                border-radius: 5px;
            }

            .optionsbox p {
                margin-top: 0;
            }

            #do-import {
                font-size: 16pt;
                display: block;
                margin: 10px auto;
            }
        </style>
        <?php
    }
);

$this->block(
    'import.blog.script', function ($vars) {
        extract($vars);
        $use_existing =
            empty($DATA['new_blog_id']) && 
            empty($DATA['new_blog_path']) &&
            empty($DATA['new_blog_url']) &&
            !empty($DATA);
        ?>
        <script type="application/javascript">
            $(document).ready(function () {
                var default_path = '<?php echo $DEFAULT_PATH?>';
                var default_url = '<?php echo $DEFAULT_URL?>';
                var path_sep = '<?php echo $PATH_SEP?>';

                $('#new_blog_id').on('change', function () {
                    var path = default_path + path_sep + $(this).val().replace('/', path_sep) + path_sep;
                    $('#new_blog_path').val(path);
                    $('#new_blog_url').val(default_url + $(this).val() + '/');
                });
            });

            // Initialize form
            $('#options-tab').tabs({
                activate: function (event, ui) {
                    var active_tab = $(this).tabs("option", "active");
                    $('#import-option').val(active_tab === 0 ? 'new' : 'existing');
                }
            });

            var is_existing_tab_selected = <?php echo json_encode($use_existing)?>;
            if (is_existing_tab_selected) {
                $('#options-tab').tabs('option', 'active', 1);
            }

            // Adapted from https://web.dev/read-files/
            const dropArea = document.getElementById('import_text');

            function readFile(file) {
              var reader = new FileReader();
              reader.addEventListener('load', (event) => {
                var data = event.target.result;
                var textarea = document.getElementById('import_text');
                textarea.value = data;
              });
              reader.readAsText(file);
            }

            dropArea.addEventListener('dragover', (event) => {
              event.stopPropagation();
              event.preventDefault();
              // Style the drag-and-drop as a "copy file" operation.
              event.dataTransfer.dropEffect = 'copy';
            });

            dropArea.addEventListener('drop', (event) => {
              event.stopPropagation();
              event.preventDefault();
              var fileList = event.dataTransfer.files;
              //console.log(fileList);
              readFile(fileList[0]);
            });
        </script>
        <?php
    }
);

$this->block(
    'main', function ($vars) {
        extract($vars, EXTR_OVERWRITE); 
        $this->showBlock('import.blog.styles');
        ?>
        <?php if (isset($ERROR)): ?>
            <h4><?php p_('Error!')?></h4>
            <p class="error"><?php echo $ERROR?></p>
        <?php endif;?>

        <form method="post">
            <?php $this->outputCsrfField() ?>
            <!-- Note: this depends on the order of the jQuery UI tabs -->
            <input type="hidden" id="import-option" name="import_option" value="new" />
            <div id="options-tab">
                <ul>
                    <li><a href="#newblog-options"><?php p_('Import as new blog')?></a></li>
                    <li><a href="#existingblog-options"><?php p_('Import into existing blog')?></a></li>
                </ul>
                <?php
                $this->showBlock('import.blog.newbox');
                $this->showBlock('import.blog.existingbox');
                ?>
            </div>
            <div>
                <label>
                <input type="checkbox" name="import_users" <?php
                    echo (!isset($DATA) || !empty($DATA['import_users'])) ? 'checked' : '';
                ?> />
                    <?php p_('Import authors as new users')?>
                </label>
                <p>
                <?php p_('The import will try to preserve author information.  If this is checked, it will create new user acccunts for any usernames that do not already exist in LnBlog.  Otherwise, any non-existent users will be converted to the owner.')
    ?>
                </p>
            </div>
            <div>
                <h4><?php p_('Set import text')?></h4>
                <p><?php p_('Drag-and-drop your import file onto the box below.  You can also copy-and-paste the contents of theimport file.')?></p>
                <textarea id="import_text" name="import_text" 
                          placeholder="<?php p_('Drag-and-drop or paste contents of import file...')?>"
                ><?php echo $DATA['import_text'] ?? '' ?></textarea>
            </div>

            <input type="submit" id="do-import" value="<?php p_('Start Import')?>" />
        </form>

        <?php
        $this->showBlock('import.blog.script');
    }
);
