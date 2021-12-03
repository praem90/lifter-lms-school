<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class School {
	public static function get_all() {
		$self    = new self();
		$filters = $_GET;

		$data['data']            = $self->get( $filters );
		$data['recordsFiltered'] = $self->count( $filters );
		$data['recordsTotal']    = $self->count();

		echo wp_json_encode( $data );
		die;
	}

	public static function download() {
		$self    = new self();
		$filters = $_REQUEST;

		$filters['start']  = 0;
		$filters['length'] = 100;

		$data = $self->get( $filters );
		if ( count( $data ) === 0 ) {
			wp_die( 'There are no students found to export' );
		}
		$fileName = 'school.csv';

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Encoding: utf-8' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( "Content-Disposition: attachment; filename={$fileName}" );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		$df = fopen( 'php://output', 'w' );

		fputcsv( $df, array_keys( $self->map_item( $data[0] ) ) );

		while ( count( $data ) ) {
			foreach ( $data as $row ) {
				fputcsv(
					$df,
					$self->map_item( $row )
				);
			}

			$filters['start'] += $filters['length'];
			$data              = $self->get( $filters );
		}

		fclose( $df );
		exit;
	}

	public function map_item( $row ) {
		$row = array(
			'ID'               => $row['ID'],
			'manual_id'        => isset( $row['school_id_manual'] ) ? $row['school_id_manual'] : $row['ID'],
			'name'             => $row['post_title'],
			'contact_name'     => Arr::get( $row, 'contact_name' ),
			'contact_email'    => Arr::get( $row, 'contact_email' ),
			'contact_mobile'   => Arr::get( $row, 'contact_mobile' ),
			'line_1'           => Arr::get( $row, 'line_1' ),
			'line_2'           => Arr::get( $row, 'line_2' ),
			'city'             => Arr::get( $row, 'city' ),
			'state'            => Arr::get( $row, 'state' ),
			'pincode'          => Arr::get( $row, 'pincode' ),
			'students_count'   => Arr::get( $row, 'students_count' ),
			'groups_count'     => Arr::get( $row, 'groups_count' ),
			'membership_count' => Arr::get( $row, 'groups_count' ),
			'date'             => Arr::get( $row, 'post_date' ),
		);

		return $row;
	}

	public function get( $filters = array() ) {
		$query    = $this->apply_filters( $filters );
		$students = $query->get();

		if ( count( $students ) === 0 ) {
			return $students;
		}

		$this->get_meta( $students );
		$this->get_school( $students );
		$this->get_course_group_membership_count( $students );

		return $students;
	}

	public function apply_filters( $filters = array() ) {
		$query = $this->get_query();

		$query->limit( Arr::get( $filters, 'length', 25 ) );
		$query->offset( Arr::get( $filters, 'start', 0 ) );

		return $query;
	}

	public function count( $filters = array() ) {
		return $this->apply_filters( $filters )->count();
	}

	public function get_query() {
		$query = wpFluent()->table( 'posts' )->where( 'post_type', 'llms_school' );

		return $query;
	}

	public static function get_student_role_query() {
		$role = apply_filters( 'llms_student_role', 'student-b2b' );
		return wpFluent()->table( 'usermeta' )->where( 'meta_key', '=', 'wp_capabilities' )->where( 'meta_value', 'like', '%"' . $role . '"%' )->select( 'user_id' );
	}

	public function get_meta( &$groups ) {
		$query = wpFluent()->table( 'postmeta' )->whereIn( 'post_id', Arr::pluck( $groups, 'ID' ) );

		$query->select( 'school_id', 'meta_key', 'meta_value' );

		$query->whereIn( 'meta_key', array( 'contact_email', 'contact_name', 'school_id_manual', 'contact_mobile', 'line_1', 'line_2', 'city', 'state', 'pincode' ) );

		$meta = $query->get();

		$groups = $this->merge_post_and_meta( $groups, $meta, 'post_id' );

		return $groups;
	}

	public function get_school( &$groups ) {
		$students = wpFluent()->table( 'usermeta' )
			->where( 'meta_key', 'school' )
			->whereIn( 'meta_value', Arr::pluck( $groups, 'ID' ) )
			->select( 'user_id' )
			->groupBy( 'meta_value' )
			->select( 'meta_value', wpFluent()->raw( 'count(user_id) as count' ) )
			->get();

		$students = collect( $students )->pluck( 'count', 'meta_value' );

		$groups = array_map(
			function ( $school ) use ( $students ) {
				$school['students_count'] = $students->get( $school['ID'], 0 );
				return $school;
			},
			$groups
		);

		return $groups;
	}

	public function get_course_group_membership_count( &$schools ) {
		$groups = wpFluent()->table( 'posts' )->where( 'post_type', '=', 'llms_group' )
			->join(
				'postmeta',
				function ( $table ) use ( $schools ) {
					$table->on( 'postmeta.post_id', '=', 'posts.ID' );
					$table->where( 'postmeta.meta_key', '=', 'school' );
					$table->whereIn( 'postmeta.meta_value', Arr::pluck( $schools, 'ID' ) );
				}
			)->groupBy( 'postmeta.meta_value' )
			->select( 'postmeta.meta_value', wpFluent()->raw( 'count(postmeta.post_id) as count' ) );

		$groups = collect( $groups )->pluck( 'count', 'meta_value' );

		$schools = array_map(
			function ( $school ) use ( $groups ) {
				$school['groups_count'] = $groups->get( $school['ID'], 0 );
				return $school;
			},
			$schools
		);

		return $schools;
	}

	public function merge_post_and_meta( $posts, $meta, $meta_key = 'post_id', $post_key = 'ID' ) {
		return array_map(
			function ( $post ) use ( $meta, $meta_key, $post_key ) {
				$user_meta = array_filter(
					$meta,
					function ( $meta_item ) use ( $post, $meta_key, $post_key ) {
						return $meta_item[ $meta_key ] === $post[ $post_key ];
					}
				);
				foreach ( $user_meta as $meta_item ) {
					$post[ $meta_item['meta_key'] ] = $meta_item['meta_value'];
				}
				return $post;
			},
			$posts
		);
	}
}

