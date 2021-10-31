<?php
namespace Lifter\MT;

class StudentListTable extends \WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'ajax' => false,
			)
		);
	}


	public function get_columns() {
		$columns = array(
			'id'           => 'Student ID',
			'name'         => 'Student Name',
			'email'        => 'Email Address',
			'class'        => 'Class',
			'section'      => 'Section',
			'no_of_groups' => 'Groups Assigned',
			'action'       => 'Action',
		);
		return $columns;
	}

	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array();

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$per_page              = $this->get_items_per_page( 'users_per_page' );
		$page_num              = $this->get_pagenum();

		$query = new \WP_User_Query(
			array(
				'role'   => 'student',
				'number' => $per_page,
				'offset' => ( $page_num - 1 ) * $per_page,
			)
		);

		$this->items = array_map(
			function ( $student ) {
				return array(
					'id'           => $student->ID,
					'name'         => $student->display_name,
					'email'        => $student->email,
					'class'        => $student->class,
					'section'      => $student->section,
					'no_of_groups' => llms_get_student( $student )->get_memberships( array( 'limit' => 1 ) )['found'],
					'action'       => '',
				);
			},
			$query->get_results()
		);

		$this->set_pagination_args(
			[
				'total_items' => $query->get_total(), //WE have to calculate the total number of items
				'per_page'    => $per_page, //WE have to determine how many items to show on a page
			]
		);
	}

	public function no_items() {
		esc_html__( 'No customers avaliable.', 'llms-school' );
	}

	public function column_default( $item, $column_name ) {
		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : print_r( $item, true );
	}
}

