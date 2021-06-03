<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_View_Page
 *
 * @since 1.0
 */
abstract class Forminator_Admin_View_Page extends Forminator_Admin_Page {
		/**
	 * Current model
	 *
	 * @var object|bool
	 */
	protected $model = false;

	/**
	 * Current form id
	 *
	 * @var int
	 */
	protected $form_id = 0;

	/**
	 * Fields
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Visible Fields
	 *
	 * @var array
	 */
	protected $visible_fields = array();

	/**
	 * Number of checked fields
	 *
	 * @var int
	 */
	protected $checked_fields = 0;

	/**
	 * Number of total fields
	 *
	 * @var int
	 */
	protected $total_fields = 0;

	/**
	 * Error message if avail
	 *
	 * @var string
	 */
	protected $error_message = '';

	/**
	 * Total Entries
	 *
	 * @var int
	 */
	protected $total_entries = 0;

	/**
	 * Per page
	 *
	 * @var int
	 */
	protected $per_page = 10;

	/**
	 * Page number
	 *
	 * @var int
	 */
	protected $page_number = 1;

	/**
	 * Filters to be used
	 *
	 * [key=>value]
	 * ['search'=>'search term']
	 *
	 * @since 1.5.4
	 * @var array
	 */
	public $filters = array();

	/**
	 * Order to be used
	 *
	 * [key=>order]
	 * ['entry_date' => 'ASC']
	 *
	 * @since 1.5.4
	 * @var array
	 */
	public $order = array();

	/**
	 * Entries
	 *
	 * @var array
	 */
	protected $entries = array();

	/**
	 * Total filtered Entries
	 *
	 * @since 1.5.4
	 * @var int
	 */
	protected $filtered_total_entries = 0;

	/**
	 * Flag fields is currently filtered
	 *
	 * @since 1.5.4
	 * @var bool
	 */
	public $fields_is_filtered = false;

	/**
	 * @var Forminator_Addon_Abstract[]
	 */
	protected static $connected_addons = null;

	/**
	 * @var Forminator_Addon_Abstract[]
	 */
	protected static $registered_addons = null;

	/** @noinspection PhpMissingParentConstructorInspection
	 *
	 * Construct Entries Renderer
	 *
	 * @since 1.0.5
	 *
	 * @param string $folder
	 */
	protected function entries_construct( $folder ) {
		$this->folder = $folder;
		$this->before_render();
		$this->trigger_before_render_action();
		$this->add_page_hooks();
	}

	/**
	 * Get fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Visible fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_visible_fields() {
		return $this->visible_fields;
	}

	/**
	 * Checked field option
	 *
	 * @since 1.0
	 *
	 * @param string $slug - the field slug
	 *
	 * @return string
	 */
	public function checked_field( $slug ) {
		if ( ! empty( $this->visible_fields ) && is_array( $this->visible_fields ) ) {
			if ( in_array( $slug, $this->visible_fields, true ) ) {
				return checked( $slug, $slug );
			} else {
				return '';
			}
		}

		return checked( $slug, $slug );
	}

	/**
	 * Get model name
	 *
	 * @since 1.0
	 * @return string
	 */
	public function get_model_name() {
		if ( $this->model ) {
			return $this->model->name;
		}

		return '';
	}

	/**
	 * Fields header
	 *
	 * @since 1.0
	 */
	public function fields_header() {
		echo esc_html( sprintf( __( 'Showing %$1s of %$2s fields', 'forminator' ), $this->checked_fields, $this->total_fields ) );
	}

	/**
	 * The total entries
	 *
	 * @since 1.0
	 * @return int
	 */
	public function total_entries() {
		return $this->total_entries;
	}

	/**
	 * The total filtered entries
	 *
	 * @since 1.5.4
	 * @return int
	 */
	public function filtered_total_entries() {
		return $this->filtered_total_entries;
	}

