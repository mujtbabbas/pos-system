<div class="row">
    <?php foreach ($orders as $order): ?>
        <div class="col-md-4">
            <div class="panel panel-default position-relative">
                <!-- Edit Icon -->
                <button type="button" 
        class="btn btn-link btn-sm edit-order" 
        data-order-id="<?= $order['order_id']; ?>"
        style="position:absolute; top:5px; right:8px; color:#337ab7;">
    <span class="glyphicon glyphicon-pencil" style="font-size:12px;"></span>
</button>



                <div class="panel-heading">
                    <strong>Table <?= $order['table_no']; ?></strong>
                </div>
                <div class="panel-body">
                    <p><b>Order #</b> <?= $order['order_id']; ?></p>
                    <p><b>Waiter:</b> <?= $order['employee_name'] ?: 'Online'; ?></p>
                    <p>
                        <b>Running:</b>
                        <span class="running-timer" data-start="<?= $order['order_time']; ?>"></span>
                    </p>
                </div>
                
                    <div class="panel-footer">
    <!-- Complete button for each order in modal -->
<button class="btn btn-success btn-xs complete-order" 
        data-order-id="<?= $order['order_id']; ?>">
    Complete
</button>


    <form method="post" action="<?= site_url("$controller_name/cancel") ?>" style="display:inline;">
        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
        <button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to cancel this order?')">
            Cancel
        </button>
    </form>
</div>

            </div>
        </div>
    <?php endforeach; ?>
</div>




  <script>
$(function(){
  // Timer logic (unchanged)
  setInterval(function(){
    $(".running-timer").each(function(){
      let start = new Date($(this).data("start")).getTime();
      let now = new Date().getTime();
      let diff = Math.floor((now - start)/1000);

      let h = String(Math.floor(diff/3600)).padStart(2,'0');
      let m = String(Math.floor((diff%3600)/60)).padStart(2,'0');
      let s = String(diff%60).padStart(2,'0');

      $(this).text(h+":"+m+":"+s);
    });
  },1000);

  $(document).on("click", ".edit-order", function() {
    let orderId = $(this).data("order-id");

    // Close the modal immediately
    $('#orderModal').modal('hide'); // Replace '#orderModal' with your modal ID

    $.get("<?= site_url('sales/loadOrderIntoCart'); ?>/" + orderId, function(response) {
        const $response = $(response);

        // Update cart contents only
        const $newCartContents = $response.find('#cart_contents');
        if ($newCartContents.length) {
            $('#cart_contents').html($newCartContents.html());
        }
         // Update sale/payment section
        const $newPayment = $response.find('#payment_details');
        if ($newPayment.length) {
            $('#payment_details').html($newPayment.html());
        }

        // Update overall totals
        const $newTotals = $response.find('#sale_totals');
        if ($newTotals.length) {
            $('#sale_totals').html($newTotals.html());
        }
    const $newpayTotals = $response.find('#payment_totals');
if ($newpayTotals.length) {
    $('#payment_totals').html($newpayTotals.html());
}

$('#payment_details select.selectpicker').selectpicker('refresh');
        // Update payment contents
const $newcontent = $response.find('#payment_contents');
        if ($newcontent.length) {
            $('#payment_contents').html($newcontent.html());
        }
//window.location.href = "<?= site_url('sales') ?>";

    }).fail(function(xhr) {
        console.error("Failed to load order:", xhr.responseText);
        alert("Failed to load order.");
    });
});

});
$(document).on("click", ".complete-order", function() {
    let orderId = $(this).data("order-id");

    // Close the modal
    $('#orderModal').modal('hide');

    // Load order into cart
    $.get("<?= site_url('sales/loadOrderIntoCart'); ?>/" + orderId, function(response) {
        const $response = $(response);

        // Update cart items
        const $newCart = $response.find('#cart_contents');
        if ($newCart.length) {
            $('#cart_contents').html($newCart.html());
        }

        // Update sale/payment section
        const $newPayment = $response.find('#payment_details');
        if ($newPayment.length) {
            $('#payment_details').html($newPayment.html());
        }

        // Update overall totals
        const $newTotals = $response.find('#sale_totals');
        if ($newTotals.length) {
            $('#sale_totals').html($newTotals.html());
        }
    const $newpayTotals = $response.find('#payment_totals');
if ($newpayTotals.length) {
    $('#payment_totals').html($newpayTotals.html());
}

$('#payment_details select.selectpicker').selectpicker('refresh');
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
//window.location.href = "<?= site_url('sales') ?>";
    }).fail(function(xhr) {
        console.error("Failed to load order:", xhr.responseText);
        alert("Failed to load order.");
    });

});


</script>



