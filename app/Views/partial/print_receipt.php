<?php
/**
 * @var string $selected_printer
 * @var bool $print_after_sale
 * @var array $config
 */
?>

<script type="text/javascript">
    // Flag to prevent multiple prints per page load
    window.hasPrinted = false;

    // Global print counter for testing
    window.printCount = 0;

    function printdoc() {
        // Stop if already printed
        if (window.hasPrinted) return;

        // Check if receipt has content
        var receiptContent = $('#receipt_wrapper').html();
        if ($.trim(receiptContent) === '') {
            console.log('Receipt empty, skipping print.');
            return;
        }

        // Mark as printed and increment counter
        window.hasPrinted = true;
        window.printCount++;
        console.log('Print triggered. Total print attempts: ' + window.printCount);

        // Install Firefox addon if available
        if (window.jsPrintSetup) {
            // Set margins
            jsPrintSetup.setOption('marginTop', '<?= $config['print_top_margin'] ?>');
            jsPrintSetup.setOption('marginLeft', '<?= $config['print_left_margin'] ?>');
            jsPrintSetup.setOption('marginBottom', '<?= $config['print_bottom_margin'] ?>');
            jsPrintSetup.setOption('marginRight', '<?= $config['print_right_margin'] ?>');

            <?php if (!$config['print_header']) { ?>
                jsPrintSetup.setOption('headerStrLeft', '');
                jsPrintSetup.setOption('headerStrCenter', '');
                jsPrintSetup.setOption('headerStrRight', '');
            <?php } ?>
            <?php if (!$config['print_footer']) { ?>
                jsPrintSetup.setOption('footerStrLeft', '');
                jsPrintSetup.setOption('footerStrCenter', '');
                jsPrintSetup.setOption('footerStrRight', '');
            <?php } ?>

            var printers = jsPrintSetup.getPrintersList().split(',');
            for (var index in printers) {
                var default_ticket_printer = window.localStorage && localStorage['<?= esc($selected_printer, 'js') ?>'];
                var selected_printer = printers[index];
                if (selected_printer === default_ticket_printer) {
                    jsPrintSetup.setPrinter(selected_printer);
                    jsPrintSetup.clearSilentPrint();

                    <?php if (!$config['print_silently']) { ?>
                        jsPrintSetup.setOption('printSilent', 1);
                    <?php } ?>

                    // Print once
                    jsPrintSetup.print();
                }
            }
        } 
        // Fallback for browsers without jsPrintSetup
        else {
            window.print();
            window.setTimeout(function(){ window.stop(); }, 500);
        }
    }

    <?php if ($print_after_sale) { ?>
        $(window).on('load', function() {
            printdoc();

            // After a delay, return to sales view
            setTimeout(function() {
                window.location.href = "<?= site_url('sales') ?>";
            }, <?= $config['print_delay_autoreturn'] * 1000 ?>);
        });
    <?php } ?>
</script>
