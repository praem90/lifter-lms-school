<?php

namespace Lifter\MT;

class LifterMt {

	const VERSION = '0.0.1';

	public $plugin_basename;

	public function with_basename( $basename ) {
		$this->plugin_basename = $basename;

		return $this;
	}

	public function boot() {
		// $this->abort_if_basic_version_is_installed();

		$this->register_hooks();
	}

	public function register_hooks() {
		// add_filter( 'plugin_action_links_' . $this->plugin_basename, array( $this, 'action_link' ) );

		add_action( 'init', array( $this, 'load_language' ) );
		add_action( 'init', array( $this, 'enqueue_scripts' ) );
		add_action( 'init', array( $this, 'register_routes' ) );
		add_action( 'init', array( $this, 'register_schools_post_type' ) );

		add_action( 'admin_menu', array( $this, 'add_admin_main_menu' ) );
		add_action( 'post_row_actions', array( $this, 'register_school_row_actions' ) );
		add_action( 'admin_init', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function abort_if_basic_version_is_installed() {
		if ( function_exists( 'llms' ) ) {
			return;
		}

		$wschat = $this;
		add_action(
			'admin_notices',
			function () use ( $wschat ) {
				is_admin() && add_filter( 'gettext', array( $wschat, 'translate_wschat_admin_notice' ), 99, 3 );
			},
			99
		);
		deactivate_plugins( $this->plugin_basename );

		wp_die( 'This works with Lifter LMS WordPress plugin only' . esc_attr( admin_url( 'plugins.php' ) ) . "'>plugins page</a>" );
	}

	public function register_schools_post_type() {
			register_post_type(
				'llms_school',
				array(
					'labels'              => array(
						'name'               => __( 'Schools', 'lifterlms-schools' ),
						'title'              => __( 'Name', 'lifterlms-schools' ),
						'singular_name'      => __( 'School', 'lifterlms-schools' ),
						'menu_name'          => _x( 'Schools', 'Admin menu name', 'lifterlms-schools' ),
						'add_new'            => __( 'Add School', 'lifterlms-schools' ),
						'add_new_item'       => __( 'Add New School', 'lifterlms-schools' ),
						'edit'               => __( 'Edit', 'lifterlms-schools' ),
						'edit_item'          => __( 'Edit School', 'lifterlms-schools' ),
						'new_item'           => __( 'New School', 'lifterlms-schools' ),
						'view'               => __( 'View School', 'lifterlms-schools' ),
						'view_item'          => __( 'View School', 'lifterlms-schools' ),
						'search_items'       => __( 'Search Schools', 'lifterlms-schools' ),
						'not_found'          => __( 'No Schools found', 'lifterlms-schools' ),
						'not_found_in_trash' => __( 'No Schools found in trash', 'lifterlms-schools' ),
						'parent'             => __( 'Parent School', 'lifterlms-schools' ),
					),
					'description'         => __( 'This is where you can add new schools.', 'lifterlms-schools' ),
					'public'              => true,
					'show_ui'             => true,
					// 'capabilities'        => LLMS_Post_Types::get_post_type_caps('school'),
					'map_meta_cap'        => true,
					'publicly_queryable'  => true,
					'exclude_from_search' => false,
					'hierarchical'        => false,
					'rewrite'             => array(
						'slug'       => _x( 'school', 'school url slug', 'lifterlms-schools' ),
						'with_front' => false,
						'feeds'      => true,
					),
					'query_var'           => true,
					'supports'            => array( 'title', 'thumbnail' ),
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
				)
			);
	}

	public function enque_acf_scripts() {
		wp_enqueue_script( 'acf' );
		wp_enqueue_script( 'acf-input' );
		wp_enqueue_script( 'select2' );
		do_action( 'acf/input/admin_enqueue_scripts' );
		add_action(
			'wp_footer',
			function () {
					acf_get_instance( 'ACF_Assets' )->print_footer_scripts();
			}
		);

		add_action(
			'llms_group_profile_after_settings',
			function () {
			global $wp;
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
			echo acf_form(
				[
					'id'              => 'group_school_info',
					'post_id'         => get_post()->id,
					'field_groups'    => [ 'group_617d20b979be4' ],
					'return'          => $current_url,
					'form_attributes' => [
						'action' => admin_url( 'post.php' ),
					],
				]
			);
			}
		);
	}

	public function translate_wschat_admin_notice( $translated_text, $untranslated_text ) {
		$old        = array(
			'Plugin <strong>activated</strong>.',
			'Selected plugins <strong>activated</strong>.',
		);
		$error_text = 'BASIC Version of this Plugin Installed. Please uninstall the BASIC Version before activating PREMIUM.';
		$new        = "<span style='color:red'>" . $error_text . '</span>';

		if ( in_array( $untranslated_text, $old, true ) ) {
			$translated_text = $new;
		}

		return $translated_text;
	}

	public function register_routes() {
		add_action( 'wp_ajax_llms_school_get', array( Student::class, 'get_all' ) );
		add_action( 'wp_ajax_llms_group_get', array( Group::class, 'get_all' ) );
	}

	public function enqueue_admin_scripts() {
		wp_enqueue_script( 'dataTable', 'https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js' , array( 'jquery' ), '1.11.3', true );
		wp_enqueue_script( 'dataTable-bt', 'https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap5.min.js' , array( 'dataTable' ), '1.11.3', true );
		wp_enqueue_style( 'dataTable', 'https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css' , array(), '1.11.3' );
		wp_enqueue_style( 'dataTable-bt', 'https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap5.min.css' , array(), '1.11.3' );
		wp_enqueue_style( 'bootstrap5', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css' , array(), '5.0.2' );

		wp_enqueue_style( 'lifter-mt', plugins_url( 'resources/dist/base.css', dirname( __FILE__ ) ), array(), self::VERSION, true );
		wp_enqueue_script( 'lifter-mt', plugins_url( 'resources/dist/admin.js', dirname( __FILE__ ) ), array( 'jquery' ), self::VERSION, true );
	}

	public function localize_script() {
		wp_localize_script(
			'lifter-mt',
			'lifter_mt',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lifter-mt-ajax-nonce' ),
			)
		);
	}

	public function enqueue_scripts() {
	}

	public function load_language() {
		load_plugin_textdomain( 'lifter-mt', false, __DIR__ . '/../lang' );
	}

	public function action_link( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wschat_chat' ) . '">' . __( 'Chats', 'wschat' ) . '</a>',
			'<a href="' . admin_url( 'admin.php?page=wschat_settings' ) . '">' . __( 'Settings', 'wschat' ) . '</a>',
		);

		if ( array_key_exists( 'deactivate', $links ) ) {
			$links['deactivate'] = str_replace( '<a', '<a class="wschat-deactivate-link"', $links['deactivate'] );
		}

		return array_merge( $plugin_links, $links );
	}

	public function show_school_details() {
		$id = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : -1;

		$school = get_post( $id );

		include_once dirname( __DIR__ ) . '/resources/views/school.php';
	}

	public function add_admin_main_menu() {
		$hook = add_submenu_page(
			null,
			__( 'Welcome', 'llms-school' ),
			__( 'Welcome', 'llms-school' ),
			'manage_options',
			'llms_school_details',
			array( $this, 'show_school_details' )
		);

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'llms_students_export' ) {
			Student::download();
			die;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'llms_groups_export' ) {
			Group::download();
			die;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'llms_course_export' ) {
			Course::download();
			die;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'llms_quiz_export' ) {
			Quiz::download();
			die;
		}
	}

	public function register_school_row_actions( $actions, $post = false ) {
		$post = $post ? $post : get_post();

		if ( 'llms_school' !== $post->post_type ) {
			return $actions;
		}

		$view_link = $this->get_school_details_url( $post );

		$actions['view'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $view_link ),
			'View'
		);

		return $actions;
	}

	public function get_school_details_url( $post = null, $args = array() ) {
		$post = get_post( $post );

		$view_link = admin_url( 'admin.php' );

		$args = array_merge(
			array(
				'page' => 'llms_school_details',
				'post' => $post->ID,
				'tab'  => 'details',
			),
			$args
		);

		return add_query_arg( $args, $view_link );
	}
}