	/**
	 * Get Entries
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_entries() {
		return $this->entries;
	}

	/**
	 * Get Page Number
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_page_number() {
		return $this->page_number;
	}

	/**
	 * Get Per Page
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_per_page() {
		return $this->per_page;
	}

	/**
	 * Get fields table
	 *
	 * @since 1.0
	 * @return Forminator_Entries_List_Table
	 */
	public function get_table() {
		return new Forminator_Entries_List_Table(
			array(
				'model'          => $this->model,
				'visible_fields' => $this->visible_fields,
			)
		);
	}

	/**
	 * Pagination
	 *
	 * @since 1.0
	 */
	public function paginate() {
		$count = $this->filtered_total_entries;
		echo '<span class="sui-pagination-results">'
				. esc_html( sprintf( _n( '%s result', '%s results', $count, 'forminator' ), $count ) )
			. '</span>';
		forminator_list_pagination( $count, 'entries' );
	}

	/**
	 * Bulk actions
	 *
	 * @since 1.0
	 *
	 * @param string $position
	 */
	public function bulk_actions( $position = 'top' ) { ?>

		<select name="<?php echo ( 'top' === $position ) ? 'entries-action' : 'entries-action-bottom'; ?>"
				class="sui-select-sm sui-select-inline"
				style="min-width: 200px;">
			<option value=""><?php esc_html_e( "Bulk Actions", 'forminator' ); ?></option>
			<option value="delete-all"><?php esc_html_e( "Delete Entries", 'forminator' ); ?></option>
		</select>

		<button class="sui-button"><?php esc_html_e( "Apply", 'forminator' ); ?></button>

		<?php
	}

	/**
	 * Prepare results
	 *
	 * @since 1.0
	 */
	public function prepare_results() {
		if ( is_object( $this->model ) ) {
			$paged    = $this->page_number;
			$per_page = $this->per_page;
			$offset   = ( $paged - 1 ) * $per_page;

			$this->total_entries = Forminator_Form_Entry_Model::count_entries( $this->model->id );

			$args = array(
				'form_id'  => $this->model->id,
				'is_spam'  => 0,
				'per_page' => $per_page,
				'offset'   => $offset,
				'order_by' => 'entries.date_created',
				'order'    => 'DESC',
			);

			$args = wp_parse_args( $this->filters, $args );
			$args = wp_parse_args( $this->order, $args );

			$count = 0;

			$this->entries                = Forminator_Form_Entry_Model::query_entries( $args, $count );
			$this->filtered_total_entries = $count;
		}
	}

	/**
	 * Called when page is loaded and content not rendered yet
	 *
	 * @since 1.0
	 */
	public function before_render() {
		$this->model = Forminator_Base_Form_Model::get_model( $this->form_id );
		if ( is_object( $this->model ) ) {
			$this->fields = $this->model->get_fields();
			if ( is_null( $this->fields ) ) {
				$this->fields = array();
			}
		} else {
			$this->model = false;
		}

		$this->pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0; // WPCS: CSRF OK

		$this->parse_filters();
		$this->parse_order();

		$this->per_page       = forminator_form_view_per_page( 'entries' );
		$this->page_number    = max( 1, $this->pagenum );
		$this->total_fields   = count( $this->fields );
		$this->checked_fields = $this->total_fields;
	}

