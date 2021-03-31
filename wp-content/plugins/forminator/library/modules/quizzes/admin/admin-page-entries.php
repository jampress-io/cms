<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class Forminator_Quizz_View_Page
 *
 * @since 1.0
 */
class Forminator_Quizz_View_Page extends Forminator_Admin_Page {

	/**
	 * Current model
	 *
	 * @var bool|Forminator_Quiz_Form_Model
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
	 * Fields
	 *
	 * @var array
	 */
	protected $lead_fields = array();



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
	 * Error message if avail
	 *
	 * @var string
	 */
	protected $error_message = '';

	/**
	 * @since 1.6.2
	 * @var Forminator_Addon_Abstract[]
	 */
	private static $registered_addons = null;

	/**
	 * @since 1.6.2
	 * @var Forminator_Addon_Abstract[]
	 */
	protected $lead_cform = null;

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
	 * Total Entries
	 *
	 * @var int
	 */
	protected $total_entries = 0;

	/**
	 * Initialise variables
	 *
	 * @since 1.0
	 */
	public function before_render() {
		// This view is unused from 1.5.4 on, using "forminator-entries" instead.
		if ( 'forminator-quiz-view' === $this->page_slug ) {
			$url = '?page=forminator-entries&form_type=forminator_quizzes';
			if ( isset( $_REQUEST['form_id'] ) ) { // WPCS: CSRF OK
				$url .= '&form_id=' . intval( $_REQUEST['form_id'] ); // WPCS: CSRF OK
			}
			if ( wp_safe_redirect( $url ) ) {
				exit;
			}
		}

		if ( isset( $_REQUEST['form_id'] ) ) { // WPCS: CSRF OK
			$this->form_id = intval( $_REQUEST['form_id'] );
			$this->model   = Forminator_Quiz_Form_Model::model()->load( $this->form_id );
			if ( is_object( $this->model ) ) {
				$this->fields = $this->model->get_fields();
				if ( is_null( $this->fields ) ) {
					$this->fields = array();
				}
			} else {
				$this->model = false;
			}

			$pagenum = isset( $_REQUEST['paged'] ) ? absint( $_REQUEST['paged'] ) : 0; // WPCS: CSRF OK

			$this->parse_filters();
			$this->parse_order();

			$this->per_page       = forminator_form_view_per_page( 'entries' );
			$this->page_number    = max( 1, $pagenum );
			$this->total_fields   = count( $this->fields ) + 1;
			$this->checked_fields = $this->total_fields;
			$this->process_request();
			$this->prepare_results();
		}

		if ( $this->has_leads() ) {
			$this->lead_cform = new Forminator_CForm_View_Page( 'forminator-quiz-view', 'custom-form/entries', __( 'Submissions:', Forminator::DOMAIN ), __( 'View Custom Form', Forminator::DOMAIN ), 'forminator' );
			$this->lead_cform->before_render( $this->lead_id() );
			$this->lead_fields = $this->lead_cform->get_fields();
        }
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

		if ( isset( $_REQUEST['field'] ) ) {
			$this->visible_fields     = $_REQUEST['field']; // wpcs XSRF ok, via GET
			$this->checked_fields     = count( $this->visible_fields );
			$this->fields_is_filtered = true;
		}

		if ( ! isset( $_REQUEST['forminatorEntryNonce'] ) ) {
			return;
		}

		$nonce = $_REQUEST['forminatorEntryNonce']; // WPCS: CSRF OK
		if ( ! wp_verify_nonce( $nonce, 'forminatorQuizEntries' ) ) {
			return;
		}

		$action = '';
        if ( isset( $_REQUEST['entries-action'] ) || isset( $_REQUEST['entries-action-bottom'] ) ) {
            if ( isset( $_REQUEST['entries-action'] ) && ! empty( $_REQUEST['entries-action'] ) ) {
                $action = $_REQUEST['entries-action'];
            } elseif ( isset( $_REQUEST['entries-action-bottom'] ) ) {
                $action = $_REQUEST['entries-action-bottom'];
            }

            switch ( $action ) {
                case 'delete-all' :
                    if ( isset( $_REQUEST['ids'] ) && is_array( $_REQUEST['ids'] ) ) {
                        $entries = implode( ",", $_REQUEST['ids'] );
                        Forminator_Form_Entry_Model::delete_by_entrys( $this->model->id, $entries );
                        $this->maybe_redirect_to_referer();
                        exit;
                    }
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

						Forminator_Form_Entry_Model::delete_by_entrys( $this->model->id, $id );
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
	 * Register content boxes
	 *
	 * @since 1.0
	 */
	public function register_content_boxes() {
		$this->add_box(
			'custom-form/entries/popup/exports-list',
			__( 'Your Exports', Forminator::DOMAIN ),
			'entries-popup-exports-list',
			null,
			null,
			null
		);

		$this->add_box(
			'custom-form/entries/popup/schedule-export',
			__( 'Edit Schedule Export', Forminator::DOMAIN ),
			'entries-popup-schedule-export',
			null,
			null,
			null
		);
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
	 * Get fields
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_lead_fields() {
		return $this->lead_fields;
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
		echo esc_html( sprintf( __( 'Showing %$1s of %$2s fields', Forminator::DOMAIN ), $this->checked_fields, $this->total_fields ) );
	}

	/**
	 * Check if quiz has leads
	 *
	 * @since 1.14
	 *
	 * @return bool
	 */
	public function has_leads() {
		if ( isset( $this->model->settings['hasLeads'] ) && "true" === $this->model->settings['hasLeads'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if quiz lead id
	 *
	 * @since 1.14
	 *
	 * @return int
	 */
	public function lead_id() {
		if ( isset( $this->model->settings['leadsId'] ) ) {
			return $this->model->settings['leadsId'];
		}

		return 0;
	}

	/**
	 * Get fields table
	 *
	 * @since 1.0
	 * @return array
	 */
	public function get_table() {
		$per_page = $this->get_per_page();
		$entries  = Forminator_Form_Entry_Model::list_entries( $this->form_id, $per_page, ( $this->get_paged() - 1 ) * $per_page );

		return $entries;
	}

	/**
	 * Get paged
	 *
	 * @since 1.0
	 * @return int
	 */
	public function get_paged() {
		$paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;

		return $paged;
	}

	/**
	 * Get the results per page
	 *
	 * @since 1.0.3
	 *
	 * @return int
	 */
	public function get_per_page() {
		return $this->per_page;
	}

	/**
	 * @since 1.0
	 * @return int
	 */
	public function get_total_entries() {
		$count = Forminator_Form_Entry_Model::count_entries( $this->form_id );

		return $count;
	}

	/**
	 * Get form type
	 *
	 * @since 1.0
	 * @return mixed
	 */
	public function get_form_type() {
		return $this->model->quiz_type;
	}

	/**
	 * Get form type param
	 *
	 * @since 1.5.4
	 * @return string
	 */
	protected function forminator_get_form_type() {
		return ( isset( $_GET['form_type'] ) ? sanitize_text_field( $_GET['form_type'] ) : '' );
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
			<option value=""><?php esc_html_e( "Bulk Actions", Forminator::DOMAIN ); ?></option>
			<option value="delete-all"><?php esc_html_e( "Delete Entries", Forminator::DOMAIN ); ?></option>
		</select>

		<button class="sui-button"><?php esc_html_e( "Apply", Forminator::DOMAIN ); ?></button>

		<?php
	}

	/**
	 * Pagination
	 *
	 * @since 1.1
	 */
	public function paginate() {
		$count = $this->get_total_entries();
		forminator_list_pagination( $count, 'entries' );
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
	 * Get integrations data
	 *
	 * @since 1.6.2
	 *
	 * @param Forminator_Form_Entry_Model $entry
	 *
	 * @return array
	 */
	public function get_integrations_data_from_entry( Forminator_Form_Entry_Model $entry ) {
		return $this->attach_addon_on_render_entry( $entry );
	}

	/**
	 * Get Globally registered Addons, avoid overhead for checking registered addons many times
	 *
	 * @since 1.6.2
	 *
	 * @return array|Forminator_Addon_Abstract[]
	 */
	public function get_registered_addons() {
		if ( empty( self::$registered_addons ) ) {
			self::$registered_addons = array();

			$registered_addons = forminator_get_registered_addons();
			foreach ( $registered_addons as $registered_addon ) {
				try {
					$quiz_hooks = $registered_addon->get_addon_quiz_hooks( $this->form_id );
					if ( $quiz_hooks instanceof Forminator_Addon_Quiz_Hooks_Abstract ) {
						self::$registered_addons[] = $registered_addon;
					}
				} catch ( Exception $e ) {
					forminator_addon_maybe_log( $registered_addon->get_slug(), 'failed to get_addon_quiz_hooks', $e->getMessage() );
				}
			}
		}

		return self::$registered_addons;
	}

	/**
	 * Executor of adding additional items on entry page
	 *
	 * @see   Forminator_Addon_Quiz_Hooks_Abstract::on_render_entry()
	 * @since 1.6.2
	 *
	 * @param Forminator_Form_Entry_Model $entry_model
	 *
	 * @return array
	 */
	private function attach_addon_on_render_entry( Forminator_Form_Entry_Model $entry_model ) {
		$additional_items = array();
		//find all registered addons, so history can be shown even for deactivated addons
		$registered_addons = $this->get_registered_addons();

		foreach ( $registered_addons as $registered_addon ) {
			try {
				$quiz_hooks = $registered_addon->get_addon_quiz_hooks( $this->form_id );
				$meta_data  = forminator_find_addon_meta_data_from_entry_model( $registered_addon, $entry_model );

				$addon_additional_items = $quiz_hooks->on_render_entry( $entry_model, $meta_data );// run and forget
				$addon_additional_items = self::format_addon_additional_items( $addon_additional_items );
				$additional_items       = array_merge( $additional_items, $addon_additional_items );
			} catch ( Exception $e ) {
				forminator_addon_maybe_log( $registered_addon->get_slug(), 'failed to on_render_entry', $e->getMessage() );
			}
		}

		return $additional_items;
	}

	/**
	 * Ensuring additional items for addons met the entries data requirement
	 * Format used is,
	 * - label
	 * - value
	 * - subentries[]
	 *      - label
	 *      - value
	 *
	 * @since 1.6.2
	 *
	 * @param  array $addon_additional_items
	 *
	 * @return mixed
	 */
	private static function format_addon_additional_items( $addon_additional_items ) {
		//to `name` and `value` basis
		$formatted_additional_items = array();
		if ( ! is_array( $addon_additional_items ) ) {
			return array();
		}

		foreach ( $addon_additional_items as $additional_item ) {
			// make sure label and value exist, without it, it will display empty row, so leave it
			if ( ! isset( $additional_item['label'] ) || ! isset( $additional_item['value'] ) ) {
				continue;
			}
			$sub_entries = array();

			// do below check if sub_entries available
			if ( isset( $additional_item['sub_entries'] ) && is_array( $additional_item['sub_entries'] ) ) {
				foreach ( $additional_item['sub_entries'] as $sub_entry ) {
					// make sure label and value exist, without it, it will display empty row, so leave it
					if ( ! isset( $sub_entry['label'] ) || ! isset( $sub_entry['value'] ) ) {
						continue;
					}
					$sub_entries[] = array(
						'label' => $sub_entry['label'],
						'value' => $sub_entry['value'],
					);
				}
			}

			$formatted_additional_items[] = array(
				'label'       => $additional_item['label'],
				'value'       => $additional_item['value'],
				'sub_entries' => $sub_entries,
			);
		}

		return $formatted_additional_items;
	}

	/**
	 * @return array
	 */
	public function entries_iterator() {
		$entries_data = array();
		$entries      = $this->entries;
		if ( $this->lead_cform ) {
			$entries_data = $this->lead_cform->entries_iterator( $entries, 'quiz' );
		} else {
			foreach ( $entries as $entry ) {
				$entries_data = array(
					'entry_id'   => $entry->entry_id,
					'entry_date' => $entry->time_created,
					'summary'    => array(),
					'detail'     => array(),
				);

				$entries_data['summary']['num_fields_left'] = 0;
				$entries_data['summary']['items']           = array();

				$entries_data['detail']['colspan'] = 0;
				$entries_data['detail']['items']   = array();

				$entries_data['detail']['quiz_entry']   = isset( $entry->meta_data['entry'] ) ? $entry->meta_data['entry'] : array();
				$entries_data['detail']['quiz_url']     = isset( $entry->meta_data['quiz_url'] ) ? $entry->meta_data['quiz_url'] : array();
            }
        }

		return $entries_data;

	}

	/**
	 * Build Html Entries Header
	 */
	public function entries_header() {
		if ( $this->lead_cform ) {
			$this->lead_cform->entries_header();
		} else { ?>
            <thead>
                <tr>
                    <th>
                        <label class="sui-checkbox">
                            <input id="wpf-cform-check_all" type="checkbox">
                            <span></span>
                            <div class="sui-description"><?php esc_html_e( 'ID', Forminator::DOMAIN ); ?></div>
                        </label>
                    </th>
                    <th colspan="5"><?php esc_html_e( 'Date Submitted', Forminator::DOMAIN ); ?></th>
                </tr>
            </thead>
        <?php }
	}

	/**
	 * Return admin edit url
	 *
	 * @since 1.0
	 *
	 * @param $type
	 * @param $id
	 *
	 * @return mixed
	 */
	public function getAdminEditUrl( $type, $id ) {
		if ( 'nowrong' === $type ) {
			return admin_url( 'admin.php?page=forminator-nowrong-wizard&id=' . $id );
		} else {
			return admin_url( 'admin.php?page=forminator-knowledge-wizard&id=' . $id );
		}
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
	 * Flag whether box filter opened or nope
	 *
	 * @since 1.5.4
	 * @return bool
	 */
	protected function is_filter_box_enabled() {
		return ( ! empty( $this->filters ) && ! empty( $this->order ) );
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
}
