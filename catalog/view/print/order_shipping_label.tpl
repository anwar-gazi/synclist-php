<!DOCTYPE html>
<html lang="en">
    <head>
        <title>print shipment label for order <?php $orderid; ?></title>
        <?php echo $head_includes; ?>

        <script src="<?php echo $assets_url ?>/SyncList/ebayorder.js"></script>
        <script src="<?php echo $assets_url ?>/SyncList/consume_shipping_api.js"></script>
        <script src="<?php echo $assets_url ?>/SyncList/consume_orders_api.js"></script>

        <style type="text/css">
            @media print {
                .no-print, .no-print * {
                    display: none !important;
                }
                div#label_container {
                    border: none !important;
                }
            }

            .display_none {
                display: none;
            }
            div#label_container {
                position: relative;
            }
            div.label-print-control {
                position: absolute;
                top: -40px;
                right: 0px;
            }
            span.delete_this_history {
                margin-left: 20px;
            }
        </style>
    </head>
    <body>
        <div style="margin-top: 10%; border: 1px solid; padding: 50px; display: table; margin-left: auto; margin-right: auto" id="label_container">
            <div class="clearfix no-print label-print-control">
                <button type="button" name="print" class="print_order btn btn-default display_none"><span class="fa fa-print"> print</span></button>
                <span class="print_ok display_none fa fa-lg fa-check-square">print ok</span>
            </div>
            <div>
                <?php echo $content; ?>
            </div>
        </div>
        <script type="text/javascript">
            (function () {
                /*
                 * take the orderid from the backend
                 * @type String
                 */
                var orderid = "<?php echo $orderid; ?>";
                
                /*
                 * load the order info and show label print history
                 */
                SyncList.Class.EbayOrder(orderid).load(function(order_info) {
                    $('button.print_order').show();
                    if (order_info.label_print_time) {
                        $('button.print_order').text('print again');
                        $('.print_ok').text("printed at "+order_info.label_print_time).show();
                    }
                });
                
                /*
                 * manage print button click
                 */
                $('[name="print"]').click(function () {
                    window.print();
                    setTimeout(print_finish, 0);
                });
                
                /*
                 * what to do when print done
                 */
                function print_finish() {
                    $('button[name="print"]').hide();
                    $('span.print_ok').show();
                    SyncList.Consume.shipping.label_printed(orderid);
                    //$('span.delete_this_history').show();
                }
            }());
        </script>
    </body>
</html>