	/**
	 * Parsing filters from $_REQUEST
	 *
	 * @since 1.5.4
	 */
	protected function parse_filters() {
		$request_data = $_REQUEST;// WPCS CSRF ok.
		$data_range   = isset( $request_data['date_range'] ) ? sanitize_text_field( $request_data['date_range'] ) : '';
		$search       = isset( $request_data['search'] ) ? sanitize_text_field( $request_data['search'] ) : '';
		$min_id       = isset( $request_data['min_id'] ) ? sanitize_text_field( $request_data['min_id'] ) : '';
		$max_id       = isset( $request_data['max_id'] ) ? sanitize_text_field( $request_data['max_id'] ) : '';

		$filters = array();
		if ( ! empty( $data_range ) ) {
			$date_ranges = explode( ' - ', $data_range );
			if ( is_array( $date_ranges ) && isset( $date_ranges[0] ) && isset( $date_ranges[1] ) ) {
				$date_ranges[0] = date( 'Y-m-d', strtotime( $date_ranges[0] ) );
				$date_ranges[1] = date( 'Y-m-d', strtotime( $date_ranges[1] ) );

				forminator_maybe_log( __METHOD__, $date_ranges );
				$filters['date_created'] = array( $date_ranges[0], $date_ranges[1] );
			}
		}
		if ( ! empty( $search ) ) {
			$filters['search'] = $search;
		}

		if ( ! empty( $min_id ) ) {
			$min_id = intval( $min_id );
			if ( $min_id > 0 ) {
				$filters['min_id'] = $min_id;
			}
		}

		if ( ! empty( $max_id ) ) {
			$max_id = intval( $max_id );
			if ( $max_id > 0 ) {
				$filters['max_id'] = $max_id;
			}
		}

		$this->filters = $filters;
	}

	/**
	 * Parsing order from $_REQUEST
	 *
	 * @since 1.5.4
	 */
	protected function parse_order() {
		$valid_order_bys = array(
			'entries.date_created',
			'entries.entry_id',
		);

		$valid_orders = array(
			'DESC',
			'ASC',
		);
		$request_data = $_REQUEST;// WPCS CSRF ok.
		$order_by     = 'entries.date_created';
		if ( isset( $request_data['order_by' ] ) ) {
			switch ( $request_data['order_by' ] ) {
				case 'entries.entry_id':
					$order_by = 'entries.entry_id';
					break;
				case 'entries.date_created':
					$order_by = 'entries.date_created';
					break;
				default:
					break;
			}
		}

		$order = 'DESC';
		if ( isset( $request_data['order'] ) ) {
			switch ( $request_data['order' ] ) {
				case 'DESC':
					$order = 'DESC';
					break;
				case 'ASC':
					$order = 'ASC';
					break;
				default:
					break;
			}
		}

		if ( ! empty( $order_by ) ) {
			if ( ! in_array( $order, $valid_order_bys, true ) ) {
				$order_by = 'entries.date_created';
			}

			$this->order['order_by'] = $order_by;
		}

		if ( ! empty( $order ) ) {
			$order = strtoupper( $order );
			if ( ! in_array( $order, $valid_orders, true ) ) {
				$order = 'DESC';
			}

			$this->order['order'] = $order;
		}
	}

	/**
	 * Executor of adding additional items on entry page
	 *
	 * @see   self::on_render_entry()
	 * @since 1.1
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 *
	 * @return array
	 */
	protected function attach_addon_on_render_entry( Forminator_Form_Entry_Model $entry_model ) {
		$additional_items = array();
		//find all registered addons, so history can be shown even for deactivated addons
		$registered_addons = $this->get_registered_addons();

		$method = 'get_addon_' . static::$module_slug . '_hooks';
		foreach ( $registered_addons as $registered_addon ) {
			try {
				$hooks     = $registered_addon->{$method}( $this->form_id );
				$meta_data = forminator_find_addon_meta_data_from_entry_model( $registered_addon, $entry_model );

				$addon_additional_items = $hooks->on_render_entry( $entry_model, $meta_data );// run and forget.
				$addon_additional_items = self::format_addon_additional_items( $addon_additional_items );
				$additional_items       = array_merge( $additional_items, $addon_additional_items );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $registered_addon->get_slug(), 'failed to on_render_entry', $e->getMessage() );
			}
		}

