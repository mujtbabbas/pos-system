<?php
/**
 * @var string $controller_name
 * @var array $modes
 * @var array $mode
 * @var array $empty_tables
 * @var array $selected_table
 * @var array $stock_locations
 * @var array $stock_location
 * @var array $cart
 * @var bool $items_module_allowed
 * @var bool $change_price
 * @var int $customer_id
 * @var int $customer_discount_type
 * @var float $customer_discount
 * @var float $customer_total
 * @var string $customer_required
 * @var float|int $item_count
 * @var float|int $total_units
 * @var float $subtotal
 * @var array $taxes
 * @var float $total
 * @var float $payments_total
 * @var float $amount_due
 * @var bool $payments_cover_total
 * @var array $payment_options
 * @var array $selected_payment_type
 * @var bool $pos_mode
 * @var array $payments
 * @var string $mode_label
 * @var string $comment
 * @var bool $print_after_sale
 * @var bool $email_receipt
 * @var bool $price_work_orders
 * @var string $invoice_number
 * @var int $cash_mode
 * @var float $non_cash_total
 * @var float $cash_amount_due
 * @var array $config
 */

use App\Models\Employee;
?>

<script>
    const allItems = <?= json_encode($all_items) ?>;
</script>
<!-- CSS Styles -->
<!-- CSS Styles -->
<!-- CSS Styles -->
<style>
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 10px;
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: #f0f2f5;
}

.items-section {
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.badge-button {
    text-align: center;
    padding: 15px 10px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    user-select: none;
    font-size: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.badge-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.badge-button:active {
    transform: translateY(0);
    box-shadow: none;
}

.category-button {
    background-color: #28a745;
    color: white;
}

.category-button.active {
    background-color: #000;
    color: white;
}

.item-button {
    background-color: #dc3545;
    color: white;
}

.item-button.active {
    background-color: #000;
    color: white;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); /* responsive */
    gap: 10px;
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: #f0f2f5;
}

.items-section {
    margin-top: 10px;
    padding: 10px;
    border-radius: 8px;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
}

.badge-button {
    text-align: center;
    padding: 15px 10px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    user-select: none;
    font-size: 14px;
    white-space: normal; /* allow text wrapping */
    word-wrap: break-word; /* break long words if needed */
    overflow: visible; /* show all content */
}

/* Hover and active states */
.badge-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.badge-button:active {
    transform: translateY(0);
    box-shadow: none;
}

/* Category buttons */
.category-button {
    background-color: #008080;
    color: white;
}

.category-button.active {
    background-color: #000;
    color: white;
}

/* Item buttons */
.item-button {
    background-color: #66B2B2;
    color: white;
}

.item-button.active {
    background-color: #000;
    color: white;
}
.add-to-bill-button {
    background-color: green;
    color: white;
    padding: 15px 25px; /* increased horizontal padding */
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    user-select: none;
    font-size: 14px;
    white-space: normal;
    word-wrap: break-word;
    overflow: visible;
    border-radius: 0; /* no rounded corners */
    margin-right: 15px; /* little space from container end */
}

.order-actions {
    display: flex;
    flex-wrap: wrap;       /* wrap on small screens */
    align-items: center;   /* vertical align */
    gap: 10px;             /* space between checkbox & button */
    margin-bottom: 10px;   /* spacing below */
}

.queue-label {
    display: flex;
    align-items: center;
    font-size: 14px;
}

.queue-label input {
    margin-right: 5px;
}
#orderModal .modal-dialog {
    max-width: 100% !important;
    margin: 0 auto;
}

#orderModal .modal-content {
    height: 100vh; /* optional if you want full screen height */
    border-radius: 0; /* removes rounded corners */
}
#buttons_sale {
    display: flex;
    flex-wrap: wrap; /* allow wrapping on small screens */
    align-items: center;
    gap: 8px;
}

#buttons_sale .order-print-group {
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Adjust Print checkbox styling */
#print_order_queue_checkbox {
    width: 14px;
    height: 14px;
    vertical-align: middle;
}

.print-label {
    display: flex;
    align-items: center;
    gap: 2px;
    font-size: 0.85em;
    margin: 0;
}

/* Responsive: stack order-print below suspend & cancel on small screens */
@media (max-width: 768px) {
    #buttons_sale .order-print-group {
        width: 100%;
        margin-top: 8px;
        justify-content: flex-start; /* left-align */
    }
}


</style>

<?= view('partial/header') ?>

<?php
if (isset($error)) {
    echo '<div class="alert alert-dismissible alert-danger">' . esc($error) . '</div>';
}

if (!empty($warning)) {
    echo '<div class="alert alert-dismissible alert-warning">' . esc($warning) . '</div>';
}

if (isset($success)) {
    echo '<div class="alert alert-dismissible alert-success">' . esc($success) . '</div>';
}
?>

