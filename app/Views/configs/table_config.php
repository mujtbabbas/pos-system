<?php
/**
 * @var array $dinner_tables
 * @var array $config
 * @var array $employees    // New variable containing all employees for the dropdown
 * @var array $waiter_names // New variable containing the IDs of the selected waiters
 */
?>


<?= form_open('config/saveTables/', ['id' => 'table_config_form', 'class' => 'form-horizontal']) ?>
    <div id="config_wrapper">
        <fieldset id="config_info">

            <div id="required_fields_message"><?= lang('Common.fields_required_message') ?></div>
            <ul id="table_error_message_box" class="error_message_box"></ul>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.dinner_table_enable'), 'dinner_table_enable', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-1">
                    <?= form_checkbox([
                        'name'    => 'dinner_table_enable',
                        'value'   => 'dinner_table_enable',
                        'id'      => 'dinner_table_enable',
                        'checked' => $config['dinner_table_enable'] == 1
                    ]) ?>
                </div>
            </div>

            <div id="dinner_tables">
                <?= view('partial/dinner_tables', ['dinner_tables' => $dinner_tables]) ?>
            </div>

            <div class="form-group form-group-sm">
                <?= form_label(lang('Config.waiter_enable'), 'waiter_enable', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-1">
                    <?= form_checkbox([
                        'name'    => 'waiter_enable',
                        'value'   => 'waiter_enable',
                        'id'      => 'waiter_enable',
                        'checked' => (isset($config['waiter_enable']) && $config['waiter_enable'] == 1)
                    ]) ?>
                </div>
            </div>
            
            <div class="form-group form-group-sm" id="waiter_select_container" style="display: <?= (isset($config['waiter_enable']) && $config['waiter_enable'] == 1) ? 'block' : 'none' ?>;">
                <?= form_label(lang('Config.waiter_names'), 'waiter_names[]', ['class' => 'control-label col-xs-2']) ?>
                <div class="col-xs-8">
                    <?= form_dropdown(
                        ['name' => 'waiter_names[]', 'id' => 'waiter_names', 'class' => 'form-control input-sm', 'multiple' => 'multiple'],
                        $employees, // This array should be ['employee_id' => 'employee_name']
                        $waiter_names // This array contains the IDs of the selected employees
                    ); ?>
                </div>
            </div>

            <?= form_submit([
                'name'  => 'submit_table',
                'id'    => 'submit_table',
                'value' => lang('Common.submit'),
                'class' => 'btn btn-primary btn-sm pull-right'
            ]) ?>

        </fieldset>
    </div>
<?= form_close() ?>

<script type="text/javascript">
    $(document).ready(function() {

        // EXISTING DINNER TABLE JS
        var enable_disable_dinner_table_enable = (function() {
            var dinner_table_enable = $("#dinner_table_enable").is(":checked");
            $("input[name*='dinner_table_']:not(input[name=dinner_table_enable])").prop("disabled", !dinner_table_enable);
            if (dinner_table_enable) {
                $(".add_dinner_table, .remove_dinner_table").show();
            } else {
                $(".add_dinner_table, .remove_dinner_table").hide();
            }
            return arguments.callee;
        })();
        
        // NEW WAITER JS
        var enable_disable_waiter_enable = (function() {
            var waiter_enable = $("#waiter_enable").is(":checked");
            $("#waiter_select_container").toggle(waiter_enable);
            return arguments.callee;
        })();
        
        $("#dinner_table_enable").change(enable_disable_dinner_table_enable);
        $("#waiter_enable").change(enable_disable_waiter_enable);

        var table_count = <?= sizeof($dinner_tables) ?>;

        var hide_show_remove = function() {
            if ($("input[name*='dinner_table_']:enabled").length > 1) {
                $(".remove_dinner_table").show();
            } else {
                $(".remove_dinner_table").hide();
            }
        };

        var add_dinner_table = function() {
            var id = $(this).parent().find('input').attr('id');
            id = id.replace(/.*?_(\d+)$/g, "$1");
            var block = $(this).parent().clone(true);
            var new_block = block.insertAfter($(this).parent());
            var new_block_id = 'dinner_table_' + ++id;
            $(new_block).find('label').html("<?= lang('Config.dinner_table') ?> " + ++table_count).attr('for', new_block_id).attr('class', 'control-label col-xs-2');
            $(new_block).find('input').attr('id', new_block_id).removeAttr('disabled').attr('name', new_block_id).attr('class', 'form-control input-sm').val('');
            hide_show_remove();
        };

        var remove_dinner_table = function() {
            $(this).parent().remove();
            hide_show_remove();
        };

        var init_add_remove_tables = function() {
            $('.add_dinner_table').click(add_dinner_table);
            $('.remove_dinner_table').click(remove_dinner_table);
            
            hide_show_remove();
            enable_disable_dinner_table_enable();
            enable_disable_waiter_enable(); // Initialize waiter state
        };
        init_add_remove_tables();

        // VALIDATION
        $.validator.addMethod('dinner_table', function(value, element) {
            var value_count = 0;
            $("input[name*='dinner_table_']:not(input[name=dinner_table_enable])").each(function() {
                value_count = $(this).val() == value ? value_count + 1 : value_count;
            });
            return value_count < 2;
        }, "<?= lang('Config.dinner_table_duplicate') ?>");
        
        $.validator.addMethod('valid_chars', function(value, element) {
            return value.indexOf('_') === -1;
        }, "<?= lang('Config.dinner_table_invalid_chars') ?>");

        $('#table_config_form').validate($.extend(form_support.handler, {
            submitHandler: function(form) {
                $(form).ajaxSubmit({
                    beforeSerialize: function(arr, $form, options) {
                        // Re-enable disabled fields for submission
                        $("input[name*='dinner_table_']:not(input[name=dinner_table_enable])").prop("disabled", false);
                        return true;
                    },
                    success: function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        });
                        // Reload the dinner tables section to reflect changes
                        $("#dinner_tables").load('<?= "config/dinnerTables" ?>', init_add_remove_tables);
                        
                        // We don't need to reload the waiter section because it's a dropdown
                        // But you might want to call a method if the selection needs to be re-initialized
                    },
                    dataType: 'json'
                });
            },

            errorLabelContainer: "#table_error_message_box",

            rules: {
                <?php
                $i = 0;
                foreach ($dinner_tables as $dinner_table => $table) {
                ?>
                <?= 'dinner_table_' . ++$i ?>: {
                    required: true,
                    dinner_table: true,
                    valid_chars: true
                },
                <?php } ?>
            },

            messages: {
                <?php
                $i = 0;
                foreach ($dinner_tables as $dinner_table => $table) {
                ?>
                <?= 'dinner_table_' . ++$i ?>: "<?= lang('Config.dinner_table_required') ?>",
                <?php } ?>
            }
        }));
    });
</script>