		return $additional_items;
	}

	/**
	 * Flag whether box filter opened or nope
	 *
	 * @since 1.5.4
	 * @return bool
	 */
	protected function is_filter_box_enabled() {
		return ( ! empty( $this->filters ) && ! empty( $this->order ) );
	}

	/**
	 * Get current error message
	 *
	 * @return string
	 *
	 * @since 1.5.2
	 */
	public function error_message() {
		return $this->error_message;
	}

	/**
	 * Get Globally registered Addons, avoid overhead for checking registered addons many times
	 *
	 * @return array|Forminator_Addon_Abstract[]
	 */
	public function get_registered_addons() {
		if ( empty( self::$registered_addons ) ) {
			self::$registered_addons = array();

			$registered_addons = forminator_get_registered_addons();
			$method            = 'get_addon_' . static::$module_slug . '_hooks';
			$class_name        = 'Forminator_Addon_' . forminator_get_prefix( static::$module_slug, 'c', true ) . '_Hooks_Abstract';
			foreach ( $registered_addons as $registered_addon ) {
				try {
					$hooks = $registered_addon->{$method}( $this->form_id );
					if ( $hooks instanceof $class_name ) {
						self::$registered_addons[] = $registered_addon;
					}
				} catch ( Exception $e ) {
					forminator_addon_maybe_log( $registered_addon->get_slug(), 'failed to ' . $method, $e->getMessage() );
				}
			}
		}

		return self::$registered_addons;
	}

	/**
	 * Process request
	 *
	 * @since 1.0
	 */
	public function process_request() {

		if ( isset( $_GET['err_msg'] ) ) {
			$this->error_message = wp_kses_post( $_GET['err_msg'] );
		}

		// it should be before nonce check cus we use filter on Submissions page without nonce :facepalm:.
		if ( isset( $_REQUEST['field'] ) ) {
			$this->visible_fields     = $_REQUEST['field']; // wpcs XSRF ok, via GET
			$this->checked_fields     = count( $this->visible_fields );
			$this->fields_is_filtered = true;
		}

		/**
		 * Start modifying data
		 */
		if ( ! isset( $_REQUEST['forminatorEntryNonce'] ) ) {
			return;
		}

		$nonce = $_REQUEST['forminatorEntryNonce']; // WPCS: CSRF OK
		if ( ! wp_verify_nonce( $nonce, 'forminator' . forminator_get_prefix( static::$module_slug, '', true ) . 'Entries' ) ) {
			return;
		}

		if ( isset( $_REQUEST['entries-action'] ) || isset( $_REQUEST['entries-action-bottom'] ) ) {
			$action = '';
			if ( ! empty( $_REQUEST['entries-action'] ) ) {
				$action = sanitize_text_field( $_REQUEST['entries-action'] );
			} elseif ( isset( $_REQUEST['entries-action-bottom'] ) ) {
				$action = sanitize_text_field( $_REQUEST['entries-action-bottom'] );
			}

			switch ( $action ) {
				case 'delete-all' :
					$this->delete_all_action();
					break;
				default:
					break;
			}
		}

		if ( isset( $_POST['forminator_action'] ) ) {
			switch ( $_POST['forminator_action'] ) {
				case 'delete':
					if ( isset( $_POST['id'] ) ) {
						$id = $_POST['id'];

						$this->delete_action( $id );
						$this->maybe_redirect_to_referer();
						exit;
					}
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Action delete
	 *
	 * @param int $id ID.
	 */
	public function delete_action( $id ) {
		Forminator_Form_Entry_Model::delete_by_entrys( $this->model->id, $id );
	}

	/**
	 * This view is unused from 1.5.4 on, using "forminator-entries" instead.
	 */
	protected function maybe_redirect() {
		if ( 'forminator-' . forminator_get_prefix( static::$module_slug, 'c' ) . '-view' === $this->page_slug ) {
			$form_type = forminator_get_prefix( static::$module_slug, 'post_type' );
			$url       = '?page=forminator-entries&form_type=' . $form_type;
			if ( isset( $_REQUEST['form_id'] ) ) { // WPCS: CSRF OK
				$url .= '&form_id=' .  intval( $_REQUEST['form_id'] ); // WPCS: CSRF OK
			}
			if ( wp_safe_redirect( $url ) ) {
				exit;
			}
		}
	}
}
