<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class Group {
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

		$fileName = 'llms_grups.csv';

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
				fputcsv( $df, $self->map_item( $row ) );
			}

			$filters['start'] += $filters['length'];
			$data              = $self->get( $filters );
		}

		fclose( $df );
		exit;
	}

	public function map_item( $row ) {
		$row = array(
			'ID'             => $row['ID'],
			'group_name'           => $row['post_title'],
			'class'          => isset( $row['class'] ) ? $row['class'] : '',
			'section'        => isset( $row['section'] ) ? $row['section'] : '',
			'membership_id'     => ( isset( $row['membership'] ) ) ? $row['membership']->ID : 'N/A',
			'membership'     => ( isset( $row['membership'] ) ) ? $row['membership']->post_title : 'N/A',
			'courses_count'  => $row['courses_count'],
			'students_count' => $row['students_count'],
			'date'           => $row['post_date'],
		);

		return $row;
	}
	public function get( $filters = array() ) {
		$query  = $this->apply_filters( $filters );
		$groups = $query->get();

		if ( count( $groups ) === 0 ) {
			return $groups;
		}
				$groups = array_map(
			function ( $group ) {
				$group['guid'] = get_edit_post_link( $group['ID'] );
				return $group;
			},
			$groups
		);

		$this->get_meta( $groups );

		$this->get_membership( $groups );
		$this->get_courses_count( $groups );
		$this->get_students_count( $groups );

		return $groups;
	}

	public function apply_filters( $filters = array() ) {
		$query = $this->get_query();

		if ( Arr::get( $filters, 'school_id' ) ) {
			$meta_query = wpFluent()->table( 'postmeta' )
						   ->where( 'meta_key', '=', 'school' )
						   ->where( 'meta_value', '=', $filters['school_id'] )
						   ->select( 'post_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( Arr::get( $filters, 'class' ) ) {
			$meta_query = wpFluent()->table( 'postmeta' )
						   ->where( 'meta_key', '=', 'class' )
						   ->where( 'meta_value', '=', $filters['class'] )
						   ->select( 'post_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( Arr::get( $filters, 'section' ) ) {
			$meta_query = wpFluent()->table( 'postmeta' )
						   ->where( 'meta_key', '=', 'section' )
						   ->where( 'meta_value', '=', $filters['section'] )
						   ->select( 'post_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		$query->limit( Arr::get( $filters, 'length', 25 ) );
		$query->offset( Arr::get( $filters, 'start', 0 ) );

		return $query;
	}

	public function count( $filters = array() ) {
		return $this->apply_filters( $filters )->count();
	}

	public function get_query() {
		$query = wpFluent()->table( 'posts' );

		$query->where( 'post_type', '=', 'llms_group' );

		return $query;
	}

	public function get_meta( &$groups ) {
		$query = wpFluent()->table( 'postmeta' )->whereIn( 'post_id', Arr::pluck( $groups, 'ID' ) );

		$query->select( 'post_id', 'meta_key', 'meta_value' );

		$query->whereIn( 'meta_key', array( '_llms_post_id', 'school', 'class', 'section', 'llms_membership' ) );

		$meta   = $query->get();
		$groups = $this->merge_post_and_meta( $groups, $meta, 'post_id' );

		return $groups;
	}

	public function get_membership( &$groups ) {
		$memberships = get_posts(
			array(
				'post_type' => 'llms_membership',
				'include'    => Arr::pluck( $groups, '_llms_post_id' ),
			)
		);

		$groups = array_map(
			function ( $group ) use ( $memberships ) {
				$group['membership'] = Arr::first(
					$memberships,
					function( $school ) use ( $group ) {
						return intval( Arr::get( $group, '_llms_post_id' ) ) === $school->ID;
					}
				);
				return $group;
			},
			$groups
		);

		return $groups;
	}

	public function get_courses_count( &$groups ) {
		$courses = wpFluent()->table( 'postmeta' )
			->where( 'meta_key', '_llms_auto_enroll' )
			->whereIn( 'post_id', array_filter( Arr::pluck( $groups, '_llms_post_id' ) ) )
			->get();

		$groups = array_map(
			function ( $student ) use ( $courses ) {
				$user_count = Arr::first(
					$courses,
					function( $course ) use ( $student ) {
						return $course['post_id'] === $student['_llms_post_id'];
					}
				);

				$student['courses_count'] = $user_count ? count( unserialize( $user_count['meta_value'] ) ) : 0;

				return $student;
			},
			$groups
		);

		return $groups;
	}

	public function get_students_count( &$groups ) {
		$group_ids = Arr::pluck( $groups, 'ID' );

		$group_users_count = wpFluent()->table( 'lifterlms_user_postmeta' )
			->where( 'meta_key', '_group_role' )
			->whereIn( 'post_id', $group_ids )
			->select( wpFluent()->raw( 'count(user_id) as count, post_id' ) )
			->groupBy( 'post_id' )
			->get();

		$group_users_count = collect( $group_users_count )->pluck( 'count', 'post_id' );

		$groups = array_map(
			function ( $group ) use ( $group_users_count ) {
				$group['students_count'] = $group_users_count->get( $group['ID'], 0 );
				return $group;
			} ,
			$groups
		);

		return $groups;
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

