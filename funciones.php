<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
add_action('wc_bookings_notification_sent', 'tera_notif_booking_notification_sent', 10, 2);
add_action('woocommerce_booking_status_changed', 'tera_notif_booking_change_status', 10, 4);
add_action('wc-booking-reminder', 'tera_notif_booking_reminder', 10, 1);

function tera_notif_booking_notification_sent($bookings, $notification){
    if(gettype($bookings) == "object"){
        $sub = new WC_Booking($bookings);
        $orden = new WC_Order($sub->get_order_id());
        $phone = $orden->get_billing_phone();
        $phoneFormatted = test_number($phone);

        $msg = $notification->get_subject()."\n\n";
        $msg = $notification->get_content_plain();
        $msg = parseMSG($msg, $orden->get_id());
        $msg = parseMSGBooking($msg, $sub->get_id());
        if($msg != "") tera_notif_text_message($phoneFormatted, $msg);
    }else{
        foreach ($bookings as $book) {
            $sub = new WC_Booking($book);
            $orden = new WC_Order($sub->get_order_id());
            $phone = $orden->get_billing_phone();
            $phoneFormatted = test_number($phone);

            $msg = $notification->get_subject()."\n\n";
            $msg = $notification->get_content_plain();
            $msg = parseMSG($msg, $orden->get_id());
            $msg = parseMSGBooking($msg, $sub->get_id());
            if($msg != "") tera_notif_text_message($phoneFormatted, $msg);
        }
    }
}

function tera_notif_booking_change_status($desde, $a, $booking_id, $booking){
    $sub = new WC_Booking($booking);
    $orden = new WC_Order($sub->get_order_id());
    $phone = $orden->get_billing_phone();
    $phoneFormatted = test_number($phone);

    $msg = get_option("tera_notif_order_booking_".$a);
    $msg = parseMSG($msg, $orden->get_id());
    $msg = parseMSGBooking($msg, $booking_id, $desde);
    if($msg != "") tera_notif_text_message($phoneFormatted, $msg);
}

function tera_notif_booking_reminder($booking_id){
    $sub = new WC_Booking($booking_id);
    $orden = new WC_Order($sub->get_order_id());
    $phone = $orden->get_billing_phone();
    $phoneFormatted = test_number($phone);

    $msg = get_option("tera_notif_order_booking_wc-booking-reminder");
    $msg = parseMSG($msg, $orden->get_id());
    $msg = parseMSGBooking($msg, $booking_id);
    if($msg != "") tera_notif_text_message($phoneFormatted, $msg);
}

add_filter("tera_notif_order_statuses", "add_tera_notif_booking_stats");
add_action("tera_notif_add_variables_page", "add_tera_notif_booking_variables");
function add_tera_notif_booking_stats($stats){
    $stats["booking_unpaid"] = "Reservación Sin Pagar";
    $stats["booking_pending-confirmation"] = "Reservación Pendiente de confirmación";
    $stats["booking_confirmed"] = "Reservación Confirmada";
    $stats["booking_paid"] = "Reservación Pagada";
    $stats["booking_complete"] = "Reservación Completada";
    $stats["booking_cancelled"] = "Reservación Cancelado";
    $stats["booking_wc-booking-reminder"] = "Recordatorio de Reservación";
    return $stats;
}

function add_tera_notif_booking_variables(){ ?>
    <h3 class="text-muted">Detalles de Reservaciones</h3>

    <code class="shortcw">[booking-id]</code>: <span class="text-muted">Reservaciones: ID </span><br>
    <code class="shortcw">[booking-status]</code>: <span class="text-muted">Reservaciones: Estado</span><br>
    <code class="shortcw">[booking-previous-status]</code>: <span class="text-muted">Reservaciones: Estado anterior</span><br>
    <code class="shortcw">[booking-date]</code>: <span class="text-muted">Reservaciones: Fecha de creación</span><br>
    <code class="shortcw">[booking-date-init]</code>: <span class="text-muted">Reservaciones: Fecha de inicio</span><br>
    <code class="shortcw">[booking-date-end]</code>: <span class="text-muted">Reservaciones: Fecha final</span><br>
    <code class="shortcw">[booking-product]</code>: <span class="text-muted">Reservaciones: Producto reservado</span><br>
<?php }

function parseMSGBooking($msg, $booking_id, $from_state = ""){
    $reserva = new WC_Booking($booking_id );

    $estados = [
        "unpaid" => "Sin Pagar",
        "pending-confirmation" => "Pendiente de confirmación",
        "confirmed" => "Confirmada",
        "paid" => "Pagada",
        "complete" => "Completada",
        "in-cart" => "En el Carrito",
        "cancelled" => "Cancelado"
    ];
    $estados = apply_filters("tera_notif_change_booking_status_name", $estados);

    $msg = str_replace("[booking-id]", $booking_id, $msg);
    $msg = str_replace("[booking-status]", $estados[$reserva->get_status()], $msg);
    $msg = str_replace("[booking-previous-status]", $from_state, $msg);
    $msg = str_replace("[booking-date]", date("d/m/Y H:i", $reserva->get_date_created()), $msg);
    $msg = str_replace("[booking-date-init]", $reserva->get_start_date(), $msg);
    $msg = str_replace("[booking-date-end]", $reserva->get_end_date(), $msg);
    $msg = str_replace("[booking-product]", $reserva->get_product()->get_name(), $msg);

    $msg = apply_filters("tera_notif_booking_add_variables", $msg, $booking_id);

    return $msg;
}