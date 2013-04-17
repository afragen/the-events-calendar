<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class TribeEventsTicketsAttendeesTable extends WP_List_Table {

	function __construct() {
		parent::__construct( array( 'singular'  => 'attendee', 'plural' => 'attendees', 'ajax' => true ) );
	}

	function search_box() {
		return;
	}

	function pagination(){
		return '';
	}

	function ajax_user_can() {
		return current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_posts );
	}

	function get_columns() {
		$columns = array( 'cb'              => '<input type="checkbox" />',
						  'order_id'        => __( 'Order #', 'tribe-events-calendar' ),
						  'order_status'    => __( 'Order Status', 'tribe-events-calendar' ),
						  'purchaser_name'  => __( 'Purchaser name', 'tribe-events-calendar' ),
						  'purchaser_email' => __( 'Purchaser email', 'tribe-events-calendar' ),
						  'ticket'          => __( 'Ticket type', 'tribe-events-calendar' ),
						  'attendee_id'     => __( 'Ticket #', 'tribe-events-calendar' ),
						  'security'        => __( 'Security Code', 'tribe-events-calendar' ),
						  'check_in'        => __( 'Check in', 'tribe-events-calendar' ) );

		return $columns;
	}


	function column_default( $item, $column ) {
		if ( empty( $item[$column] ) )
			return '';
		return $item[$column];
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', esc_attr( $this->_args['singular'] ), esc_attr( $item['attendee_id'] . "|" . $item['provider'] ) );
	}

	function column_order_id( $item ) {
		return sprintf( '<a class="row-title" href="%s">%s</a>', esc_url( get_edit_post_link( $item['order_id'], true ) ), esc_html( $item['order_id'] ) );
	}

	function column_order_status( $item ) {

		$icon = "";

		if ( strtolower( $item['order_status'] ) !== 'completed' ) {
			$tec  = TribeEvents::instance();
			$icon = sprintf( "<span class='warning'><img src='%s'/></span> ", trailingslashit( $tec->pluginUrl ) . 'resources/warning.png' );
		}

		return $icon . ucwords( $item['order_status'] );
	}

	function column_check_in( $item ) {
		$checkin =  sprintf( '<a href="#" data-attendee-id="%d" data-provider="%s" class="button-secondary tickets_checkin">%s</a>', esc_attr( $item['attendee_id'] ), esc_attr( $item['provider'] ), __( 'Check in', 'tribe-events-calendar' ) );
		$uncheckin =  sprintf( '<span class="delete"><a href="#" data-attendee-id="%d" data-provider="%s" class="tickets_uncheckin">%s</a></span>', esc_attr( $item['attendee_id'] ), esc_attr( $item['provider'] ), __( 'Undo Check in', 'tribe-events-calendar' ) );

		return $checkin . $uncheckin;
	}

	function single_row( $item ) {
		static $row_class = '';
		$row_class = ( $row_class == '' ? ' alternate ' : '' );

		$checked = '';
		if ( intval( $item["check_in"] ) === 1 )
			$checked = ' tickets_checked ';

		echo '<tr class="' . sanitize_html_class( $row_class  ) . $checked . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}


	function extra_tablenav( $which ) {

		echo '<div class="alignleft actions">';

		echo sprintf( '<input type="button" name="print" class="print button action" value="%s">', __( 'Print', 'tribe-events-calendar' ) );
		echo sprintf( '<input type="button" name="email" class="email button action" value="%s">', __( 'Email', 'tribe-events-calendar' ) );
		echo sprintf( '<a href="%s" class="export button action">%s</a>', esc_url( add_query_arg( array( "attendees_csv" => true, "attendees_csv_nonce" => wp_create_nonce( 'attendees_csv_nonce' ) ) ) ), __( 'Export', 'tribe-events-calendar' ) );

		echo '</div>';

		if ( 'top' == $which ) {
			echo '<div class="alignright">';
			echo sprintf( '%s: <input type="text" name="filter_attendee" id="filter_attendee" value="">', __( "Filter by ticket #, order # or security code", "tribe-events-calendar" ) );
			echo '</div>';

		}
	}

	function get_bulk_actions() {
		$actions = array( 'check_in' => __( 'Check in', 'tribe-events-calendar' ), 'uncheck_in' => __( 'Undo Check in', 'tribe-events-calendar' ) );
		return $actions;

	}


	function process_bulk_action() {
		if ( 'check_in' === $this->current_action() ) {
			if ( isset( $_GET['attendee'] ) ) {

				foreach ( $_GET['attendee'] as $attendee_provider ) {
					$vars = explode( "|", $attendee_provider );
					if ( isset( $vars[1] ) ) {
						$obj = call_user_func( array( $vars[1], 'get_instance' ) );
						$obj->checkin( $vars[0] );
					}

				}

			}
		}


		if ( 'uncheck_in' === $this->current_action() ) {
			if ( isset( $_GET['attendee'] ) ) {

				foreach ( $_GET['attendee'] as $attendee_provider ) {
					$vars = explode( "|", $attendee_provider );
					if ( isset( $vars[1] ) ) {
						$obj = call_user_func( array( $vars[1], 'get_instance' ) );
						$obj->uncheckin( $vars[0] );
					}

				}

			}
		}

	}

	function prepare_items() {

		$this->process_bulk_action();

		$event_id = isset( $_GET['event_id'] ) ? $_GET['event_id'] : 0;

		$items = TribeEventsTickets::get_event_attendees( $event_id );


		$this->items = $items;
		$total_items = count( $this->items );
		$per_page    = $total_items;

		$this->set_pagination_args( array( 'total_items' => $total_items,
		                                   'per_page'    => $per_page,
		                                   'total_pages' => 1 ) );

	}


}
