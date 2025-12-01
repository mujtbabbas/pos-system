<?php

namespace App\Controllers;

use App\Libraries\Barcode_lib;
use App\Libraries\Email_lib;
use App\Libraries\Sale_lib;
use App\Libraries\Tax_lib;
use App\Libraries\Token_lib;
use App\Models\Customer;
use App\Models\Customer_rewards;
use App\Models\Dinner_table;
use App\Models\Employee;
use App\Models\Giftcard;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Item_kit;
use App\Models\Sale;
use App\Models\Stock_location;
use App\Models\Tokens\Token_invoice_count;
use App\Models\Tokens\Token_customer;
use App\Models\Tokens\Token_invoice_sequence;
use App\Models\Order;
use App\Models\OrderItem; 
use Config\Services;
use Config\OSPOS;
use ReflectionException;
use stdClass;

class Sales extends Secure_Controller
{
    protected $helpers = ['file'];
    private Barcode_lib $barcode_lib;
    private Email_lib $email_lib;
    private Sale_lib $sale_lib;
    private Tax_lib $tax_lib;
    private Token_lib $token_lib;
    private Customer $customer;
    private Customer_rewards $customer_rewards;
    private Dinner_table $dinner_table;
    protected Employee $employee;
    private Item $item;
    private Item_kit $item_kit;
    private Sale $sale;
    private Stock_location $stock_location;
    private array $config;

    public function __construct()
    {
        parent::__construct('sales');

        $this->session = session();
        $this->barcode_lib = new Barcode_lib();
        $this->email_lib = new Email_lib();
        $this->sale_lib = new Sale_lib();
        $this->tax_lib = new Tax_lib();
        $this->token_lib = new Token_lib();
        $this->config = config(OSPOS::class)->settings;

        $this->customer = model(Customer::class);
        $this->sale = model(Sale::class);
        $this->item = model(Item::class);
        $this->item_kit = model(Item_kit::class);
        $this->stock_location = model(Stock_location::class);
        $this->customer_rewards = model(Customer_rewards::class);
        $this->dinner_table = model(Dinner_table::class);
        $this->employee = model(Employee::class);
       
    }

    /**
     * @return void
     */
   /**
 * @return void
 */
public function getIndex(): void

{
    
    $this->session->set('allow_temp_items', 1);

    
    $this->_reload();
}

    /**
     * Load the sale edit modal. Used in app/Views/sales/register.php.
     *
     * @return void
     * @noinspection PhpUnused
     */
     
    public function getManage(): void
    {
        $person_id = $this->session->get('person_id');

        if (!$this->employee->has_grant('reports_sales', $person_id)) {
            redirect('no_access/sales/reports_sales');
        } else {
            $data['table_headers'] = get_sales_manage_table_headers();

            $data['filters'] = [
                'only_cash'         => lang('Sales.cash_filter'),
                'only_due'          => lang('Sales.due_filter'),
                'only_check'        => lang('Sales.check_filter'),
                'only_creditcard'   => lang('Sales.credit_filter'),
                'only_invoices'     => lang('Sales.invoice_filter'),
                'selected_customer' => lang('Sales.selected_customer')
            ];

            if ($this->sale_lib->get_customer() != -1) {
                $selected_filters = ['selected_customer'];
                $data['customer_selected'] = true;
            } else {
                $data['customer_selected'] = false;
                $selected_filters = [];
            }
            $data['selected_filters'] = $selected_filters;

            echo view('sales/manage', $data);
        }
    }

    /**
     * @param int $row_id
     * @return void
     */
    public function getRow(int $row_id): void
    {
        $sale_info = $this->sale->get_info($row_id)->getRow();
        $data_row = get_sale_data_row($sale_info);

        echo json_encode($data_row);
    }