<div id="register_wrapper">

   
<?= form_open("$controller_name/changeMode", ['id' => 'mode_form', 'class' => 'form-horizontal panel panel-default']) ?>
    <div class="panel-body form-group">
        <ul>
            <li class="pull-left first_li">
                <label class="control-label"><?= lang(ucfirst($controller_name) . '.mode') ?></label>
            </li>
            <li class="pull-left">
                <?= form_dropdown('mode', $modes, $mode, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
            </li>
            <?php if ($config['dinner_table_enable']) { ?>
                <li class="pull-left first_li">
                    <label class="control-label"><?= lang(ucfirst($controller_name) . '.table') ?></label>
                </li>
                <li class="pull-left">
                    <?= form_dropdown('dinner_table', $empty_tables, $selected_table, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
                </li>
                
            <?php } ?>
           <?php if (isset($config['waiter_enable']) && $config['waiter_enable']) { ?>
    <li class="pull-left first_li">
        <label class="control-label">Waiter</label>
    </li>
    <li class="pull-left">
        <?= form_dropdown('waiter', $employees_list, $selected_waiter_id, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
    </li>
<?php } ?>
    
            <?php if (count($stock_locations) > 1) { ?>
                <li class="pull-left">
                    <label class="control-label"><?= lang(ucfirst($controller_name) . '.stock_location') ?></label>
                </li>
                <li class="pull-left">
                    <?= form_dropdown('stock_location', $stock_locations, $stock_location, ['onchange' => "$('#mode_form').submit();", 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
                </li>
            <?php } ?>

            <li class="pull-right">
                <button class="btn btn-default btn-sm modal-dlg" id="show_suspended_sales_button" data-href="<?= esc("$controller_name/suspended") ?>"
                    title="<?= lang(ucfirst($controller_name) . '.suspended_sales') ?>">
                    <span class="glyphicon glyphicon-align-justify">&nbsp;</span><?= lang(ucfirst($controller_name) . '.suspended_sales') ?>
                </button>
            </li>

            <?php
            $employee = model(Employee::class);
            if ($employee->has_grant('reports_sales', session('person_id'))) {
            ?>
                <li class="pull-right">
                    <?= anchor(
                        "$controller_name/manage",
                        '<span class="glyphicon glyphicon-list-alt">&nbsp;</span>' . lang(ucfirst($controller_name) . '.takings'),
                        ['class' => 'btn btn-primary btn-sm', 'id' => 'sales_takings_button', 'title' => lang(ucfirst($controller_name) . '.takings')]
                    ) ?>
                </li>
            <?php } ?>
        </ul>
    </div>
<?= form_close() ?>
    <?php $tabindex = 0; ?>
<?= form_open("$controller_name/add", ['id' => 'add_item_form', 'class' => 'form-horizontal panel panel-default']) ?>
<div class="panel-body form-group">
    <ul>
        <li class="pull-left first_li">
            <label for="item" class="control-label"><?= lang(ucfirst($controller_name) . '.find_or_scan_item_or_receipt') ?></label>
        </li>
        <li class="pull-left">
            <!-- Search bar -->
            <?= form_input(['name' => 'item', 'id' => 'item', 'class' => 'form-control input-sm', 'size' => '50']) ?>

            


        </li>
    </ul>
</div>
<div class="category-grid" id="categoryContainer">
    </div>

<div id="itemsContainer">
    </div>

<input type="hidden" id="allItemsJson" value='<?= $all_items_json ?>'>
<?= form_close() ?>
<!-- Replace your existing dropdown HTML with this -->


    <!-- Sale Items List -->

    <table class="sales_table_100" id="register">
        <thead>
            <tr>
                <th style="width: 5%;"><?= lang('Common.delete') ?></th>
                <th style="width: 15%;"><?= lang(ucfirst($controller_name) . '.item_number') ?></th>
                <th style="width: 30%;"><?= lang(ucfirst($controller_name) . '.item_name') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.price') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.quantity') ?></th>
                <th style="width: 15%;"><?= lang(ucfirst($controller_name) . '.discount') ?></th>
                <th style="width: 10%;"><?= lang(ucfirst($controller_name) . '.total') ?></th>
                <th style="width: 5%;"><?= lang(ucfirst($controller_name) . '.update') ?></th>
            </tr>
        </thead>

        <tbody id="cart_contents">
            <?php if (count($cart) == 0) { ?>
                <tr>
                    <td colspan="8">
                        <div class="alert alert-dismissible alert-info"><?= lang(ucfirst($controller_name) . '.no_items_in_cart') ?></div>
                    </td>
                </tr>
            <?php
            } else {
                foreach (array_reverse($cart, true) as $line => $item) {
            ?>
                    <?= form_open("$controller_name/editItem/$line", ['class' => 'form-horizontal', 'id' => "cart_$line"]) ?>
                        <tr>
                            <td>
                                <?php
                                echo anchor("$controller_name/deleteItem/$line", '<span class="glyphicon glyphicon-trash"></span>');
                                echo form_hidden('location', (string)$item['item_location']);
                                echo form_input(['type' => 'hidden', 'name' => 'item_id', 'value' => $item['item_id']]);
                                ?>
                            </td>
                            <?php if ($item['item_type'] == ITEM_TEMP) { ?>
                                <td><?= form_input(['name' => 'item_number', 'id' => 'item_number', 'class' => 'form-control input-sm', 'value' => $item['item_number'], 'tabindex' => ++$tabindex]) ?></td>
                                <td style="align: center;">
                                    <?= form_input(['name' => 'name', 'id' => 'name', 'class' => 'form-control input-sm', 'value' => $item['name'], 'tabindex' => ++$tabindex]) ?>
                                </td>
                            <?php } else { ?>
                                <td><?= esc($item['item_number']) ?></td>
                                <td style="align: center;">
                                    <?= esc($item['name']) . ' ' . implode(' ', [$item['attribute_values'], $item['attribute_dtvalues']]) ?>
                                    <br>
                                    <?php if ($item['stock_type'] == '0'): echo '[' . to_quantity_decimals($item['in_stock']) . ' in ' . $item['stock_name'] . ']';
                                    endif; ?>
                                </td>
                            <?php } ?>

                            <td>
                                <?php
                                if ($items_module_allowed && $change_price) {
                                    echo form_input(['name' => 'price', 'class' => 'form-control input-sm', 'value' => to_currency_no_money($item['price']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']);
                                } else {
                                    echo to_currency($item['price']);
                                    echo form_hidden('price', to_currency_no_money($item['price']));
                                }
                                ?>
                            </td>

                            <td>
                                <?php
                                if ($item['is_serialized']) {
                                    echo to_quantity_decimals($item['quantity']);
                                    echo form_hidden('quantity', $item['quantity']);
                                } else {
                                    echo form_input(['name' => 'quantity', 'class' => 'form-control input-sm', 'value' => to_quantity_decimals($item['quantity']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']);
                                }
                                ?>
                            </td>

                            <td>
                                <div class="input-group">
                                    <?= form_input(['name' => 'discount', 'class' => 'form-control input-sm', 'value' => $item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']) ?>
                                    <span class="input-group-btn">
                                        <?= form_checkbox(['id' => 'discount_toggle', 'name' => 'discount_toggle', 'value' => 1, 'data-toggle' => "toggle", 'data-size' => 'small', 'data-onstyle' => 'success', 'data-on' => '<b>' . $config['currency_symbol'] . '</b>', 'data-off' => '<b>%</b>', 'data-line' => $line, 'checked' => $item['discount_type'] == 1]) ?>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <?php
                                if ($item['item_type'] == ITEM_AMOUNT_ENTRY) {    // TODO: === ?
                                    echo form_input(['name' => 'discounted_total', 'class' => 'form-control input-sm', 'value' => to_currency_no_money($item['discounted_total']), 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']);
                                } else {
                                    echo to_currency($item['discounted_total']);
                                }
                                ?>
                            </td>

                            <td>
                                <a href="javascript:document.getElementById('<?= "cart_$line" ?>').submit();" title="<?= lang(ucfirst($controller_name) . '.update') ?>">
                                    <span class="glyphicon glyphicon-refresh"></span>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <?php if ($item['item_type'] == ITEM_TEMP) { ?>
                                <td><?= form_input(['type' => 'hidden', 'name' => 'item_id', 'value' => $item['item_id']]) ?></td>
                                <td style="align: center;" colspan="6">
                                    <?= form_input(['name' => 'item_description', 'id' => 'item_description', 'class' => 'form-control input-sm', 'value' => $item['description'], 'tabindex' => ++$tabindex]) ?>
                                </td>
                                <td> </td>
                            <?php } else { ?>
                                <td>&nbsp;</td>
                                <?php if ($item['allow_alt_description']) { ?>
                                    <td style="color: #2F4F4F;"><?= lang(ucfirst($controller_name) . '.description_abbrv') ?></td>
                                <?php } ?>

                                <td colspan="2" style="text-align: left;">
                                    <?php
                                    if ($item['allow_alt_description']) {
                                        echo form_input(['name' => 'description', 'class' => 'form-control input-sm', 'value' => $item['description'], 'onClick' => 'this.select();']);
                                    } else {
                                        if ($item['description'] != '') {
                                            echo $item['description'];
                                            echo form_hidden('description', $item['description']);
                                        } else {
                                            echo lang(ucfirst($controller_name) . '.no_description');
                                            echo form_hidden('description', '');
                                        }
                                    }
                                    ?>
                                </td>
                                <td>&nbsp;</td>
                                <td style="color: #2F4F4F;">
                                    <?php
                                    if ($item['is_serialized']) {
                                        echo lang(ucfirst($controller_name) . '.serial');
                                    }
                                    ?>
                                </td>
                                <td colspan="4" style="text-align: left;">
                                    <?php
                                    if ($item['is_serialized']) {
                                        echo form_input(['name' => 'serialnumber', 'class' => 'form-control input-sm', 'value' => $item['serialnumber'], 'onClick' => 'this.select();']);
                                    } else {
                                        echo form_hidden('serialnumber', '');
                                    }
                                    ?>
                                </td>
                            <?php } ?>
                        </tr>
                    <?= form_close() ?>
            <?php
                }
            }
            ?>
        </tbody>
    </table>
    <div id="addToBillWrapper" style="display: flex; justify-content: flex-end;">
   
<div class="badge-button add-to-bill-button" style="border-radius: 0.25rem;">
    Add to Bill
</div>



    <button id="new_order_button" class="btn btn-warning" style="margin-right: 10px;">
    Clear Cart
</button>


</div>



</div>


<!-- Overall Sale -->

<div id="overall_sale" class="panel panel-default">
    <div class="panel-body">
        <?= form_open("$controller_name/selectCustomer", ['id' => 'select_customer_form', 'class' => 'form-horizontal']) ?>
            <?php if (isset($customer)) { ?>
                <table class="sales_table_100">
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer') ?></th>
                        <th style="width: 45%; text-align: right;"><?= anchor("customers/view/$customer_id", $customer, ['class' => 'modal-dlg', 'data-btn-submit' => lang('Common.submit'), 'title' => lang('Customers.update')]) ?></th>
                    </tr>
                    <?php if (!empty($customer_email)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_email') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($customer_email) ?></th>
                        </tr>
                    <?php } ?>
                    <?php if (!empty($customer_address)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_address') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($customer_address) ?></th>
                        </tr>
                    <?php } ?>
                    <?php if (!empty($customer_location)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_location') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($customer_location) ?></th>
                        </tr>
                    <?php } ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_discount') ?></th>
                        <th style="width: 45%; text-align: right;"><?= ($customer_discount_type == FIXED) ? to_currency($customer_discount) : $customer_discount . '%' ?></th>
                    </tr>
                    <?php if ($config['customer_reward_enable']): ?>
                        <?php if (!empty($customer_rewards)) { ?>
                            <tr>
                                <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.rewards_package') ?></th>
                                <th style="width: 45%; text-align: right;"><?= esc($customer_rewards['package_name']) ?></th>
                            </tr>
                            <tr>
                                <th style="width: 55%;"><?= lang('Customers.available_points') ?></th>
                                <th style="width: 45%; text-align: right;"><?= esc($customer_rewards['points']) ?></th>
                            </tr>
                        <?php } ?>
                    <?php endif; ?>
                    <tr>
                        <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_total') ?></th>
                        <th style="width: 45%; text-align: right;"><?= to_currency($customer_total) ?></th>
                    </tr>
                    <?php if (!empty($mailchimp_info)) { ?>
                        <tr>
                            <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.customer_mailchimp_status') ?></th>
                            <th style="width: 45%; text-align: right;"><?= esc($mailchimp_info['status']) ?></th>
                        </tr>
                    <?php } ?>
                </table>

                <?= anchor(
                    "$controller_name/removeCustomer",
                    '<span class="glyphicon glyphicon-remove">&nbsp;</span>' . lang('Common.remove') . ' ' . lang('Customers.customer'),
                    ['class' => 'btn btn-danger btn-sm', 'id' => 'remove_customer_button', 'title' => lang('Common.remove') . ' ' . lang('Customers.customer')]
                )
                ?>
            <?php } else { ?>
                <div class="form-group" id="select_customer">
                    <label id="customer_label" for="customer" class="control-label" style="margin-bottom: 1em; margin-top: -1em;">
                        <?= lang(ucfirst($controller_name) . '.select_customer') . esc(" $customer_required") ?>
                    </label>
                    <?= form_input(['name' => 'customer', 'id' => 'customer', 'class' => 'form-control input-sm', 'value' => lang(ucfirst($controller_name) . '.start_typing_customer_name')]) ?>

                    <button class="btn btn-info btn-sm modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "customers/view" ?>" title="<?= lang(ucfirst($controller_name) . ".new_customer") ?>">
                        <span class="glyphicon glyphicon-user">&nbsp;</span><?= lang(ucfirst($controller_name) . ".new_customer") ?>
                    </button>
                    <button class="btn btn-default btn-sm modal-dlg" id="show_keyboard_help" data-href="<?= esc("$controller_name/salesKeyboardHelp") ?>" title="<?= lang(ucfirst($controller_name) . '.key_title') ?>">
                        <span class="glyphicon glyphicon-share-alt">&nbsp;</span><?= lang(ucfirst($controller_name) . '.key_help') ?>
                    </button>
                </div>
            <?php } ?>
        <?= form_close() ?>

        <table class="sales_table_100" id="sale_totals">
            <tr>
                <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.quantity_of_items', [$item_count]) ?></th>
                <th style="width: 45%; text-align: right;"><?= $total_units ?></th>
            </tr>
            <tr>
                <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.sub_total') ?></th>
                <th style="width: 45%; text-align: right;"><?= to_currency($subtotal) ?></th>
            </tr>
            <?php foreach ($taxes as $tax_group_index => $tax) { ?>
                <tr>
                    <th style="width: 55%;"><?= (float)$tax['tax_rate'] . '% ' . $tax['tax_group'] ?></th>
                    <th style="width: 45%; text-align: right;"><?= to_currency_tax($tax['sale_tax_amount']) ?></th>
                </tr>
            <?php } ?>
            <tr>
                <th style="width: 55%; font-size: 150%"><?= lang(ucfirst($controller_name) . '.total') ?></th>
                <th style="width: 45%; font-size: 150%; text-align: right;"><span id="sale_total"><?= to_currency($total) ?></span></th>
            </tr>
        </table>



    </div>
        <?php if (isset($cart) && count($cart) >= 0) { ?>

            <table class="sales_table_100" id="payment_totals">
                <tr>
                    <th style="width: 55%;"><?= lang(ucfirst($controller_name) . '.payments_total') ?></th>
                    <th style="width: 45%; text-align: right;"><?= to_currency($payments_total) ?></th>
                </tr>
                <tr>
                    <th style="width: 55%; font-size: 120%"><?= lang(ucfirst($controller_name) . '.amount_due') ?></th>
                    <th style="width: 45%; font-size: 120%; text-align: right;"><span id="sale_amount_due"><?= to_currency($amount_due) ?></span></th>
                </tr>
            </table>

            <div id="payment_details">
                <?php if ($payments_cover_total) { // Show Complete sale button instead of Add Payment if there is no amount due left ?>
                    <?= form_open("$controller_name/addPayment", ['id' => 'add_payment_form', 'class' => 'form-horizontal']) ?>
    <table class="sales_table_100">
        <tr>
            <td><?= lang(ucfirst($controller_name) . '.payment') ?></td>
            <td>
                <?php
                // ADD THIS LINE:
                
                //$payment_options = ['' => lang('Common.select_payment_option')] + $payment_options;
                $payment_options = ['' => 'Select Payment Method'] + $payment_options;
                ?>
                <?= form_dropdown('payment_type', $payment_options, $selected_payment_type, ['id' => 'payment_types', 'class' => 'selectpicker show-menu-arrow', 'data-style' => 'btn-default btn-sm', 'data-width' => 'fit']) ?>
            </td>
        </tr>
                            <tr>
                                <td><span id="amount_tendered_label"><?= lang(ucfirst($controller_name) . '.amount_tendered') ?></span></td>
                                <td>
                                    <?= form_input(['name' => 'amount_tendered', 'id' => 'amount_tendered', 'class' => 'form-control input-sm disabled', 'disabled' => 'disabled', 'value' => '0', 'size' => '5', 'tabindex' => ++$tabindex, 'onClick' => 'this.select();']) ?>
                                </td>
                            </tr>
                        </table>
                    <?= form_close() ?>

                    <?php
                    // Only show this part if in sale or return mode
                    if ($pos_mode) {
                        $due_payment = false;

                        if (count($payments) > 0) {
                            foreach ($payments as $payment_id => $payment) {
                                if ($payment['payment_type'] == lang(ucfirst($controller_name) . '.due')) {
                                    $due_payment = true;
                                }
                            }
                        }

                        if (!$due_payment || ($due_payment && isset($customer))) {    // TODO: $due_payment is not needed because the first clause insures that it will always be true if it gets to this point.  Can be shortened to if (!$due_payment || isset($customer))
                    ?>
                            <div class="btn btn-sm btn-success pull-right" id="finish_sale_button" tabindex="<?= ++$tabindex ?>">
                                <span class="glyphicon glyphicon-ok">&nbsp;</span><?= lang(ucfirst($controller_name) . '.complete_sale') ?>
                            </div>
                    <?php
                        }
                    }
                    ?>
               <?php } else { ?>
    <?= form_open("$controller_name/addPayment", ['id' => 'add_payment_form', 'class' => 'form-horizontal']) ?>
    <input type="hidden" name="amount_tendered" id="hidden_amount_tendered" value="<?= to_currency_no_money($amount_due) ?>">
    
    <table class="sales_table_100">
        <!-- Payment Type -->
        <tr>
            <td><?= lang(ucfirst($controller_name) . '.payment') ?></td>
            <td>
                <?= form_dropdown(
                    'payment_type',
                    $payment_options,
                    $selected_payment_type,
                    [
                        'id' => 'payment_types',
                        'class' => 'selectpicker show-menu-arrow',
                        'data-style' => 'btn-default btn-sm',
                        'data-width' => 'fit'
                    ]
                ) ?>
            </td>
        </tr>

        <!-- Amount Tendered -->
        <tr>
            <td><span id="amount_tendered_label"><?= lang(ucfirst($controller_name) . '.amount_tendered') ?></span></td>
            <td>
                <?= form_input([
                    'name' => 'amount_tendered',
                    'id' => 'amount_tendered',
                    'class' => 'form-control input-sm non-giftcard-input',
                    'value' => to_currency_no_money($amount_due),
                    'size' => '5',
                    'tabindex' => ++$tabindex,
                    'onClick' => 'this.select();'
                ]) ?>
                <?= form_input([
                    'name' => 'amount_tendered',
                    'id' => 'amount_tendered',
                    'class' => 'form-control input-sm giftcard-input',
                    'disabled' => true,
                    'value' => to_currency_no_money($amount_due),
                    'size' => '5',
                    'tabindex' => ++$tabindex
                ]) ?>
            </td>
        </tr>




    </table>
    
<?= form_close() ?>
<?php } ?>
<?= form_open("$controller_name/editItem/all", ['id' => 'edit_all_discount', 'class' => 'form-horizontal']) ?>
<?= form_hidden('all_items_discount', (string) 1) ?> <!-- cast to string -->

<tr>
    <td colspan="7" style="padding-top: 90px;"> <!-- add top padding instead of margin -->
        <div style="display: flex; align-items: center; gap: 40px;">
            <!-- Label -->
            <span style="white-space: nowrap;">
                <?= lang(ucfirst($controller_name) . '.discount') ?>
            </span>

            <!-- Input + Toggle grouped together -->
            <div style="display: flex; align-items: center;">
                <?= form_input([
                    'name' => 'discount',
                    'id' => 'all_items_discount_input',
                    'class' => 'form-control input-sm',
                    'style' => 'width: 80px; margin: 0; border-top-right-radius: 0; border-bottom-right-radius: 0; padding-right: 55px;', 
                    'value' => '0',
                    'tabindex' => ++$tabindex,
                    'onClick' => 'this.select();'
                ]) ?>

                <?= form_checkbox([
                    'id' => 'all_discount_toggle',
                    'name' => 'discount_type',
                    'value' => '1',
                    'data-toggle' => "toggle",
                    'data-size' => 'small',
                    'data-onstyle' => 'success',
                    'data-on' => '<b>' . $config['currency_symbol'] . '</b>',
                    'data-off' => '<b>%</b>',
                    'checked' => false
                ]) ?>
            </div>
        </div>
    </td>

    <td>
        <button type="submit" class="btn btn-sm btn-primary" style="display:none;">
            <?= lang('Common.apply') ?>
        </button>
    </td>
</tr>

<?= form_close() ?>


                <?php if (count($payments) >= 0 ) { // Only show this part if there is at least one payment entered. ?>
                    <table class="sales_table_100" id="register">
                        <thead>
                            <tr>
                                <th style="width: 10%;"><?= lang('Common.delete') ?></th>
                                <th style="width: 60%;"><?= lang(ucfirst($controller_name) . '.payment_type') ?></th>
                                <th style="width: 20%;"><?= lang(ucfirst($controller_name) . '.payment_amount') ?></th>
                               
                            </tr>
                        </thead>

                        <tbody id="payment_contents">
                            <?php foreach ($payments as $payment_id => $payment) { ?>
                                <tr>
                                    <td><?= anchor("$controller_name/deletePayment/". strtr(base64_encode($payment_id), '+/=', '-_.'), '<span class="glyphicon glyphicon-trash"></span>') ?></td>
                                    <td><?= $payment['payment_type'] ?></td>
                                    <td style="text-align: right;"><?= to_currency($payment['payment_amount']) ?></td>
                                </tr>
                                
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>
            </div>

          <?= form_open("$controller_name/cancel", ['id' => 'buttons_form']) ?>
<div class="form-group" id="buttons_sale">
    <!-- Suspend Sale Button -->
    <div class="btn btn-sm btn-default" id="suspend_sale_button">
        <span class="glyphicon glyphicon-align-justify">&nbsp;</span>
        <?= lang(ucfirst($controller_name) . '.suspend_sale') ?>
    </div>

    <!-- Cancel Sale Button -->
    <div class="btn btn-sm btn-danger" id="cancel_sale_button">
        <span class="glyphicon glyphicon-remove">&nbsp;</span>
        <?= lang(ucfirst($controller_name) . '.cancel_sale') ?>
    </div>

    <!-- Order Button + Print Checkbox -->
    <div class="order-print-group">
        <button type="button" class="btn btn-sm btn-primary" id="order_button">
            <span class="glyphicon glyphicon-list-alt"></span> Order
        </button>

        <label class="print-label">
            <input type="checkbox" id="print_order_queue_checkbox">
            Print
        </label>
    </div>

    <!-- Optional: Finish Invoice Button -->
    <?php if (!$pos_mode && isset($customer)) { ?>
        <div class="btn btn-sm btn-success" id="finish_invoice_quote_button">
            <span class="glyphicon glyphicon-ok">&nbsp;</span>
            <?= esc($mode_label) ?>
        </div>
    <?php } ?>
</div>


<?= form_close() ?>


            <?php if ($payments_cover_total || !$pos_mode) { // Only show this part if the payment cover the total ?>
                <div class="container-fluid">
                    <div class="no-gutter row">
                        <div class="form-group form-group-sm">
                            <div class="col-xs-12">
                                <?= form_label(lang('Common.comments'), 'comments', ['class' => 'control-label', 'id' => 'comment_label', 'for' => 'comment']) ?>
                                <?= form_textarea(['name' => 'comment', 'id' => 'comment', 'class' => 'form-control input-sm', 'value' => $comment, 'rows' => '2']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group form-group-sm">
                            <div class="col-xs-6">
                                <label for="sales_print_after_sale" class="control-label checkbox">
                                    <?= form_checkbox(['name' => 'sales_print_after_sale', 'id' => 'sales_print_after_sale', 'value' => 1, 'checked' => $print_after_sale]) ?>
                                    <?= lang(ucfirst($controller_name) . '.print_after_sale') ?>
                                </label>
                            </div>

                            <?php if (!empty($customer_email)) { ?>
                                <div class="col-xs-6">
                                    <label for="email_receipt" class="control-label checkbox">
                                        <?= form_checkbox(['name' => 'email_receipt', 'id' => 'email_receipt', 'value' => 1, 'checked' => $email_receipt]) ?>
                                        <?= lang(ucfirst($controller_name) . '.email_receipt') ?>
                                    </label>
                                </div>
                            <?php } ?>
                            <?php if ($mode == 'sale_work_order') { ?>
                                <div class="col-xs-6">
                                    <label for="price_work_orders" class="control-label checkbox">
                                        <?= form_checkbox(['name' => 'price_work_orders', 'id' => 'price_work_orders', 'value' => 1, 'checked' => $price_work_orders]) ?>
                                        <?= lang(ucfirst($controller_name) . '.include_prices') ?>
                                    </label>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if (($mode == 'sale_invoice') && $config['invoice_enable']) { ?>
                        <div class="row">
                            <div class="form-group form-group-sm">
                                <div class="col-xs-6">
                                    <label for="sales_invoice_number" class="control-label checkbox">
                                        <?= lang(ucfirst($controller_name) . '.invoice_enable') ?>
                                    </label>
                                </div>

                                <div class="col-xs-6">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-addon input-sm">#</span>
                                        <?= form_input(['name' => 'sales_invoice_number', 'id' => 'sales_invoice_number', 'class' => 'form-control input-sm', 'value' => $invoice_number]) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
                
        <?php
            }
        }
        ?>
    </div>
</div>
<div class="modal fade" id="orderModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Orders Queue</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body"></div>
    </div>
  </div>
</div>



<script type="text/javascript">
    $(document).ready(function() {
        const redirect = function() {
            window.location.href = "<?= site_url('sales'); ?>";
        };

        $("#remove_customer_button").click(function() {
            $.post("<?= site_url('sales/removeCustomer'); ?>", redirect);
        });

        $(".delete_item_button").click(function() {
            const item_id = $(this).data('item-id');
            $.post("<?= site_url('sales/deleteItem/'); ?>" + item_id, redirect);
        });

        $(".delete_payment_button").click(function() {
            const item_id = $(this).data('payment-id');
            $.post("<?= site_url('sales/deletePayment/'); ?>" + item_id, redirect);
        });

        $("input[name='item_number']").change(function() {
            var item_id = $(this).parents('tr').find("input[name='item_id']").val();
            var item_number = $(this).val();
            $.ajax({
                url: "<?= site_url('sales/changeItemNumber') ?>",
                method: 'post',
                data: {
                    'item_id': item_id,
                    'item_number': item_number,
                },
                dataType: 'json'
            });
        });

        $("input[name='name']").change(function() {
            var item_id = $(this).parents('tr').find("input[name='item_id']").val();
            var item_name = $(this).val();
            $.ajax({
                url: "<?= site_url('sales/changeItemName') ?>",
                method: 'post',
                data: {
                    'item_id': item_id,
                    'item_name': item_name,
                },
                dataType: 'json'
            });
        });

        $("input[name='item_description']").change(function() {
            var item_id = $(this).parents('tr').find("input[name='item_id']").val();
            var item_description = $(this).val();
            $.ajax({
                url: "<?= site_url('sales/changeItemDescription') ?>",
                method: 'post',
                data: {
                    'item_id': item_id,
                    'item_description': item_description,
                },
                dataType: 'json'
            });
        });

        $('#item').focus();

        $('#item').blur(function() {
            $(this).val("<?= lang(ucfirst($controller_name) . '.start_typing_item_name') ?>");
        });

        $('#item').autocomplete({
            source: "<?= esc("$controller_name/itemSearch") ?>",
            minChars: 0,
            autoFocus: false,
            delay: 500,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $('#add_item_form').submit();
                return false;
            }
        });

        $('#item').keypress(function(e) {
            if (e.which == 13) {
                $('#add_item_form').submit();
                return false;
            }
        });

        var clear_fields = function() {
            if ($(this).val().match("<?= lang(ucfirst($controller_name) . '.start_typing_item_name') . '|' . lang(ucfirst($controller_name) . '.start_typing_customer_name') ?>")) {
                $(this).val('');
            }
        };

        $('#item, #customer').click(clear_fields).dblclick(function(event) {
            $(this).autocomplete('search');
        });

        $('#customer').blur(function() {
            $(this).val("<?= lang(ucfirst($controller_name) . '.start_typing_customer_name') ?>");
        });

        $('#customer').autocomplete({
            source: "<?= site_url('customers/suggest') ?>",
            minChars: 0,
            delay: 10,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $('#select_customer_form').submit();
                return false;
            }
        });

        $('#customer').keypress(function(e) {
            if (e.which == 13) {
                $('#select_customer_form').submit();
                return false;
            }
        });

        $('.giftcard-input').autocomplete({
            source: "<?= site_url('giftcards/suggest') ?>",
            minChars: 0,
            delay: 10,
            select: function(a, ui) {
                $(this).val(ui.item.value);
                $('#add_payment_form').submit();
                return false;
            }
        });

        $('#comment').keyup(function() {
            $.post("<?= esc(site_url("$controller_name/setComment")) ?>", {
                comment: $('#comment').val()
            });
        });

        <?php if ($config['invoice_enable']) { ?>
            $('#sales_invoice_number').keyup(function() {
                $.post("<?= esc(site_url("$controller_name/setInvoiceNumber")) ?>", {
                    sales_invoice_number: $('#sales_invoice_number').val()
                });
            });
        <?php } ?>

        $('#sales_print_after_sale').change(function() {
            $.post("<?= esc(site_url("$controller_name/setPrintAfterSale")) ?>", {
                sales_print_after_sale: $(this).is(':checked')
            });
        });

        $('#price_work_orders').change(function() {
            $.post("<?= esc(site_url("$controller_name/setPriceWorkOrders")) ?>", {
                price_work_orders: $(this).is(':checked')
            });
        });

        $('#email_receipt').change(function() {
            $.post("<?= esc(site_url("$controller_name/setEmailReceipt")) ?>", {
                email_receipt: $(this).is(':checked')
            });
        });

        $('#finish_sale_button').click(function() {
            $('#buttons_form').attr('action', "<?= "$controller_name/complete" ?>");
            $('#buttons_form').submit();
        });

        $('#finish_invoice_quote_button').click(function() {
            $('#buttons_form').attr('action', "<?= "$controller_name/complete" ?>");
            $('#buttons_form').submit();
        });

        $('#suspend_sale_button').click(function() {
            $('#buttons_form').attr('action', "<?= site_url("$controller_name/suspend") ?>");
            $('#buttons_form').submit();
        });

        $('#cancel_sale_button').click(function() {
            if (confirm("<?= lang(ucfirst($controller_name) . '.confirm_cancel_sale') ?>")) {
                $('#buttons_form').attr('action', "<?= site_url("$controller_name/cancel") ?>");
                $('#buttons_form').submit();
            }
        });

        // FIXED: Handle payment type changes and automatic form submission
        $('#payment_types').change(function() {
            check_payment_type();
            // Auto-submit the form when payment type changes
            $('#add_payment_form').submit();
        }).ready(check_payment_type);

        $('#cart_contents input').keypress(function(event) {
            if (event.which == 13) {
                $(this).parents('tr').prevAll('form:first').submit();
            }
        });

        // FIXED: Handle amount tendered changes for automatic payment addition
        $('#amount_tendered').on('blur', function() {
            // Submit form when amount is changed and field loses focus
            $('#add_payment_form').submit();
        });

        $('#finish_sale_button').keypress(function(event) {
            if (event.which == 13) {
                $('#finish_sale_form').submit();
            }
        });

        dialog_support.init('a.modal-dlg, button.modal-dlg');

        table_support.handle_submit = function(resource, response, stay_open) {
            $.notify({
                message: response.message
            }, {
                type: response.success ? 'success' : 'danger'
            })

            if (response.success) {
                if (resource.match(/customers$/)) {
                    $('#customer').val(response.id);
                    $('#select_customer_form').submit();
                } else {
                    var $stock_location = $("select[name='stock_location']").val();
                    $('#item_location').val($stock_location);
                    $('#item').val(response.id);
                    if (stay_open) {
                        $('#add_item_form').ajaxSubmit();
                    } else {
                        $('#add_item_form').submit();
                    }
                }
            }
        }

        $('[name="price"],[name="quantity"],[name="discount"],[name="description"],[name="serialnumber"],[name="discounted_total"]').change(function() {
            $(this).parents('tr').prevAll('form:first').submit()
        });

        $('[name="discount_toggle"]').change(function() {
            var input = $('<input>').attr('type', 'hidden').attr('name', 'discount_type').val(($(this).prop('checked')) ? 1 : 0);
            $('#cart_' + $(this).attr('data-line')).append($(input));
            $('#cart_' + $(this).attr('data-line')).submit();
        });

        // Initialize payment handlers on page load
        initializePaymentHandlers();
    });

    // Function to initialize payment event handlers
    function initializePaymentHandlers() {
        // Remove existing handlers to prevent duplicates
        $('#payment_types').off('change.paymentHandler');
        $('#amount_tendered').off('blur.paymentHandler keypress.paymentHandler input.paymentHandler change.paymentHandler');
        $('#add_payment_form').off('submit.paymentHandler');
        $('.delete_payment_button').off('click.paymentHandler');

        // Handle payment type changes
        $('#payment_types').on('change.paymentHandler', function() {
            check_payment_type();
            // Auto-submit the form when payment type changes
            $('#add_payment_form').submit();
        });

        // Handle amount tendered changes
        $('#amount_tendered').on('input.paymentHandler change.paymentHandler', function() {
            $('#hidden_amount_tendered').val($(this).val());
        });

        // Handle amount tendered blur (auto-submit)
        $('#amount_tendered').on('blur.paymentHandler', function() {
            $('#add_payment_form').submit();
        });

        // Handle Enter key on amount tendered
        $('#amount_tendered').on('keypress.paymentHandler', function(event) {
            if (event.which == 13) {
                $('#add_payment_form').submit();
            }
        });

        // Handle payment deletion buttons
        $('.delete_payment_button').on('click.paymentHandler', function() {
            const payment_id = $(this).data('payment-id');
            $.post("<?= site_url('sales/deletePayment/'); ?>" + payment_id, function() {
                window.location.reload();
            });
        });

        // Run check_payment_type if payment type is already selected
        if ($('#payment_types').val()) {
            check_payment_type();
        }
    }



    function check_payment_type() {
        var cash_mode = <?= json_encode($cash_mode) ?>;

        if ($("#payment_types").val() == "<?= lang(ucfirst($controller_name) . '.giftcard') ?>") {
            $("#sale_total").html("<?= to_currency($total) ?>");
            $("#sale_amount_due").html("<?= to_currency($amount_due) ?>");
            $("#amount_tendered_label").html("<?= lang(ucfirst($controller_name) . '.giftcard_number') ?>");
            $("#amount_tendered:enabled").val('').focus();
            $(".giftcard-input").attr('disabled', false);
            $(".non-giftcard-input").attr('disabled', true);
            $(".giftcard-input:enabled").val('').focus();
        } else if (($("#payment_types").val() == "<?= lang(ucfirst($controller_name) . '.cash') ?>" && cash_mode == '1')) {
            $("#sale_total").html("<?= to_currency($non_cash_total) ?>");
            $("#sale_amount_due").html("<?= to_currency($cash_amount_due) ?>");
            $("#amount_tendered_label").html("<?= lang(ucfirst($controller_name) . '.amount_tendered') ?>");
            $("#amount_tendered:enabled").val("<?= to_currency_no_money($cash_amount_due) ?>");
            $(".giftcard-input").attr('disabled', true);
            $(".non-giftcard-input").attr('disabled', false);
        } else {
            $("#sale_total").html("<?= to_currency($non_cash_total) ?>");
            $("#sale_amount_due").html("<?= to_currency($amount_due) ?>");
            $("#amount_tendered_label").html("<?= lang(ucfirst($controller_name) . '.amount_tendered') ?>");
            $("#amount_tendered:enabled").val("<?= to_currency_no_money($amount_due) ?>");
            $(".giftcard-input").attr('disabled', true);
            $(".non-giftcard-input").attr('disabled', false);
        }
        $('#hidden_amount_tendered').val($('#amount_tendered').val());
    }

    // Add Keyboard Shortcuts/Hotkeys to Sale Register
    document.body.onkeyup = function(e) {
        switch (event.altKey && event.keyCode) {
            case 49: // Alt + 1 Items Search
                $("#item").focus();
                $("#item").select();
                break;
            case 50: // Alt + 2 Customers Search
                $("#customer").focus();
                $("#customer").select();
                break;
            case 51: // Alt + 3 Suspend Current Sale
                $("#suspend_sale_button").click();
                break;
            case 52: // Alt + 4 Check Suspended
                $("#show_suspended_sales_button").click();
                break;
            case 53: // Alt + 5 Edit Amount Tendered Value
                $("#amount_tendered").focus();
                $("#amount_tendered").select();
                break;
            case 54: // Alt + 6 Add Payment (kept for compatibility)
                $("#add_payment_form").submit();
                break;
            case 55: // Alt + 7 Add Payment and Complete Sales/Invoice
                $("#add_payment_form").submit();
                setTimeout(function() {
                    window.location.href = "<?= 'sales/complete' ?>";
                }, 500);
                break;
            case 56: // Alt + 8 Finish Quote/Invoice without payment
                $("#finish_invoice_quote_button").click();
                break;
            case 57: // Alt + 9 Open Shortcuts Help Modal
                $("#show_keyboard_help").click();
                break;
        }

        switch (event.keyCode) {
            case 27: // ESC Cancel Current Sale
                $("#cancel_sale_button").click();
                break;
        }

    }

$(document).ready(function () {
    const categoryContainer = $('#categoryContainer');
    const itemsContainer = $('#itemsContainer');
    const allItems = JSON.parse($('#allItemsJson').val());

    // A Set to keep track of which category item grids are currently open
    const openCategories = new Set();

    const categories = <?= json_encode($categories) ?>;
    const itemsByCategory = {};
    Object.keys(categories).forEach(key => {
        itemsByCategory[key] = { name: categories[key], items: [] };
    });

    allItems.forEach(item => {
        const categoryKey = item.category;
        if (itemsByCategory[categoryKey]) {
            itemsByCategory[categoryKey].items.push(item);
        }
    });

    // Function to render the category buttons
    function renderCategoryButtons() {
        let html = '';
        Object.keys(itemsByCategory).forEach(categoryKey => {
            const categoryName = itemsByCategory[categoryKey].name;
            const safeKey = categoryKey.replace(/\s+/g, '_').replace(/[^\w\-]/g, '');

            // Do not render the "Select Category" button
            if (categoryName.trim().toLowerCase() !== 'select category') {
                const isActive = openCategories.has(categoryKey) ? 'active' : '';
                html += `
                    <div class="badge-button category-button ${isActive}" data-category-key="${categoryKey}" data-safe-key="${safeKey}">
                        ${categoryName}
                    </div>
                `;
            }
        });
        
        

        categoryContainer.html(html);
    }

    // Function to render the item buttons for a given category
    function renderItemButtons(categoryKey, safeKey) {
        const category = itemsByCategory[categoryKey];
        if (!category) return;

        let html = `
            <div class="items-section" id="items-grid-${safeKey}">
                <div style="font-weight: bold; margin-bottom: 10px;">${category.name}</div>
                <div class="category-grid">
        `;

        category.items.forEach(item => {
            html += `
                <div class="badge-button item-button" data-item-id="${item.item_id}" data-item-name="${item.name}">
                    ${item.name}
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
        itemsContainer.append(html);
        openCategories.add(categoryKey);
    }

    // Initially show all category buttons
    renderCategoryButtons();

    // Event handler for clicking on a category button
    categoryContainer.on('click', '.category-button:not(.close-all-button)', function () {
        const clickedButton = $(this);
        const categoryKey = clickedButton.data('category-key');
        const safeKey = clickedButton.data('safe-key');

        // Check if the clicked category is already open
        if (openCategories.has(categoryKey)) {
            // If it's the currently open one, just close it
            $(`#items-grid-${safeKey}`).remove();
            openCategories.delete(categoryKey);
            clickedButton.removeClass('active');
            
            return;
        }

        // If another category is already open, close it first
        if (openCategories.size > 0) {
            const currentOpenKey = [...openCategories][0];
            const currentSafeKey = currentOpenKey.replace(/\s+/g, '_').replace(/[^\w\-]/g, '');

            $(`#items-grid-${currentSafeKey}`).remove();
            $(`[data-category-key="${currentOpenKey}"]`).removeClass('active');
            openCategories.delete(currentOpenKey);
            
        }

        // Open the new category
        clickedButton.addClass('active');
        renderItemButtons(categoryKey, safeKey);
    });

    // Event handler for the "Close All Categories" button
    categoryContainer.on('click', '.close-all-button', function () {
        window.location.href = "<?= site_url('sales') ?>";
    });

    // Event handler for clicking on an item button
    itemsContainer.on('click', '.item-button', function (e) {
        e.stopPropagation();
        const itemId = $(this).data('item-id');
        if (!itemId) return;

        $('#add_item_form input[name="item"]').val(itemId);
var selectedPaymentType = $('#payment_details select.selectpicker').val();


        $.ajax({
            url: $('#add_item_form').attr('action'),
            type: 'POST',
            data: $('#add_item_form').serialize(),
            success: function(response) {
                const $response = $(response);

                // Update cart contents
                const $newCartContents = $response.find('#cart_contents');
                if ($newCartContents.length) {
                    $('#cart_contents').html($newCartContents.html());
                }
              
             // Update sale/payment section
        const $newPayment = $response.find('#payment_details');
        if ($newPayment.length) {
            $('#payment_details').html($newPayment.html());
        }
var $paymentSelect = $('#payment_details select.selectpicker');
        $paymentSelect.selectpicker('refresh');

        // If a payment type was already selected, trigger form submit
        if (selectedPaymentType && selectedPaymentType !== '') {
            // Re-apply the previously selected payment type
            $paymentSelect.val(selectedPaymentType).selectpicker('refresh');

            // Submit payment form automatically
            $('#add_payment_form').submit();
        }


        // Update overall totals
        const $newTotals = $response.find('#sale_totals');
        if ($newTotals.length) {
            $('#sale_totals').html($newTotals.html());
             //$('#add_payment_form').submit();
        }
    const $newpayTotals = $response.find('#payment_totals');
if ($newpayTotals.length) {
    $('#payment_totals').html($newpayTotals.html());
}

// Refresh selectpicker
var $paymentSelect = $('#payment_details select.selectpicker');
$paymentSelect.selectpicker('refresh');

// Reload immediately if a payment type is selected
if ($paymentSelect.val() && $paymentSelect.val() !== '') {
    $('#add_payment_form').submit();
}

// Also reload when user changes the payment type
$paymentSelect.off('change').on('change', function() {
    if ($(this).val() && $(this).val() !== '') {
       $('#add_payment_form').submit();
    }
});

   

        // Update payment contents
const $newcontent = $response.find('#payment_contents');
        if ($newcontent.length) {
            $('#payment_contents').html($newcontent.html());
        }
          
            },
            error: function(xhr, status, error) {
                console.error('Error updating cart:', error);
                alert('Error updating cart/payment');
            }
        });
    });
});
 
 $(document).on('click', '.add-to-bill-button', function () {
     window.location.href = "<?= site_url('sales') ?>";
 });



const currentOrderId = <?= $current_order_id ?? 'null' ?>;
    const dinnerTable = "<?= $dinner_table_number ?? 'N/A' ?>";
    const waiterName = "<?= $waiter_name ?? 'N/A' ?>";
    const orders = <?= json_encode($orders ?? []) ?>;

  document.getElementById('print_order_queue_checkbox').addEventListener('change', function () {
    if (!this.checked) return;
    
    // Aggregate orders by description
    const aggregatedOrders = [];
    orders.forEach(item => {
        const existing = aggregatedOrders.find(i => i.description === item.description);
        if (existing) {
            existing.quantity += parseFloat(item.quantity);
        } else {
            aggregatedOrders.push({
                description: item.description,
                quantity: parseFloat(item.quantity)
            });
        }
    });
    
    // Fixed Receipt HTML with width AND height fitted to content only
    const receiptHTML = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Kitchen Order Queue</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            @media print {
                @page {
                    size: 80mm auto;
                    margin: 0 !important;
                    padding: 0 !important;
                }
                
                html, body {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: fit-content !important;  /* Only as wide as content */
                    max-width: 80mm !important;     /* But not wider than 80mm */
                    height: fit-content !important; /* Only as tall as content */
                    overflow: hidden !important;
                }
                
                /* Hide everything except our receipt */
                body * {
                    visibility: hidden;
                }
                
                .receipt-container, .receipt-container * {
                    visibility: visible;
                }
                
                .receipt-container {
                    position: absolute !important;
                    left: 0 !important;
                    top: 0 !important;
                    width: fit-content !important;  /* Only as wide as content */
                    max-width: 80mm !important;     /* But not wider than 80mm */
                    height: fit-content !important; /* Only as tall as content */
                }
            }
            
            html {
                width: fit-content !important;   /* Only as wide as receipt */
                max-width: 80mm !important;      /* But not wider than 80mm */
                height: fit-content !important;  /* Only as tall as receipt */
            }
            
            body {
                font-family: 'Courier New', monospace;
                font-size: 22px;
                line-height: 1.2;
                color: #000;
                background: white;
                width: fit-content !important;   /* Only as wide as receipt */
                max-width: 80mm !important;      /* But not wider than 80mm */
                height: fit-content !important;  /* Only as tall as receipt */
                margin: 0;
                padding: 0;
            }
            
            .receipt-container {
                width: fit-content !important;   /* Only as wide as content */
                max-width: 80mm !important;      /* But not wider than 80mm */
                height: fit-content !important;  /* Only as tall as content */
                padding: 2mm;
                background: white;
                display: inline-block;            /* Helps with fit-content */
            }
            
            .header {
                text-align: center;
                font-weight: bold;
                font-size: 24px;
                margin-bottom: 3mm;
                width: 100%;
                white-space: nowrap;              /* Prevent text wrapping to control width */
            }
            
            .order-info {
                font-size: 22px;
                line-height: 1.1;
                margin-bottom: 1mm;
                white-space: nowrap;              /* Prevent text wrapping */
            }
            
            .separator {
                border-bottom: 1px dashed #000;
                margin: 2mm 0;
                width: 100%;
                min-width: 70mm;                  /* Ensure minimum width for separator */
            }
            
            .items-table {
                width: 100%;
                min-width: 70mm;                  /* Ensure minimum table width */
                max-width: 76mm;                  /* Account for padding */
                border-collapse: collapse;
                font-size: 22px;
            }
            
            .items-table th {
                text-align: left;
                padding: 1mm 0;
                border-bottom: 1px solid #000;
                font-weight: bold;
            }
            
            .items-table td {
                padding: 0.5mm 2mm 0.5mm 0;      /* Add right padding for spacing */
                vertical-align: top;
            }
            
            .qty-col {
                text-align: right;
                width: 15mm;
                min-width: 15mm;
            }
            
            .item-name {
                word-wrap: break-word;
                max-width: 55mm;
                min-width: 40mm;                  /* Ensure minimum width */
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="header">Bistecca Cafe and Grill</div>
            <div class="order-info">Order: ${currentOrderId ?? 'N/A'}</div>
            <div class="order-info">Table: ${dinnerTable}</div>
            <div class="order-info">Waiter: ${waiterName}</div>
            <div class="separator"></div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="qty-col">Qty</th>
                    </tr>
                </thead>
                <tbody>
                    ${aggregatedOrders.length > 0
                        ? aggregatedOrders.map(item => `
                            <tr>
                                <td class="item-name">${item.description ?? 'N/A'}</td>
                                <td class="qty-col">${item.quantity ?? '0'}</td>
                            </tr>
                        `).join('')
                        : `<tr><td colspan="2">No items</td></tr>`
                    }
                </tbody>
            </table>
        </div>
    </body>
    </html>
    `;
    
    // Create and print using a more reliable method
    const printWindow = window.open('', '_blank',);
    
    if (printWindow) {
        printWindow.document.write(receiptHTML);
        printWindow.document.close();
        
        // Wait for content to load before printing
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                
                // Close after printing
                setTimeout(() => {
                    printWindow.close();
                }, 1000);
            }, 500);
        };
    }
    
    this.checked = false;
});


$(document).ready(function() {
    $("#order_button").on("click", function(e) {
        e.preventDefault(); // stop form submission
        $.get("<?= site_url('sales/order_queue'); ?>", function(data) {
            $("#orderModal .modal-body").html(data);
            $("#orderModal").modal("show");
        });
    });
});

$('#new_order_button').click(function() {
    if (confirm("Start a new order? Current cart will be cleared.")) {
        // Direct GET call to the new order method
        window.location.href = "<?= site_url("$controller_name/newOrder") ?>";
    }
});


</script>

<?= view('partial/footer') ?>