    /**
     * @return void
     */
    public function getSearch(): void
    {
        $search = $this->request->getGet('search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $limit = $this->request->getGet('limit', FILTER_SANITIZE_NUMBER_INT);
        $offset = $this->request->getGet('offset', FILTER_SANITIZE_NUMBER_INT);
        $sort = $this->sanitizeSortColumn(sales_headers(), $this->request->getGet('sort', FILTER_SANITIZE_FULL_SPECIAL_CHARS), 'sale_id');
        $order = $this->request->getGet('order', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $filters = [
            'sale_type'         => 'all',
            'location_id'       => 'all',
            'start_date'        => $this->request->getGet('start_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'end_date'          => $this->request->getGet('end_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'only_cash'         => false,
            'only_due'          => false,
            'only_check'        => false,
            'selected_customer' => false,
            'only_creditcard'   => false,
            'only_invoices'     => $this->config['invoice_enable'] && $this->request->getGet('only_invoices', FILTER_SANITIZE_NUMBER_INT),
            'is_valid_receipt'  => $this->sale->is_valid_receipt($search)
        ];

        // Check if any filter is set in the multiselect dropdown
        $request_filters = array_fill_keys($this->request->getGet('filters', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [], true);
        $filters = array_merge($filters, $request_filters);

        $sales = $this->sale->search($search, $filters, $limit, $offset, $sort, $order);
        $total_rows = $this->sale->get_found_rows($search, $filters);
        $payments = $this->sale->get_payments_summary($search, $filters);
        $payment_summary = get_sales_manage_payments_summary($payments);

        $data_rows = [];
        foreach ($sales->getResult() as $sale) {
            $data_rows[] = get_sale_data_row($sale);
        }

        if ($total_rows > 0) {
            $data_rows[] = get_sale_data_last_row($sales);
        }

        echo json_encode(['total' => $total_rows, 'rows' => $data_rows, 'payment_summary' => $payment_summary]);
    }

    /**
     * Gets search suggestions for an item or item kit. Used in app/Views/sales/register.php.
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function getItemSearch(): void
    {
        $suggestions = [];
        $receipt = $search = $this->request->getGet('term') != ''
            ? $this->request->getGet('term')
            : null;

        if ($this->sale_lib->get_mode() == 'return' && $this->sale->is_valid_receipt($receipt)) {
            // If a valid receipt or invoice was found the search term will be replaced with a receipt number (POS #)
            $suggestions[] = $receipt;
        }
        $suggestions = array_merge($suggestions, $this->item->get_search_suggestions($search, ['search_custom' => false, 'is_deleted' => false], true));
        $suggestions = array_merge($suggestions, $this->item_kit->get_search_suggestions($search));

        echo json_encode($suggestions);
    }
   /**
 * Fetch items by category (supports GET and POST)
 *
 * @return \CodeIgniter\HTTP\Response
 */
public function get_items_by_category()
{
    $category = trim($this->request->getPost('category') ?? $this->request->getGet('category'));

    if (!$category) {
        return $this->response->setJSON([]);
    }

    // Fetch items from model
    $items = $this->item->get_items_by_category($category);

    $result = [];
    foreach ($items as $item) {
        $result[] = [
            'item_id'         => $item['item_id'],
            'barcode'         => $item['item_number'], // assuming `item_number` is barcode
            'name'            => $item['name'],
            'category'        => $item['category'],
            'cost_price'      => $item['cost_price'],
            'unit_price'      => $item['unit_price'],
            'quantity'        => $item['quantity'],
            'tax_category_id' => $item['tax_category_id'], // can map to % if needed
        ];
    }

    return $this->response->setJSON($result);
}


    /**
     * @return void
     */
    public function suggest_search(): void
    {
        $search = $this->request->getPost('term') != ''
            ? $this->request->getPost('term')
            : null;

        $suggestions = $this->sale->get_search_suggestions($search);

        echo json_encode($suggestions);
    }

    /**
     * Set a given customer. Used in app/Views/sales/register.php.
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postSelectCustomer(): void
    {
        $customer_id = (int)$this->request->getPost('customer', FILTER_SANITIZE_NUMBER_INT);
        if ($this->customer->exists($customer_id)) {
            $this->sale_lib->set_customer($customer_id);
            $discount = $this->customer->get_info($customer_id)->discount;
            $discount_type = $this->customer->get_info($customer_id)->discount_type;

            // Apply customer default discount to items that have 0 discount
            if ($discount != '') {
                $this->sale_lib->apply_customer_discount($discount, $discount_type);
            }
        }

        $this->_reload();
    }

    /**
     * Changes the sale mode in the register to carry out different types of sales
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postChangeMode(): void
    {
        $mode = $this->request->getPost('mode', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $this->sale_lib->set_mode($mode);

        if ($mode == 'sale') {
            $this->sale_lib->set_sale_type(SALE_TYPE_POS);
        } elseif ($mode == 'sale_quote') {
            $this->sale_lib->set_sale_type(SALE_TYPE_QUOTE);
        } elseif ($mode == 'sale_work_order') {
            $this->sale_lib->set_sale_type(SALE_TYPE_WORK_ORDER);
        } elseif ($mode == 'sale_invoice') {
            $this->sale_lib->set_sale_type(SALE_TYPE_INVOICE);
        } else {
            $this->sale_lib->set_sale_type(SALE_TYPE_RETURN);
        }

        if ($this->config['dinner_table_enable']) {
            $occupied_dinner_table = $this->request->getPost('dinner_table', FILTER_SANITIZE_NUMBER_INT);
            $released_dinner_table = $this->sale_lib->get_dinner_table();
            $occupied = $this->dinner_table->is_occupied($released_dinner_table);

            if ($occupied && ($occupied_dinner_table != $released_dinner_table)) {
                $this->dinner_table->swap_tables($released_dinner_table, $occupied_dinner_table);
            }

            $this->sale_lib->set_dinner_table($occupied_dinner_table);
        }
        $waiter_id = $this->request->getPost('waiter');
        if ($waiter_id !== null) {
            $this->sale_lib->set_waiter($waiter_id);
        }
        $stock_location = $this->request->getPost('stock_location', FILTER_SANITIZE_NUMBER_INT);

        if (!$stock_location || $stock_location == $this->sale_lib->get_sale_location()) {
            // TODO: The code below was removed in 2017 by @steveireland. We either need to reinstate some of it or remove this entire if block but we can't leave an empty if block
            //            $dinner_table = $this->request->getPost('dinner_table');
            //            $this->sale_lib->set_dinner_table($dinner_table);
        } elseif ($this->stock_location->is_allowed_location($stock_location, 'sales')) {
            $this->sale_lib->set_sale_location($stock_location);
        }

        $this->sale_lib->empty_payments();
        
        

        $this->_reload();
    }
    /**
     * @param int $sale_type
     * @return void
     */
    public function change_register_mode(int $sale_type): void
    {
        $mode = match ($sale_type) {
            SALE_TYPE_QUOTE => 'sale_quote',
            SALE_TYPE_WORK_ORDER => 'sale_work_order',
            SALE_TYPE_INVOICE => 'sale_invoice',
            SALE_TYPE_RETURN => 'return',
            default => 'sale' // SALE_TYPE_POS
        };

        $this->sale_lib->set_mode($mode);
    }


    /**
     * Sets the sales comment. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postSetComment(): void
    {
        $this->sale_lib->set_comment($this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    /**
     * Sets the invoice number. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postSetInvoiceNumber(): void
    {
        $this->sale_lib->set_invoice_number($this->request->getPost('sales_invoice_number', FILTER_SANITIZE_NUMBER_INT));
    }

    /**
     * @return void
     */
    public function postSetPaymentType(): void    // TODO: This function does not appear to be called anywhere in the code.
    {
        $this->sale_lib->set_payment_type($this->request->getPost('selected_payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
        $this->_reload();    // TODO: Hungarian notation.
    }

    /**
     * Sets PrintAfterSale flag. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postSetPrintAfterSale(): void
    {
        $this->sale_lib->set_print_after_sale($this->request->getPost('sales_print_after_sale') != 'false');
    }

    /**
     * Sets the flag to include prices in the work order. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postSetPriceWorkOrders(): void
    {
        $price_work_orders = parse_decimals($this->request->getPost('price_work_orders'));
        $this->sale_lib->set_price_work_orders($price_work_orders);
    }

    /**
     * Sets the flag to email receipt to the customer. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postSetEmailReceipt(): void
    {
        $this->sale_lib->set_email_receipt($this->request->getPost('email_receipt', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    /**
     * Add a payment to the sale. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
   
    public function postAddPayment(): void
{
    $data = [];
    $giftcard = model(Giftcard::class);
    $payment_type = $this->request->getPost('payment_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $amount_tendered = $this->request->getPost('amount_tendered');

    // FIX: Check if amount_tendered is null or empty before validation
    if ($amount_tendered === null || $amount_tendered === '') {
        // If payment type is selected but no amount entered, just reload without validation
        $this->_reload($data);
        return;
    }

    if ($payment_type !== lang('Sales.giftcard')) {
        $rules = ['amount_tendered' => 'trim|required|decimal_locale',];
        $messages = ['amount_tendered' => lang('Sales.must_enter_numeric')];
    } else {
        $rules = ['amount_tendered' => 'trim|required',];
        $messages = ['amount_tendered' => lang('Sales.must_enter_numeric_giftcard')];
    }

    if (!$this->validate($rules, $messages)) {
        $data['error'] = $payment_type === lang('Sales.giftcard')
            ? lang('Sales.must_enter_numeric_giftcard')
            : lang('Sales.must_enter_numeric');
    } else {
        if ($payment_type === lang('Sales.giftcard')) {
            // In the case of giftcard payment the register input amount_tendered becomes the giftcard number
            $amount_tendered = parse_decimals($this->request->getPost('amount_tendered'));
            $giftcard_num = $amount_tendered;

            $payments = $this->sale_lib->get_payments();
            $payment_type = $payment_type . ':' . $giftcard_num;
            $current_payments_with_giftcard = isset($payments[$payment_type]) ? $payments[$payment_type]['payment_amount'] : 0;
            $cur_giftcard_value = $giftcard->get_giftcard_value($giftcard_num);
            $cur_giftcard_customer = $giftcard->get_giftcard_customer($giftcard_num);
            $customer_id = $this->sale_lib->get_customer();

            if (isset($cur_giftcard_customer) && $cur_giftcard_customer != $customer_id) {
                $data['error'] = lang('Giftcards.cannot_use', [$giftcard_num]);
            } elseif (($cur_giftcard_value - $current_payments_with_giftcard) <= 0 && $this->sale_lib->get_mode() === 'sale') {
                $data['error'] = lang('Giftcards.remaining_balance', [$giftcard_num, $cur_giftcard_value]);
            } else {
                $new_giftcard_value = $giftcard->get_giftcard_value($giftcard_num) - $this->sale_lib->get_amount_due();
                $new_giftcard_value = max($new_giftcard_value, 0);
                $this->sale_lib->set_giftcard_remainder($new_giftcard_value);
                $new_giftcard_value = str_replace('$', '\$', to_currency($new_giftcard_value));
                $data['warning'] = lang('Giftcards.remaining_balance', [$giftcard_num, $new_giftcard_value]);
                $amount_tendered = min($this->sale_lib->get_amount_due(), $giftcard->get_giftcard_value($giftcard_num));

                $this->sale_lib->add_payment($payment_type, $amount_tendered);
            }
        } elseif ($payment_type === lang('Sales.rewards')) {
            $customer_id = $this->sale_lib->get_customer();
            $package_id = $this->customer->get_info($customer_id)->package_id;
            if (!empty($package_id)) {
                $package_name = $this->customer_rewards->get_name($package_id);    // TODO: this variable is never used.
                $points = $this->customer->get_info($customer_id)->points;
                $points = ($points == null ? 0 : $points);

                $payments = $this->sale_lib->get_payments();
                $current_payments_with_rewards = isset($payments[$payment_type]) ? $payments[$payment_type]['payment_amount'] : 0;
                $cur_rewards_value = $points;

                if (($cur_rewards_value - $current_payments_with_rewards) <= 0) {
                    $data['error'] = lang('Sales.rewards_remaining_balance') . to_currency($cur_rewards_value);
                } else {
                    $new_reward_value = $points - $this->sale_lib->get_amount_due();
                    $new_reward_value = max($new_reward_value, 0);
                    $this->sale_lib->set_rewards_remainder($new_reward_value);
                    $new_reward_value = str_replace('$', '\$', to_currency($new_reward_value));
                    $data['warning'] = lang('Sales.rewards_remaining_balance') . $new_reward_value;
                    $amount_tendered = min($this->sale_lib->get_amount_due(), $points);

                    $this->sale_lib->add_payment($payment_type, $amount_tendered);
                }
            }
        } elseif ($payment_type === lang('Sales.cash')) {
            $amount_due = $this->sale_lib->get_total();
            $sales_total = $this->sale_lib->get_total(false);
            $amount_tendered = parse_decimals($this->request->getPost('amount_tendered'));
            $this->sale_lib->add_payment($payment_type, $amount_tendered);
            $cash_adjustment_amount = $amount_due - $sales_total;
            if ($cash_adjustment_amount <> 0) {
                $this->session->set('cash_mode', CASH_MODE_TRUE);
                $this->sale_lib->add_payment(lang('Sales.cash_adjustment'), $cash_adjustment_amount, CASH_ADJUSTMENT_TRUE);
            }
        } else {
            $amount_tendered = parse_decimals($this->request->getPost('amount_tendered'));
            $this->sale_lib->add_payment($payment_type, $amount_tendered);
        }
    }
   // Handle AJAX request
    if ($this->request->isAJAX()) {
        $this->_ajaxReload($data);
        return;
    }



    $this->_reload($data);
}
/**
 * Helper method to handle AJAX reloads - returns JSON with updated data
 */
private function _ajaxReload($data = []): void
{
    // Just return the essential data - let frontend handle the updates
    $response = [
        'success' => !isset($data['error']),
        'cart' => $this->sale_lib->get_cart(),
        'payments' => $this->sale_lib->get_payments(),
        'amount_due' => $this->sale_lib->get_amount_due(),
        'total' => $this->sale_lib->get_total(),
        'payments_cover_total' => $this->sale_lib->get_payments_total() >= $this->sale_lib->get_total(),
        'reload_needed' => true // Signal frontend to do a simple reload
    ];

    if (isset($data['error'])) {
        $response['error'] = $data['error'];
    }
    if (isset($data['warning'])) {
        $response['warning'] = $data['warning'];
    }

    $this->response->setJSON($response);
    return;
}
    /**
     * Multiple Payments. Used in app/Views/sales/register.php
     *
     * @param string $payment_id
     * @return void
     * @noinspection PhpUnused
     */
    public function getDeletePayment(string $encoded_payment_id): void
{
    // Decode the URL-safe base64 encoded payment ID
    $payment_id = base64_decode(strtr($encoded_payment_id, '-_.', '+/='));
    
    $this->sale_lib->delete_payment($payment_id);
    $this->_reload();    // TODO: Hungarian notation
}

    /**
     * Add an item to the sale. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postAdd(): void
    {
        if ($this->request->getPost('reload_cart')) {
        $this->_reload(); // only refresh cart HTML
        return;          // stop the rest of postAdd() from running
    }
        $data = [];

        $discount = $this->config['default_sales_discount'];
        $discount_type = $this->config['default_sales_discount_type'];

        // Check if any discount is assigned to the selected customer
        $customer_id = $this->sale_lib->get_customer();
        if ($customer_id != NEW_ENTRY) {
            // Load the customer discount if any
            $customer_discount = $this->customer->get_info($customer_id)->discount;
            $customer_discount_type = $this->customer->get_info($customer_id)->discount_type;
            if ($customer_discount != '') {
                $discount = $customer_discount;
                $discount_type = $customer_discount_type;
            }
        }

        $item_id_or_number_or_item_kit_or_receipt = $this->request->getPost('item', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $this->token_lib->parse_barcode($quantity, $price, $item_id_or_number_or_item_kit_or_receipt);
        $mode = $this->sale_lib->get_mode();
        $quantity = ($mode == 'return') ? -$quantity : $quantity;
        $item_location = $this->sale_lib->get_sale_location();

        if ($mode == 'return' && $this->sale->is_valid_receipt($item_id_or_number_or_item_kit_or_receipt)) {
            $this->sale_lib->return_entire_sale($item_id_or_number_or_item_kit_or_receipt);
        } elseif ($this->item_kit->is_valid_item_kit($item_id_or_number_or_item_kit_or_receipt)) {
            // Add kit item to order if one is assigned
            $pieces = explode(' ', $item_id_or_number_or_item_kit_or_receipt);

            $item_kit_id = (count($pieces) > 1) ? $pieces[1] : $item_id_or_number_or_item_kit_or_receipt;
            $item_kit_info = $this->item_kit->get_info($item_kit_id);
            $kit_item_id = $item_kit_info->kit_item_id;
            $kit_price_option = $item_kit_info->price_option;
            $kit_print_option = $item_kit_info->print_option; // 0-all, 1-priced, 2-kit-only

            if ($discount_type == $item_kit_info->kit_discount_type) {
                if ($item_kit_info->kit_discount > $discount) {
                    $discount = $item_kit_info->kit_discount;
                }
            } else {
                $discount = $item_kit_info->kit_discount;
                $discount_type = $item_kit_info->kit_discount_type;
            }

            $print_option = PRINT_ALL; // Always include in list of items on invoice // TODO: This variable is never used in the code

            if (!empty($kit_item_id)) {
                if (!$this->sale_lib->add_item($kit_item_id, $item_location, $quantity, $discount, $discount_type, PRICE_MODE_KIT, $kit_price_option, $kit_print_option, $price)) {
                    $data['error'] = lang('Sales.unable_to_add_item');
                } else {
                    $data['warning'] = $this->sale_lib->out_of_stock($item_kit_id, $item_location);
                }
            }

            // Add item kit items to order
            $stock_warning = null;
            if (!$this->sale_lib->add_item_kit($item_id_or_number_or_item_kit_or_receipt, $item_location, $discount, $discount_type, $kit_price_option, $kit_print_option, $stock_warning)) {
                $data['error'] = lang('Sales.unable_to_add_item');
            } elseif ($stock_warning != null) {
                $data['warning'] = $stock_warning;
            }
        } else {
            if ($item_id_or_number_or_item_kit_or_receipt == '' || !$this->sale_lib->add_item($item_id_or_number_or_item_kit_or_receipt, $item_location, $quantity, $discount, $discount_type, PRICE_MODE_STANDARD, null, null, $price)) {
                $data['error'] = lang('Sales.unable_to_add_item');
            } else {
                $data['warning'] = $this->sale_lib->out_of_stock($item_id_or_number_or_item_kit_or_receipt, $item_location);
            }
        }
// --- Only run if no errors ---
    if (empty($data['error'])) {

        // 1. Save waiter from POST
        $waiter_id = $this->request->getPost('waiter');
        if ($waiter_id !== null) {
            $this->sale_lib->set_waiter($waiter_id);
        }
// ✅ Get the dinner table ID directly from session
$dinner_table_id = $this->sale_lib->get_dinner_table();

        // 2. Get employee fallback
        $employee_id = $this->sale_lib->get_waiter() ?? $this->employee->get_logged_in_employee_info()->person_id;
        if (!$employee_id) {
            $data['error'] = 'No valid employee found';
            $this->_reload($data);
            return;
        }

        // 3. Get current order and cart BEFORE adding new item
        $order_id = $this->sale_lib->get_current_order_id();
        $cart_items = $this->sale_lib->get_cart();

        // Create new order only if cart is empty or no order ID exists
        if (!$order_id || empty($cart_items)) {
            $order_data = [
                'sale_id'     => null,
                'customer_id' => $this->sale_lib->get_customer() != -1 ? $this->sale_lib->get_customer() : null,
                'employee_id' => $employee_id,
               'table_no'    => $dinner_table_id ?? null,

                'status'      => 'pending',
                'order_type'  => 'dine_in',
                'notes'       => $this->sale_lib->get_comment(),
                'order_time'  => date('Y-m-d H:i:s')
            ];

            try {
                $order_model = model(\App\Models\Order::class);
                $order_model->insert($order_data);
                $order_id = $order_model->getInsertID();

                if (!$order_id) {
                    throw new \Exception("Order insert returned null ID");
                }

                $this->sale_lib->set_current_order_id($order_id);
            } catch (\Exception $e) {
                log_message('error', 'Order creation failed: ' . $e->getMessage());
                $data['error'] = 'Failed to create order';
                $this->_reload($data);
                return;
            }
        }

        // 4. Save entire cart to order_items
        if ($order_id) {
            $this->sale_lib->sync_order_items($order_id);
        } else {
            log_message('error', 'Invalid order_id, cannot save cart items');
            $data['error'] = 'Invalid order ID';
            $this->_reload($data);
            return;
        }
    }


        $this->_reload($data);
    }

    /**
     * Edit an item in the sale. Used in app/Views/sales/register.php
     *
     * @param string $line
     * @return void
     * @noinspection PhpUnused
     */
   public function postEditItem(string $line = null): void
{
    $data = [];

    // Flag if applying discount to all items
    $all_discount = $this->request->getPost('all_items_discount');

    // Fetch cart
    $cart = $this->sale_lib->get_cart();

    // Function to calculate new discount
$calculate_final_discount = function ($item, $new_discount_value, $new_discount_type, $stack = true) {
    $existing_discount = $item['discount'] ?? 0;
    $existing_type     = $item['discount_type'] ?? null;
    $base_price        = $item['price'];

    if (!$stack) {
        // For cart individual edits: replace previous discount
        return $new_discount_value;
    }

    // Case 1: both are % (combine multiplicatively)
    if ($existing_type === 'percent' && $new_discount_type) {
        $price_after_existing = $base_price * (1 - $existing_discount / 100);
        $total_discount       = $base_price - ($price_after_existing * (1 - $new_discount_value / 100));
        return $total_discount;
    }

    // Case 2: existing Rs, new %
    if ($existing_type === 'fixed' && $new_discount_type) {
        $price_after_existing = $base_price - $existing_discount;
        $extra_discount       = $price_after_existing * ($new_discount_value / 100);
        return $existing_discount + $extra_discount;
    }

    // Case 3: existing %, new Rs
    if ($existing_type === 'percent' && !$new_discount_type) {
        $discount_from_percent = $base_price * ($existing_discount / 100);
        return $discount_from_percent + $new_discount_value;
    }

    // Case 4: both Rs (just add them)
    if (!$existing_type && !$new_discount_type) {
        return $existing_discount + $new_discount_value;
    }

    // Fallback (if type missing)
    return $existing_discount + $new_discount_value;
};


    // If applying discount to all items (Payment / Apply All)
    if ($all_discount) {
        $discount_type = $this->request->getPost('discount_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $discount_value = $discount_type
            ? parse_quantity($this->request->getPost('discount')) // percentage
            : parse_decimals($this->request->getPost('discount')); // fixed amount

        foreach ($cart as $line_index => $item) {
            $final_discount = $calculate_final_discount($item, $discount_value, $discount_type, true);

            $this->sale_lib->edit_item(
                $line_index,
                $item['description'],
                $item['serialnumber'] ?? '',
                $item['quantity'],
                $final_discount,
                $discount_type,
                $item['price'],
                $item['discounted_total'] ?? null
            );
        }

        $this->sale_lib->empty_payments();
        
        $this->_reload($data);
        return;
    }

    // Normal single item edit (Cart individual)
    $rules = [
    'price'    => 'if_exist|trim|required|decimal_locale',
    'quantity' => 'if_exist|trim|required|decimal_locale',
    'discount' => 'if_exist|permit_empty|decimal_locale',
];


    if (!$this->validate($rules)) {
        $data['error'] = lang('Sales.error_editing_item');
        $this->_reload($data);
        return;
    }

    $description = $this->request->getPost('description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $serialnumber = $this->request->getPost('serialnumber', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $price = parse_decimals($this->request->getPost('price'));
    $quantity = parse_decimals($this->request->getPost('quantity'));
    $discount_type = $this->request->getPost('discount_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $discount_input = $discount_type
        ? parse_quantity($this->request->getPost('discount')) // %
        : parse_decimals($this->request->getPost('discount')); // Rs

    $cart_item = $cart[$line] ?? null;

    if ($cart_item) {
        // For cart individual edits, stack=false -> replace previous discount
        $discount = $calculate_final_discount($cart_item, $discount_input, $discount_type, false);
    } else {
        $discount = $discount_input;
    }

    $item_location = $this->request->getPost('location', FILTER_SANITIZE_NUMBER_INT);
    $discounted_total = $this->request->getPost('discounted_total') != ''
        ? parse_decimals($this->request->getPost('discounted_total') ?? '')
        : null;

    $this->sale_lib->edit_item($line, $description, $serialnumber, $quantity, $discount, $discount_type, $price, $discounted_total);
    $this->sale_lib->empty_payments();
    $data['warning'] = $this->sale_lib->out_of_stock($this->sale_lib->get_item_id($line), $item_location);

    // Sync with DB
    $order_id = $this->sale_lib->get_current_order_id();
    $item_id  = $this->sale_lib->get_item_id($line);

    if ($order_id && $item_id && $cart_item) {
        $orderItemModel = model(\App\Models\OrderItem::class);
        $quantity    = (float) ($cart_item['quantity'] ?? 0);
        $price       = (float) ($cart_item['price'] ?? 0);
        $description = $cart_item['name'] ?? '';

        if ($quantity > 0) {
            $orderItemModel
                ->where('order_id', $order_id)
                ->where('item_id', $item_id)
                ->set('quantity', $quantity)
                ->set('unit_price', $price)
                ->set('discount_percent', $discount)
                ->set('description', $description)
                ->update();
        } else {
            $orderItemModel
                ->where('order_id', $order_id)
                ->where('item_id', $item_id)
                ->delete();
        }
    }

    $this->_reload($data);
}


    /**
     * Deletes an item specified in the parameter from the shopping cart. Used in app/Views/sales/register.php
     *
     * @param int $item_id
     * @return void
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public function getDeleteItem(int $line): void
{
    $order_id = $this->sale_lib->get_current_order_id();

    if ($order_id !== null) {
        // Get the actual item_id for this line in the cart
        $item_id = $this->sale_lib->get_item_id($line);

        if ($item_id !== null) {
            $orderItemModel = model(\App\Models\OrderItem::class);
            $orderItemModel
                ->where('order_id', $order_id)
                ->where('item_id', $item_id)
                ->delete();
        }
    }

    // Remove from cart/session
    $this->sale_lib->delete_item($line);

    // Clear payments
    $this->sale_lib->empty_payments();

    // If cart is empty, reset current order
    $cart_items = $this->sale_lib->get_cart();
    if (empty($cart_items)) {
        $this->sale_lib->set_current_order_id(null);
    }

    // Reload UI
    $this->_reload();
}


    /**
     * Remove the current customer from the sale. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function getRemoveCustomer(): void
    {
        $this->sale_lib->clear_giftcard_remainder();
        $this->sale_lib->clear_rewards_remainder();
        $this->sale_lib->delete_payment(lang('Sales.rewards'));
        $this->sale_lib->clear_invoice_number();
        $this->sale_lib->clear_quote_number();
        $this->sale_lib->remove_customer();

        $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * Complete and finalize a sale. Used in app/Views/sales/register.php
     *
     * @return void
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public function postComplete(): void
{
    // TODO: this function is huge. Probably should be refactored.
    $sale_id = $this->sale_lib->get_sale_id();
    $data = [];
    
    // Get the dinner table ID directly from the session
     $dinner_table_id = $this->sale_lib->get_dinner_table();
    $data['dinner_table_number'] = $dinner_table_id;

    // ADDED: Fetch the waiter's name and add it to the data array for the receipt view
    $waiter_id = $this->sale_lib->get_waiter();
    $data['waiter_name'] = null;
    if (!empty($waiter_id)) {
        $waiter_info = $this->employee->get_info($waiter_id);
        if ($waiter_info) {
            $data['waiter_name'] = $waiter_info->first_name . ' ' . $waiter_info->last_name;
        }
    }

    $data['cart'] = $this->sale_lib->get_cart();

    $data['include_hsn'] = (bool)$this->config['include_hsn'];
    $__time = time();
    $data['transaction_time'] = to_datetime($__time);
    $data['transaction_date'] = to_date($__time);
    $data['show_stock_locations'] = $this->stock_location->show_locations('sales');
    $data['comments'] = $this->sale_lib->get_comment();
    $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
    $employee_info = $this->employee->get_info($employee_id);
    $data['employee'] = $employee_info->first_name . ' ' . mb_substr($employee_info->last_name, 0, 1);

    $data['company_info'] = implode("\n", [$this->config['address'], $this->config['phone']]);

    if ($this->config['account_number']) {
        $data['company_info'] .= "\n" . lang('Sales.account_number') . ": " . $this->config['account_number'];
    }

    if ($this->config['tax_id'] != '') {
        $data['company_info'] .= "\n" . lang('Sales.tax_id') . ": " . $this->config['tax_id'];
    }

    $data['invoice_number_enabled'] = $this->sale_lib->is_invoice_mode();
    $data['cur_giftcard_value'] = $this->sale_lib->get_giftcard_remainder();
    $data['cur_rewards_value'] = $this->sale_lib->get_rewards_remainder();
    $data['print_after_sale'] = $this->session->get('sales_print_after_sale');
    $data['price_work_orders'] = $this->sale_lib->is_price_work_orders();
    $data['email_receipt'] = $this->sale_lib->is_email_receipt();
    $customer_id = $this->sale_lib->get_customer();
    $invoice_number = $this->sale_lib->get_invoice_number();
    $data["invoice_number"] = $invoice_number;
    $work_order_number = $this->sale_lib->get_work_order_number();
    $data["work_order_number"] = $work_order_number;
    $quote_number = $this->sale_lib->get_quote_number();
    $data["quote_number"] = $quote_number;
    $customer_info = $this->_load_customer_data($customer_id, $data);

    if ($customer_info != null) {
        $data["customer_comments"] = $customer_info->comments;
        $data['tax_id'] = $customer_info->tax_id;
    }
    $tax_details = $this->tax_lib->get_taxes($data['cart']);
    $data['taxes'] = $tax_details[0];
    $data['discount'] = $this->sale_lib->get_discount();
    $data['payments'] = $this->sale_lib->get_payments();

    // Returns 'subtotal', 'total', 'cash_total', 'payment_total', 'amount_due', 'cash_amount_due', 'payments_cover_total'
    $totals = $this->sale_lib->get_totals($tax_details[0]);
    $data['subtotal'] = $totals['subtotal'];
    $data['total'] = $totals['total'];
    $data['payments_total'] = $totals['payment_total'];
    $data['payments_cover_total'] = $totals['payments_cover_total'];
    $data['cash_rounding'] = $this->session->get('cash_rounding');
    $data['cash_mode'] = $this->session->get('cash_mode');
    $data['prediscount_subtotal'] = $totals['prediscount_subtotal'];
    $data['cash_total'] = $totals['cash_total'];
    $data['non_cash_total'] = $totals['total'];
    $data['cash_amount_due'] = $totals['cash_amount_due'];
    $data['non_cash_amount_due'] = $totals['amount_due'];

    if ($data['cash_mode']) {
        $data['amount_due'] = $totals['cash_amount_due'];
    } else {
        $data['amount_due'] = $totals['amount_due'];
    }

    $data['amount_change'] = $data['amount_due'] * -1;

    if ($data['amount_change'] > 0) {
        // Save cash refund to the cash payment transaction if found, if not then add as new Cash transaction

        if (array_key_exists(lang('Sales.cash'), $data['payments'])) {
            $data['payments'][lang('Sales.cash')]['cash_refund'] = $data['amount_change'];
        } else {
            $payment = [
                lang('Sales.cash') => [
                    'payment_type' => lang('Sales.cash'),
                    'payment_amount' => 0,
                    'cash_refund' => $data['amount_change']
                ]
            ];

            $data['payments'] += $payment;
        }
    }

    $data['print_price_info'] = true;

    if ($this->sale_lib->is_invoice_mode()) {
        $invoice_format = $this->config['sales_invoice_format'];

        // Generate final invoice number (if using the invoice in sales by receipt mode then the invoice number can be manually entered or altered in some way
        if (!empty($invoice_format) && $invoice_number == null) {
            // The user can retain the default encoded format or can manually override it. It still passes through the rendering step.
            $invoice_number = $this->token_lib->render($invoice_format);
        }

        if ($sale_id == NEW_ENTRY && $this->sale->check_invoice_number_exists($invoice_number)) {
            $data['error'] = lang('Sales.invoice_number_duplicate', [$invoice_number]);
            $this->_reload($data);
        } else {
            $data['invoice_number'] = $invoice_number;
            $data['sale_status'] = COMPLETED;
            $sale_type = SALE_TYPE_INVOICE;

            // The PHP file name is the same as the invoice_type key
            $invoice_view = $this->config['invoice_type'];

            // Save the data to the sales table
            $data['sale_id_num'] = $this->sale->save_value($sale_id, $data['sale_status'], $data['cart'], $customer_id, $employee_id, $data['comments'], $invoice_number, $work_order_number, $quote_number, $sale_type, $data['payments'], $dinner_table_id, $tax_details);
            $data['sale_id'] = 'InvoiceNumber# ' . $data['sale_id_num'];
            // ADD ORDER UPDATE HERE
$order_id = $this->sale_lib->get_current_order_id();
if ($order_id) {
    $order_model = model(\App\Models\Order::class);
    $order_model->update($order_id, [
        'status' => 'completed',
        'sale_id' => $data['sale_id_num']
    ]);
    $this->sale_lib->set_current_order_id(null);
}


            // Resort and filter cart lines for printing
            $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);

            if ($data['sale_id_num'] == NEW_ENTRY) {
                $data['error_message'] = lang('Sales.transaction_failed');
            } else {
                $pure_sale_id = $this->extractNumericSaleId($data['sale_id']);
$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($pure_sale_id);
                echo view('sales/' . $invoice_view, $data);
                $this->sale_lib->clear_all();
            }
        }
    } elseif ($this->sale_lib->is_work_order_mode()) {
        if (!($data['price_work_orders'] == 1)) {
            $data['print_price_info'] = false;
        }
        $data['sales_work_order'] = lang('Sales.work_order');
        $data['work_order_number_label'] = lang('Sales.work_order_number');
        if ($work_order_number == null) {
            // Generate work order number
            $work_order_format = $this->config['work_order_format'];
            $work_order_number = $this->token_lib->render($work_order_format);
        }

        if ($sale_id == NEW_ENTRY && $this->sale->check_work_order_number_exists($work_order_number)) {
            $data['error'] = lang('Sales.work_order_number_duplicate');
            $this->_reload($data);
        } else {
            $data['work_order_number'] = $work_order_number;
            $data['sale_status'] = SUSPENDED;
            $sale_type = SALE_TYPE_WORK_ORDER;

            $data['sale_id_num'] = $this->sale->save_value($sale_id, $data['sale_status'], $data['cart'], $customer_id, $employee_id, $data['comments'], $invoice_number, $work_order_number, $quote_number, $sale_type, $data['payments'], $dinner_table_id, $tax_details);
            $this->sale_lib->set_suspended_id($data['sale_id_num']);
            // ADD ORDER UPDATE HERE
$order_id = $this->sale_lib->get_current_order_id();
if ($order_id) {
    $order_model = model(\App\Models\Order::class);
    $order_model->update($order_id, [
        'status' => 'completed',
        'sale_id' => $data['sale_id_num']
    ]);
    $this->sale_lib->set_current_order_id(null);
}


            $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);
            $data['barcode'] = null;

            echo view('sales/work_order', $data);
            $this->sale_lib->clear_mode();
            $this->sale_lib->clear_all();
        }
    } elseif ($this->sale_lib->is_quote_mode()) {
        $data['sales_quote'] = lang('Sales.quote');
        $data['quote_number_label'] = lang('Sales.quote_number');

        if ($quote_number == null) {
            // Generate quote number
            $quote_format = $this->config['sales_quote_format'];
            $quote_number = $this->token_lib->render($quote_format);
        }

        if ($sale_id == NEW_ENTRY && $this->sale->check_quote_number_exists($quote_number)) {
            $data['error'] = lang('Sales.quote_number_duplicate');
            $this->_reload($data);
        } else {
            $data['quote_number'] = $quote_number;
            $data['sale_status'] = SUSPENDED;
            $sale_type = SALE_TYPE_QUOTE;

            $data['sale_id_num'] = $this->sale->save_value($sale_id, $data['sale_status'], $data['cart'], $customer_id, $employee_id, $data['comments'], $invoice_number, $work_order_number, $quote_number, $sale_type, $data['payments'], $dinner_table_id, $tax_details);
            $this->sale_lib->set_suspended_id($data['sale_id_num']);
            // ADD ORDER UPDATE HERE
$order_id = $this->sale_lib->get_current_order_id();
if ($order_id) {
    $order_model = model(\App\Models\Order::class);
    $order_model->update($order_id, [
        'status' => 'completed',
        'sale_id' => $data['sale_id_num']
    ]);
    $this->sale_lib->set_current_order_id(null);
}


            $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);
            $data['barcode'] = null;

            echo view('sales/quote', $data);
            $this->sale_lib->clear_mode();
            $this->sale_lib->clear_all();
        }
    } else {
        // Save the data to the sales table
        $data['sale_status'] = COMPLETED;
        if ($this->sale_lib->is_return_mode()) {
            $sale_type = SALE_TYPE_RETURN;
        } else {
            $sale_type = SALE_TYPE_POS;
        }

        $data['sale_id_num'] = $this->sale->save_value($sale_id, $data['sale_status'], $data['cart'], $customer_id, $employee_id, $data['comments'], $invoice_number, $work_order_number, $quote_number, $sale_type, $data['payments'], $dinner_table_id, $tax_details);

        $data['sale_id'] = 'InvoiceNumber# ' . $data['sale_id_num'];
        // ADD ORDER UPDATE HERE
$order_id = $this->sale_lib->get_current_order_id();
if ($order_id) {
    $order_model = model(\App\Models\Order::class);
    $order_model->update($order_id, [
        'status' => 'completed',
        'sale_id' => $data['sale_id_num']
    ]);
    $this->sale_lib->set_current_order_id(null);
}


        $data['cart'] = $this->sale_lib->sort_and_filter_cart($data['cart']);

        if ($data['sale_id_num'] == NEW_ENTRY) {
            $data['error_message'] = lang('Sales.transaction_failed');
        } else {
            $pure_sale_id = $this->extractNumericSaleId($data['sale_id']);
$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($pure_sale_id);
            echo view('sales/receipt', $data);
            $this->sale_lib->clear_all();
        }
    }
}
    /**
     * Email PDF invoice to customer. Used in app/Views/sales/form.php, invoice.php, quote.php, tax_invoice.php and work_order.php
     *
     * @param int $sale_id
     * @param string $type
     * @return bool
     * @noinspection PhpUnused
     */
    public function getSendPdf(int $sale_id, string $type = 'invoice'): bool
    {
        $sale_data = $this->_load_sale_data($sale_id);

        $result = false;
        $message = lang('Sales.invoice_no_email');

        if (!empty($sale_data['customer_email'])) {
            $to = $sale_data['customer_email'];
            $number = array_key_exists($type . "_number", $sale_data) ?  $sale_data[$type . "_number"] : "";
            $subject = lang('Sales.' . $type) . ' ' . $number;

            $text = $this->config['invoice_email_message'];
            $tokens = [
                new Token_invoice_sequence($number),
                new Token_invoice_count('Invoice' . $sale_data['sale_id']),
                new Token_customer((array)$sale_data)
            ];
            $text = $this->token_lib->render($text, $tokens);
            $sale_data['mimetype'] = mime_content_type(FCPATH . 'uploads/' . $this->config['company_logo']);

            // Generate email attachment: invoice in PDF format
            $view = Services::renderer();
            $html = $view->setData($sale_data)->render("sales/$type" . '_email', $sale_data);

            // Load PDF helper
            helper(['dompdf', 'file']);
            $filename = sys_get_temp_dir() . '/' . lang('Sales.' . $type) . '-' . str_replace('/', '-', $number) . '.pdf';
            if (file_put_contents($filename, create_pdf($html)) !== false) {
                $result = $this->email_lib->sendEmail($to, $subject, $text, $filename);
            }

            $message = lang($result ? "Sales." . $type . "_sent" : "Sales." . $type . "_unsent") . ' ' . $to;
        }

        echo json_encode(['success' => $result, 'message' => $message, 'id' => $sale_id]);

        $this->sale_lib->clear_all();
        


        return $result;
    }

    /**
     * Emails sales receipt to customer. Used in app/Views/sales/receipt.php
     *
     * @param int $sale_id
     * @return bool
     * @noinspection PhpUnused
     */
    public function getSendReceipt(int $sale_id): bool
    {
        $sale_data = $this->_load_sale_data($sale_id);

        $result = false;
        $message = lang('Sales.receipt_no_email');

        if (!empty($sale_data['customer_email'])) {
            $sale_data['barcode'] = $this->barcode_lib->generate_receipt_barcode($sale_data['sale_id']);

            $to = $sale_data['customer_email'];
            $subject = lang('Sales.receipt');

            $view = Services::renderer();
            $text = $view->setData($sale_data)->render('sales/receipt_email');

            $result = $this->email_lib->sendEmail($to, $subject, $text);

            $message = lang($result ? 'Sales.receipt_sent' : 'Sales.receipt_unsent') . ' ' . $to;
        }

        echo json_encode(['success' => $result, 'message' => $message, 'id' => $sale_id]);

        $this->sale_lib->clear_all();

        return $result;
    }

    /**
     * @param int $customer_id
     * @param array $data
     * @param bool $stats
     * @return array|stdClass|string|null
     */
    private function _load_customer_data(int $customer_id, array &$data, bool $stats = false): array|string|stdClass|null    // TODO: Hungarian notation
    {
        $customer_info = '';

        if ($customer_id != NEW_ENTRY) {
            $customer_info = $this->customer->get_info($customer_id);
            $data['customer_id'] = $customer_id;

            if (!empty($customer_info->company_name)) {
                $data['customer'] = $customer_info->company_name;
            } else {
                $data['customer'] = $customer_info->first_name . ' ' . $customer_info->last_name;
            }

            $data['first_name'] = $customer_info->first_name;
            $data['last_name'] = $customer_info->last_name;
            $data['customer_email'] = $customer_info->email;
            $data['customer_address'] = $customer_info->address_1;

            if (!empty($customer_info->zip) || !empty($customer_info->city)) {
                $data['customer_location'] = $customer_info->zip . ' ' . $customer_info->city . "\n" . $customer_info->state;
            } else {
                $data['customer_location'] = '';
            }

            $data['customer_account_number'] = $customer_info->account_number;
            $data['customer_discount'] = $customer_info->discount;
            $data['customer_discount_type'] = $customer_info->discount_type;
            $package_id = $this->customer->get_info($customer_id)->package_id;

            if ($package_id != null) {
                $package_name = $this->customer_rewards->get_name($package_id);
                $points = $this->customer->get_info($customer_id)->points;
                $data['customer_rewards']['package_id'] = $package_id;
                $data['customer_rewards']['points'] = empty($points) ? 0 : $points;
                $data['customer_rewards']['package_name'] = $package_name;
            }

            if ($stats) {
                $cust_stats = $this->customer->get_stats($customer_id);
                $data['customer_total'] = empty($cust_stats) ? 0 : $cust_stats->total;
            }

            $data['customer_info'] = implode("\n", [
                $data['customer'],
                $data['customer_address'],
                $data['customer_location']
            ]);

            if ($data['customer_account_number']) {
                $data['customer_info'] .= "\n" . lang('Sales.account_number') . ": " . $data['customer_account_number'];
            }

            if ($customer_info->tax_id != '') {
                $data['customer_info'] .= "\n" . lang('Sales.tax_id') . ": " . $customer_info->tax_id;
            }
            $data['tax_id'] = $customer_info->tax_id;
        }

        return $customer_info;
    }

    /**
     * @param $sale_id
     * @return array
     */
    private function _load_sale_data($sale_id): array    // TODO: Hungarian notation
    {
        $this->sale_lib->clear_all();
        $cash_rounding = $this->sale_lib->reset_cash_rounding();
        $data['cash_rounding'] = $cash_rounding;

        $sale_info = $this->sale->get_info($sale_id)->getRowArray();
        $this->sale_lib->copy_entire_sale($sale_id);
        $data = [];
        $data['cart'] = $this->sale_lib->get_cart();
        $data['payments'] = $this->sale_lib->get_payments();
        $data['selected_payment_type'] = $this->sale_lib->get_payment_type();

        $tax_details = $this->tax_lib->get_taxes($data['cart'], $sale_id);
        $data['taxes'] = $this->sale->get_sales_taxes($sale_id);
        $data['discount'] = $this->sale_lib->get_discount();
        $data['transaction_time'] = to_datetime(strtotime($sale_info['sale_time']));
        $data['transaction_date'] = to_date(strtotime($sale_info['sale_time']));
        $data['show_stock_locations'] = $this->stock_location->show_locations('sales');

        $data['include_hsn'] = (bool)$this->config['include_hsn'];

        // Returns 'subtotal', 'total', 'cash_total', 'payment_total', 'amount_due', 'cash_amount_due', 'payments_cover_total'
        $totals = $this->sale_lib->get_totals($tax_details[0]);
        $this->session->set('cash_adjustment_amount', $totals['cash_adjustment_amount']);
        $data['subtotal'] = $totals['subtotal'];
        $data['payments_total'] = $totals['payment_total'];
        $data['payments_cover_total'] = $totals['payments_cover_total'];
        $data['cash_mode'] = $this->session->get('cash_mode');    // TODO: Duplicated code.
        $data['prediscount_subtotal'] = $totals['prediscount_subtotal'];
        $data['cash_total'] = $totals['cash_total'];
        $data['non_cash_total'] = $totals['total'];
        $data['cash_amount_due'] = $totals['cash_amount_due'];
        $data['non_cash_amount_due'] = $totals['amount_due'];

        if ($data['cash_mode'] && ($data['selected_payment_type'] === lang('Sales.cash') || $data['payments_total'] > 0)) {
            $data['total'] = $totals['cash_total'];
            $data['amount_due'] = $totals['cash_amount_due'];
        } else {
            $data['total'] = $totals['total'];
            $data['amount_due'] = $totals['amount_due'];
        }

        $data['amount_change'] = $data['amount_due'] * -1;

        $employee_info = $this->employee->get_info($this->sale_lib->get_employee());
        $data['employee'] = $employee_info->first_name . ' ' . mb_substr($employee_info->last_name, 0, 1);
        $this->_load_customer_data($this->sale_lib->get_customer(), $data);

        $data['sale_id_num'] = $sale_id;
        $data['sale_id'] = 'InvoiceNumber# ' . $sale_id;
        $data['comments'] = $sale_info['comment'];
        $data['invoice_number'] = $sale_info['invoice_number'];
        $data['quote_number'] = $sale_info['quote_number'];
        $data['sale_status'] = $sale_info['sale_status'];

        $data['company_info'] = implode("\n", [$this->config['address'], $this->config['phone']]);    // TODO: Duplicated code.

        if ($this->config['account_number']) {
            $data['company_info'] .= "\n" . lang('Sales.account_number') . ": " . $this->config['account_number'];
        }
        if ($this->config['tax_id'] != '') {
            $data['company_info'] .= "\n" . lang('Sales.tax_id') . ": " . $this->config['tax_id'];
        }

        $pure_sale_id = $this->extractNumericSaleId($data['sale_id']);
$data['barcode'] = $this->barcode_lib->generate_receipt_barcode($pure_sale_id);
        $data['print_after_sale'] = false;
        $data['price_work_orders'] = false;

        if ($this->sale_lib->get_mode() == 'sale_invoice') {    // TODO: Duplicated code.
            $data['mode_label'] = lang('Sales.invoice');
            $data['customer_required'] = lang('Sales.customer_required');
        } elseif ($this->sale_lib->get_mode() == 'sale_quote') {
            $data['mode_label'] = lang('Sales.quote');
            $data['customer_required'] = lang('Sales.customer_required');
        } elseif ($this->sale_lib->get_mode() == 'sale_work_order') {
            $data['mode_label'] = lang('Sales.work_order');
            $data['customer_required'] = lang('Sales.customer_required');
        } elseif ($this->sale_lib->get_mode() == 'return') {
            $data['mode_label'] = lang('Sales.return');
            $data['customer_required'] = lang('Sales.customer_optional');
        } else {
            $data['mode_label'] = lang('Sales.receipt');
            $data['customer_required'] = lang('Sales.customer_optional');
        }

        $invoice_type = $this->config['invoice_type'];
        $data['invoice_view'] = $invoice_type;

        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    private function _reload(array $data = []): void
{
    $sale_id = $this->session->get('sale_id');

    if ($sale_id == '') {
        $sale_id = NEW_ENTRY;
        $this->session->set('sale_id', NEW_ENTRY);
    }
    $cash_rounding = $this->sale_lib->reset_cash_rounding();

    // cash_rounding indicates only that the site is configured for cash rounding
    $data['cash_rounding'] = $cash_rounding;

    // --- START: NEW/MOVED CODE FOR EMPLOYEE LIST ---
    // Fetch the list of all employees for the "Waiter" dropdown
    $employees_list = $this->employee->get_all()->getResult();
    $employees_dropdown = [];
    foreach ($employees_list as $employee) {
        $employees_dropdown[$employee->person_id] = $employee->first_name . ' ' . $employee->last_name;
    }

    // Add the employee list to the $data array
    $data['employees_list'] = $employees_dropdown;

    // Get the selected waiter ID from the session and add it to the $data array
    $data['selected_waiter_id'] = $this->sale_lib->get_waiter();
    // --- END: NEW/MOVED CODE ---
// In Sales.php Controller, inside the _reload() method


// Load categories
    $categories = $this->item->get_all_categories();
    $categories_dropdown = ['' => 'Select Category'];
    foreach ($categories as $cat) {
        $categories_dropdown[$cat['category']] = $cat['category'];
    }
    $data['categories'] = $categories_dropdown;

    // Load all items as JSON for search bar
$all_items = $this->item->get_all_items(); // returns array
$data['all_items_json'] = json_encode($all_items);
$data['cart'] = $this->sale_lib->get_cart();
$customer_info = $this->_load_customer_data($this->sale_lib->get_customer(), $data, true);
$data['all_items'] = $all_items; // ✅ correct


    $data['modes'] = $this->sale_lib->get_register_mode_options();
    $data['mode'] = $this->sale_lib->get_mode();
    $data['selected_table'] = $this->sale_lib->get_dinner_table();
    $data['empty_tables'] = $this->sale_lib->get_empty_tables($data['selected_table']);
    $data['stock_locations'] = $this->stock_location->get_allowed_locations('sales');
    $data['stock_location'] = $this->sale_lib->get_sale_location();
    $data['tax_exclusive_subtotal'] = $this->sale_lib->get_subtotal(true, true);
    $tax_details = $this->tax_lib->get_taxes($data['cart']);
    $data['taxes'] = $tax_details[0];
    $data['discount'] = $this->sale_lib->get_discount();
    $data['payments'] = $this->sale_lib->get_payments();

    // Returns 'subtotal', 'total', 'cash_total', 'payment_total', 'amount_due', 'cash_amount_due', 'payments_cover_total'
    $totals = $this->sale_lib->get_totals($tax_details[0]);

    $data['item_count'] = $totals['item_count'];
    $data['total_units'] = $totals['total_units'];
    $data['subtotal'] = $totals['subtotal'];
    $data['total'] = $totals['total'];
    $data['payments_total'] = $totals['payment_total'];
    $data['payments_cover_total'] = $totals['payments_cover_total'];

    // cash_mode indicates whether this sale is going to be processed using cash_rounding
    $cash_mode = $this->session->get('cash_mode');
    $data['cash_mode'] = $cash_mode;
    $data['prediscount_subtotal'] = $totals['prediscount_subtotal'];
    $data['cash_total'] = $totals['cash_total'];
    $data['non_cash_total'] = $totals['total'];
    $data['cash_amount_due'] = $totals['cash_amount_due'];
    $data['non_cash_amount_due'] = $totals['amount_due'];

    $data['selected_payment_type'] = $this->sale_lib->get_payment_type();

    if ($data['cash_mode'] && ($data['selected_payment_type'] == lang('Sales.cash') || $data['payments_total'] > 0)) {
        $data['total'] = $totals['cash_total'];
        $data['amount_due'] = $totals['cash_amount_due'];
    } else {
        $data['total'] = $totals['total'];
        $data['amount_due'] = $totals['amount_due'];
    }

    $data['amount_change'] = $data['amount_due'] * -1;

    $data['comment'] = $this->sale_lib->get_comment();
    $data['email_receipt'] = $this->sale_lib->is_email_receipt();

   if ($customer_info && $this->config['customer_reward_enable']) {
    $payment_options = $this->sale->get_payment_options(true, true);
} else {
    $payment_options = $this->sale->get_payment_options();
}

// Default option + selected value fix for CodeIgniter 4
$data['payment_options'] = ['' => lang('select_payment_option')] + $payment_options;
$data['selected_payment_type'] = $this->request->getPost('payment_type') ?? '';


    $data['items_module_allowed'] = $this->employee->has_grant('items', $this->employee->get_logged_in_employee_info()->person_id);
    $data['change_price'] = $this->employee->has_grant('sales_change_price', $this->employee->get_logged_in_employee_info()->person_id);

    $temp_invoice_number = $this->sale_lib->get_invoice_number();
    $invoice_format = $this->config['sales_invoice_format'];

    if ($temp_invoice_number == null || $temp_invoice_number == '') {
        $temp_invoice_number = $this->token_lib->render($invoice_format, [], false);
    }

    $data['invoice_number'] = $temp_invoice_number;

    $data['print_after_sale'] = $this->sale_lib->is_print_after_sale();
    $data['price_work_orders'] = $this->sale_lib->is_price_work_orders();

    $data['pos_mode'] = $data['mode'] == 'sale' || $data['mode'] == 'return';

    $data['quote_number'] = $this->sale_lib->get_quote_number();
    $data['work_order_number'] = $this->sale_lib->get_work_order_number();

    // TODO: the if/else set below should be converted to a switch
    if ($this->sale_lib->get_mode() == 'sale_invoice') {
        $data['mode_label'] = lang('Sales.invoice');
        $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'sale_quote') {
        $data['mode_label'] = lang('Sales.quote');
        $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'sale_work_order') {
        $data['mode_label'] = lang('Sales.work_order');
        $data['customer_required'] = lang('Sales.customer_required');
    } elseif ($this->sale_lib->get_mode() == 'return') {
        $data['mode_label'] = lang('Sales.return');
        $data['customer_required'] = lang('Sales.customer_optional');
    } else {
        $data['mode_label'] = lang('Sales.receipt');
        $data['customer_required'] = lang('Sales.customer_optional');
    }
$order_id = $this->sale_lib->get_current_order_id(); // now works
$data['orders'] = [];

if ($order_id) {
    $data['orders'] = model(OrderItem::class)
        ->where('order_id', $order_id)
        ->orderBy('order_item_id', 'ASC')
        ->findAll();
}
$data['current_order_id'] = $this->sale_lib->get_current_order_id();


$data['dinner_table_number'] = $this->sale_lib->get_dinner_table();
$data['waiter_name'] = $this->sale_lib->get_waiter();

// Fetch saved order items directly from OrderItem model
$orderItemModel = model(\App\Models\OrderItem::class);
$data['orders'] = $orderItemModel
    ->where('order_id', $order_id)
    ->orderBy('line', 'ASC')
    ->findAll(); // returns array of saved items



    echo view("sales/register", $data);
}
    /**
     * Load the sales receipt for a sale. Used in app/Views/sales/form.php
     *
     * @param int $sale_id
     * @return void
     * @noinspection PhpUnused
     */
    public function getReceipt(int $sale_id): void
    {
        $data = $this->_load_sale_data($sale_id);
        echo view('sales/receipt', $data);
        $this->sale_lib->clear_all();
    }

    /**
     * @param int $sale_id
     * @return void
     */
    public function getInvoice(int $sale_id): void
    {
        $data = $this->_load_sale_data($sale_id);

        echo view('sales/' . $data['invoice_view'], $data);
        $this->sale_lib->clear_all();
    }

    /**
     * @param int $sale_id
     * @return void
     */
    public function getEdit(int $sale_id): void
    {
        $data = [];

        $sale_info = $this->sale->get_info($sale_id)->getRowArray();
        $data['selected_customer_id'] = $sale_info['customer_id'];
        $data['selected_customer_name'] = $sale_info['customer_name'];
        $employee_info = $this->employee->get_info($sale_info['employee_id']);
        $data['selected_employee_id'] = $sale_info['employee_id'];
        $data['selected_employee_name'] = $employee_info->first_name . ' ' . $employee_info->last_name;
        $data['sale_info'] = $sale_info;
        $balance_due = round($sale_info['amount_due'] - $sale_info['amount_tendered'] + $sale_info['cash_refund'], totals_decimals(), PHP_ROUND_HALF_UP);

        if (!$this->sale_lib->reset_cash_rounding() && $balance_due < 0) {
            $balance_due = 0;
        }

        $data['payments'] = [];

        foreach ($this->sale->get_sale_payments($sale_id)->getResult() as $payment) {
            foreach (get_object_vars($payment) as $property => $value) {
                $payment->$property = $value;
            }
            $data['payments'][] = $payment;
        }

        $data['payment_type_new'] = PAYMENT_TYPE_UNASSIGNED;
        $data['payment_amount_new'] = $balance_due;

        $data['balance_due'] = $balance_due != 0;

        // Don't allow gift card to be a payment option in a sale transaction edit because it's a complex change
        $payment_options = $this->sale->get_payment_options(false);

        if ($this->sale_lib->reset_cash_rounding()) {
            $payment_options[lang('Sales.cash_adjustment')] = lang('Sales.cash_adjustment');
        }

        $data['payment_options'] = $payment_options;

        // Set up a slightly modified list of payment types for new payment entry
        $payment_options["--"] = lang('Common.none_selected_text');

        $data['new_payment_options'] = $payment_options;

        echo view('sales/form', $data);
    }

    /**
     * @throws ReflectionException
     */
    public function postDelete(int $sale_id = NEW_ENTRY, bool $update_inventory = true): void
    {
        $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $has_grant = $this->employee->has_grant('sales_delete', $employee_id);

        if (!$has_grant) {
            echo json_encode(['success' => false, 'message' => lang('Sales.not_authorized')]);
        } else {
            $sale_ids = $sale_id == NEW_ENTRY ? $this->request->getPost('ids', FILTER_SANITIZE_NUMBER_INT) : [$sale_id];

            if ($this->sale->delete_list($sale_ids, $employee_id, $update_inventory)) {
                echo json_encode([
                    'success' => true,
                    'message' => lang('Sales.successfully_deleted') . ' ' . count($sale_ids) . ' ' . lang('Sales.one_or_multiple'),
                    'ids'     => $sale_ids
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => lang('Sales.unsuccessfully_deleted')]);
            }
        }
    }

    /**
     * @param int $sale_id
     * @param bool $update_inventory
     * @return void
     */
    public function restore(int $sale_id = NEW_ENTRY, bool $update_inventory = true): void
    {
        $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $has_grant = $this->employee->has_grant('sales_delete', $employee_id);

        if (!$has_grant) {
            echo json_encode(['success' => false, 'message' => lang('Sales.not_authorized')]);
        } else {
            $sale_ids = $sale_id == NEW_ENTRY ? $this->request->getPost('ids', FILTER_SANITIZE_NUMBER_INT) : [$sale_id];

            if ($this->sale->restore_list($sale_ids, $employee_id, $update_inventory)) {
                echo json_encode([
                    'success' => true,
                    'message' => lang('Sales.successfully_restored') . ' ' . count($sale_ids) . ' ' . lang('Sales.one_or_multiple'),
                    'ids'     => $sale_ids
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => lang('Sales.unsuccessfully_restored')]);
            }
        }
    }

    /**
     * This saves the sale from the update sale view (sales/form).
     * It only updates the sales table and payments.
     * @param int $sale_id
     * @throws ReflectionException
     */
    public function postSave(int $sale_id = NEW_ENTRY): void
    {
        $newdate = $this->request->getPost('date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $inventory = model(Inventory::class);
        $date_formatter = date_create_from_format($this->config['dateformat'] . ' ' . $this->config['timeformat'], $newdate);
        $sale_time = $date_formatter->format('Y-m-d H:i:s');

        $sale_data = [
            'sale_time'      => $sale_time,
            'customer_id'    => $this->request->getPost('customer_id') != '' ? $this->request->getPost('customer_id', FILTER_SANITIZE_NUMBER_INT) : null,
            'employee_id'    => $this->request->getPost('employee_id') != '' ? $this->request->getPost('employee_id', FILTER_SANITIZE_NUMBER_INT) : null,
            'comment'        => $this->request->getPost('comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'invoice_number' => $this->request->getPost('invoice_number') != '' ? $this->request->getPost('invoice_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null
        ];

        // In order to maintain tradition the only element that can change on prior payments is the payment type
        $amount_tendered = 0;
        $number_of_payments = $this->request->getPost('number_of_payments', FILTER_SANITIZE_NUMBER_INT);
        for ($i = 0; $i < $number_of_payments; ++$i) {
            $payment_id = $this->request->getPost("payment_id_$i", FILTER_SANITIZE_NUMBER_INT);
            $payment_type = $this->request->getPost("payment_type_$i", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $payment_amount = parse_decimals($this->request->getPost("payment_amount_$i"));
            $refund_type = $this->request->getPost("refund_type_$i", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $cash_refund = parse_decimals($this->request->getPost("refund_amount_$i"));

            $cash_adjustment = $payment_type == lang('Sales.cash_adjustment') ? CASH_ADJUSTMENT_TRUE : CASH_ADJUSTMENT_FALSE;

            if (!$cash_adjustment) {
                $amount_tendered += $payment_amount - $cash_refund;
            }

            // Non-cash positive refund amounts
            if (empty(strstr($refund_type, lang('Sales.cash'))) && $cash_refund > 0) {    // TODO: This if and the one below can be combined.
                // Change it to be a new negative payment (a "non-cash refund")
                $payment_type = $refund_type;
                $payment_amount = $payment_amount - $cash_refund;
                $cash_refund = 0.00;
            }

            $sale_data['payments'][] = [
                'payment_id'      => $payment_id,
                'payment_type'    => $payment_type,
                'payment_amount'  => $payment_amount,
                'cash_refund'     => $cash_refund,
                'cash_adjustment' => $cash_adjustment,
                'employee_id'     => $employee_id
            ];
        }

        $payment_id = NEW_ENTRY;
        $payment_amount_new = $this->request->getPost('payment_amount_new');
        $payment_type = $this->request->getPost('payment_type_new', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($payment_type != PAYMENT_TYPE_UNASSIGNED && !empty($payment_amount_new)) {
            $payment_amount = parse_decimals($payment_amount_new);
            $cash_refund = 0;
            if ($payment_type == lang('Sales.cash_adjustment')) {
                $cash_adjustment = CASH_ADJUSTMENT_TRUE;
            } else {
                $cash_adjustment = CASH_ADJUSTMENT_FALSE;
                $amount_tendered += $payment_amount;
                $sale_info = $this->sale->get_info($sale_id)->getRowArray();

                if ($amount_tendered > $sale_info['amount_due']) {
                    $cash_refund = $amount_tendered - $sale_info['amount_due'];
                }
            }

            $sale_data['payments'][] = [
                'payment_id'      => $payment_id,
                'payment_type'    => $payment_type,
                'payment_amount'  => $payment_amount,
                'cash_refund'     => $cash_refund,
                'cash_adjustment' => $cash_adjustment,
                'employee_id'     => $employee_id
            ];
        }

        $inventory->update('POS ' . $sale_id, ['trans_date' => $sale_time]);    // TODO: Reflection Exception
        if ($this->sale->update($sale_id, $sale_data)) {
            echo json_encode(['success' => true, 'message' => lang('Sales.successfully_updated'), 'id' => $sale_id]);
        } else {
            echo json_encode(['success' => false, 'message' => lang('Sales.unsuccessfully_updated'), 'id' => $sale_id]);
        }
    }

    /**
     * This is used to cancel a suspended pos sale, quote.
     * Completed sales (POS Sales or Invoiced Sales) can not be removed from the system
     * Work orders can be canceled but are not physically removed from the sales history.
     * Used in app/Views/sales/register.php
     *
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public function postCancel(?int $order_id = null): void
{
    // Force current_order_id from POST if it's not set
    $order_id = $order_id ?? $this->request->getPost('order_id');
    if ($order_id) {
        $this->sale_lib->set_current_order_id($order_id);
    }

    $sale_id = $this->sale_lib->get_sale_id();
    if ($sale_id != NEW_ENTRY && $sale_id != '') {
        $sale_type = $this->sale_lib->get_sale_type();

        if ($this->config['dinner_table_enable']) {
            $dinner_table = $this->sale_lib->get_dinner_table();
            $this->dinner_table->release($dinner_table);
        }

        if ($sale_type == SALE_TYPE_WORK_ORDER) {
            $this->sale->update_sale_status($sale_id, CANCELED);
        } else {
            $this->sale->delete($sale_id);
            $this->session->set('sale_id', NEW_ENTRY);
        }
    } else {
        $this->sale_lib->remove_temp_items();
    }

    $this->sale_lib->clear_all();

    // Update order status using the now-forced current_order_id
    $current_order_id = $this->sale_lib->get_current_order_id();
    if ($current_order_id) {
        $order_model = model(\App\Models\Order::class);
        $order_model->update($current_order_id, [
            'status' => 'cancelled',
        ]);
        $this->sale_lib->set_current_order_id(null);
    }

    if (!$this->request->isAJAX()) {
    $this->_reload();
}

}

    /**
     * Discards the suspended sale. Used in app/Views/sales/quote.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function getDiscardSuspendedSale(): void
    {
        $suspended_id = $this->sale_lib->get_suspended_id();
        $this->sale_lib->clear_all();
        $this->sale->delete_suspended_sale($suspended_id);
        $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * Suspend the current sale.
     * If the current sale is already suspended then update the existing suspended sale otherwise create
     * it as a new suspended sale. Used in app/Views/sales/register.php.
     *
     * @throws ReflectionException
     * @noinspection PhpUnused
     */
    public function postSuspend(): void
    {
        $sale_id = $this->sale_lib->get_sale_id();
        $dinner_table = $this->sale_lib->get_dinner_table();
        $cart = $this->sale_lib->get_cart();
        $payments = $this->sale_lib->get_payments();
        $employee_id = $this->employee->get_logged_in_employee_info()->person_id;
        $customer_id = $this->sale_lib->get_customer();
        $invoice_number = $this->sale_lib->get_invoice_number();
        $work_order_number = $this->sale_lib->get_work_order_number();
        $quote_number = $this->sale_lib->get_quote_number();
        $sale_type = $this->sale_lib->get_sale_type();

        if ($sale_type == '') {
            $sale_type = SALE_TYPE_POS;
        }

        $comment = $this->sale_lib->get_comment();
        $sale_status = SUSPENDED;

        $data = [];
        $sales_taxes = [[], []];

        if ($this->sale->save_value($sale_id, $sale_status, $cart, $customer_id, $employee_id, $comment, $invoice_number, $work_order_number, $quote_number, $sale_type, $payments, $dinner_table, $sales_taxes) == '-1') {
            $data['error'] = lang('Sales.unsuccessfully_suspended_sale');
        } else {
            $data['success'] = lang('Sales.successfully_suspended_sale');
        }

        $this->sale_lib->clear_all();

        $this->_reload($data);    // TODO: Hungarian notation
    }

    /**
     * List suspended sales
     */
    public function getSuspended(): void
    {
        $data = [];
        $customer_id = $this->sale_lib->get_customer();
        $data['suspended_sales'] = $this->sale->get_all_suspended($customer_id);
        // In your Sales.php controller, somewhere in the index() method before loading the view
$data['employees'] = $this->Employee->get_all()->getResult();
        echo view('sales/suspended', $data);
    }

    /**
     * Unsuspended sales are now left in the tables and are only removed
     * when they are intentionally cancelled. Used in app/Views/sales/suspended.php.
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postUnsuspend(): void
    {
        $sale_id = $this->request->getPost('suspended_sale_id', FILTER_SANITIZE_NUMBER_INT);
        $this->sale_lib->clear_all();

        if ($sale_id > 0) {
            $this->sale_lib->copy_entire_sale($sale_id);
        }

        // Set current register mode to reflect that of unsuspended order type
        $this->change_register_mode($this->sale_lib->get_sale_type());

        $this->_reload();    // TODO: Hungarian notation
    }

    /**
     * Show Keyboard shortcut modal. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function getSalesKeyboardHelp(): void
    {
        echo view('sales/help');
    }

    /**
     * Check the validity of an invoice number. Used in app/Views/sales/form.php.
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postCheckInvoiceNumber(): void
    {
        $sale_id = $this->request->getPost('sale_id', FILTER_SANITIZE_NUMBER_INT);
        $invoice_number = $this->request->getPost('invoice_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $exists = !empty($invoice_number) && $this->sale->check_invoice_number_exists($invoice_number, $sale_id);
        echo !$exists ? 'true' : 'false';
    }

    /**
     * @param array $cart
     * @return array
     */
    public function get_filtered(array $cart): array
    {
        $filtered_cart = [];
        foreach ($cart as $id => $item) {
            if ($item['print_option'] == PRINT_ALL) // Always include
            {
                $filtered_cart[$id] = $item;
            } elseif ($item['print_option'] == PRINT_PRICED && $item['price'] != 0)  // Include only if the price is not zero
            {
                $filtered_cart[$id] = $item;
            }
            // print_option 2 is never included
        }

        return $filtered_cart;
    }

    /**
     * Update the item number in the register. Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postChangeItemNumber(): void
    {
        $item_id = $this->request->getPost('item_id', FILTER_SANITIZE_NUMBER_INT);
        $item_number = $this->request->getPost('item_number', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $this->item->update_item_number($item_id, $item_number);
        $cart = $this->sale_lib->get_cart();
        $x = $this->search_cart_for_item_id($item_id, $cart);
        if ($x != null) {
            $cart[$x]['item_number'] = $item_number;
        }
        $this->sale_lib->set_cart($cart);
    }

    /**
     * Change a given item name. Used in app/Views/sales/register.php.
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postChangeItemName(): void
    {
        $item_id = $this->request->getPost('item_id', FILTER_SANITIZE_NUMBER_INT);
        $name = $this->request->getPost('item_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->item->update_item_name($item_id, $name);

        $cart = $this->sale_lib->get_cart();
        $x = $this->search_cart_for_item_id($item_id, $cart);

        if ($x != null) {
            $cart[$x]['name'] = $name;
        }

        $this->sale_lib->set_cart($cart);
    }

    /**
     * Update the given item description.  Used in app/Views/sales/register.php
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function postChangeItemDescription(): void
    {
        $item_id = $this->request->getPost('item_id', FILTER_SANITIZE_NUMBER_INT);
        $description = $this->request->getPost('item_description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->item->update_item_description($item_id, $description);

        $cart = $this->sale_lib->get_cart();
        $x = $this->search_cart_for_item_id($item_id, $cart);

        if ($x != null) {
            $cart[$x]['description'] = $description;
        }

        $this->sale_lib->set_cart($cart);
    }

    /**
     * @param int $search_item_id
     * @param array $shopping_cart
     * @return int|string|null
     */
    public function search_cart_for_item_id(int $search_item_id, array $shopping_cart): int|string|null
    {
        foreach ($shopping_cart as $key => $val) {
            if ($val['item_id'] === $search_item_id) {
                return $key;
            }
        }

        return null;
    }
    /**
     * Gets all employees to be configured as waiters.
     *
     * @return void
     */
    public function getWaiters(): void
    {
        // Get all employees to populate the waiter dropdown
        $this->load->model('employees');
        $employees_query = $this->employees->get_all();
        $employees_list = [];
        foreach ($employees_query->getResultArray() as $row) {
            $employees_list[$row['person_id']] = $row['first_name'] . ' ' . $row['last_name'];
        }

        // Get the saved waiter names configuration and unserialize it
        $waiter_names_config = $this->appconfig->get('waiter_names');
        $waiter_names = $waiter_names_config ? unserialize($waiter_names_config) : [];
        
        $data['employees'] = $employees_list;
        $data['waiter_names'] = $waiter_names;

        echo view('partial/waiters_config', $data);
    }
    private function extractNumericSaleId($sale_id): string
{
    // Remove all non-digit characters
    $numeric_id = preg_replace('/\D/', '', $sale_id);
    
    // Ensure we have a valid numeric ID
    if (empty($numeric_id) || !is_numeric($numeric_id)) {
        // Fallback to a default or log error
        log_message('error', 'Invalid sale ID for barcode generation: ' . $sale_id);
        return '0'; // or throw exception based on your error handling strategy
    }
    
    return $numeric_id;
}
public function postPreviewItem(): void
{
    $item_id_or_number = $this->request->getPost('item', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $discount = $this->config['default_sales_discount'];
    $discount_type = $this->config['default_sales_discount_type'];
    $item_location = $this->sale_lib->get_sale_location();

    // Get item info but don't actually add
    $item = $this->item_lib->get_item_info_for_sale($item_id_or_number, $item_location);

    if (!$item) {
        echo json_encode(['error' => lang('Sales.unable_to_add_item')]);
        return;
    }

    // Prepare preview item data
    $item['discount'] = $discount;
    $item['discount_type'] = $discount_type;
    $item['quantity'] = 1; // default preview quantity
    $item['discounted_total'] = $item['price'] - ($item['price'] * ($discount / 100)); // simplified

    // Render HTML row inline for the existing cart table
    $row_html = '<tr class="preview-row" data-item-id="' . $item['item_id'] . '">';
    $row_html .= '<td>&nbsp;</td>'; // Delete icon placeholder for preview
    $row_html .= '<td>' . esc($item['item_number']) . '</td>';
    $row_html .= '<td>' . esc($item['name']) . '</td>';
    $row_html .= '<td>' . to_currency($item['price']) . '</td>';
    $row_html .= '<td>' . to_quantity_decimals($item['quantity']) . '</td>';
    $row_html .= '<td>' . ($item['discount_type'] ? to_currency_no_money($item['discount']) : to_decimals($item['discount'])) . '</td>';
    $row_html .= '<td>' . to_currency($item['discounted_total']) . '</td>';
    $row_html .= '<td>&nbsp;</td>'; // Update placeholder
    $row_html .= '</tr>';

    echo json_encode(['html' => $row_html]);
}
public function reset_sale()
{
    // Remove all session data about current sale
    $this->session->remove('sale_id');
    $this->session->remove('payments');
    $this->session->remove('cart');
    $this->session->remove('customer_id');
    $this->session->remove('comment');

    // Optional: redirect back to fresh sale
    return redirect()->to(site_url('sales'));
}



public function print_order_queue($order_id) // remove int type hint
{
    $order_id = (int)$order_id; // cast to integer
    if ($order_id <= 0) {
        return redirect()->back()->with('error', 'Invalid Order ID');
    }

    

    $orderItems = model(OrderItem::class)
    ->where('order_id', $order_id)
    ->orderBy('order_item_id', 'ASC')
    ->findAll();

// Convert to array of plain arrays
$orderItemsArr = array_map(function($item){
    return [
        'name' => $item['name'] ?? $item->name ?? '',
        'quantity' => $item['quantity'] ?? $item->quantity ?? 0,
    ];
}, $orderItems);

$order = model(Order::class)->find($order_id);
$waiter = $order ? model(Employee::class)->find($order->employee_id) : null;

$data = [
    'order_id' => $order_id,
    'dinner_table_number' => $order->dinner_table_id ?? '',
    'waiter_name' => $waiter ? $waiter->first_name . ' ' . $waiter->last_name : '',
    'orders' => $orderItemsArr
];

echo view('sales/register.php', $data);


}

public function getOrder_queue()
{
    $orderModel = new \App\Models\Order();
    $data['orders'] = $orderModel->get_orders();
    return view('sales/queue_view', $data);
}

public function getLoadOrderIntoCart($order_id = null)
{
    if (!$order_id) {
        throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
    }

    $orderItemModel = model(\App\Models\OrderItem::class);
    $items = $orderItemModel->get_order_items($order_id);

    if (empty($items)) {
        return "No items found in this order.";
    }

    $this->sale_lib->clear_all();
    $this->sale_lib->set_current_order_id($order_id);

    foreach ($items as $item) {
    $item_id = $item->item_id;
    $item_location = 1; // or actual location id
    $quantity = (string)$item->quantity;  // must be string
    $discount = (string)($item->discount_percent ?? 0);
    $discount_type = 0; // integer
    $price_mode = PRICE_MODE_STANDARD; // integer
    $description = $item->description ?? '';

    $this->sale_lib->add_item(
        $item_id,
        $item_location,
        $quantity,
        $discount,
        $discount_type,
        $price_mode,
        null,  // kit_price_option
        null,  // kit_print_option
        null,  // price_override
        $description
    );
}


    return $this->_reload();
}



// Inside Sales.php
public function getNewOrder(): void
{
    // Reuse the same logic as postNewOrder
    $this->sale_lib->clear_all();
    $this->sale_lib->set_current_order_id(null);
    
    $this->_reload(); // reloads the register page
}



}