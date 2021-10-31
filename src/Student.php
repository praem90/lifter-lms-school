<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class Student {
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


		ob_start();
		$df = fopen( 'php://output', 'w' );
		// fputcsv( $df, array_keys( reset( $array ) ) );
		$data = $self->get( $filters );
		while ( count( $data ) ) {
			foreach ( $array as $row ) {
				fputcsv( $df, $row );
			}

			$filters['start'] += $filters['length'];
			$data              = $self->get( $filters );
		}

		fclose( $df );
		return ob_get_clean();
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

		if ( isset( $filters['school_id'] ) ) {
			$meta_query = wpFluent()->table( 'usermeta' )
						   ->where( 'meta_key', '=', 'school' )
						   ->where( 'meta_value', '=', $filters['school_id'] )
							->select( 'user_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( isset( $filters['class'] ) ) {
			$meta_query = wpFluent()->table( 'usermeta' )->where( 'class', '=', $filters['class'] )->select( 'user_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( isset( $filters['section'] ) ) {
			$meta_query = wpFluent()->table( 'usermeta' )->where( 'section', '=', $filters['section'] )->select( 'user_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( isset( $filters['group_id'] ) ) {
			$meta_query = wpFluent()->table( 'lifterlms_user_postmeta' )->where( 'post_id', '=', $filters['group_id'] )->select( 'user_id' );
			$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( isset( $filters['search']['value'] ) ) {
			$query->where( 'display_name', 'like', '%' . $filters['search']['value'] . '%' );
		}

		$query->limit( Arr::get( $filters, 'length', 25 ) );
		$query->offset( Arr::get( $filters, 'start', 0 ) );

		return $query;
	}

	public function count( $filters = array() ) {
		return $this->apply_filters( $filters )->count();
	}

	public function get_query() {
		$query = wpFluent()->table( 'users' );

		$meta_query = wpFluent()->table( 'usermeta' )->where( 'meta_key', '=', 'wp_capabilities' )->where( 'meta_value', 'like', '%"student"%' )->select( 'user_id' );

		$query->where( wpFluent()->raw( 'ID in (' . $meta_query->getQuery()->getRawSql() . ')' ) );

		return $query;
	}

	public function get_meta( &$students ) {
		$query = wpFluent()->table( 'usermeta' )->whereIn( 'user_id', Arr::pluck( $students, 'ID' ) );

		$query->select( 'user_id', 'meta_key', 'meta_value' );

		$query->whereIn( 'meta_key', array( 'first_name', 'school', 'class', 'section', 'last_name', 'llms_overall_grade', 'llms_overall_progress', 'llms_last_login' ) );

		$meta = $query->get();

		$students = $this->merge_post_and_meta( $students, $meta, 'user_id' );

		return $students;
	}

	public function get_school( &$students ) {
		$schools = get_posts(
			array(
				'post_type' => 'llms_school',
				'id__in'    => Arr::pluck( $students, 'school' ),
			)
		);

		$students = array_map(
			function ( $student ) use ( $schools ) {
				$student['school'] = Arr::first(
					$schools,
					function( $school ) use ( $student ) {
						return $school->ID === (int) $student['school'];
					},
					array()
				);
				return $student;
			},
			$students
		);

		return $students;
	}

	public function get_course_group_membership_count( &$students ) {
		$query = wpFluent()->table( 'lifterlms_user_postmeta' )->join( 'posts', 'ID', '=', 'post_id' );

		$post_types = array( 'course', 'llms_membership', 'llms_group' );

		$query->whereIn( 'post_type', $post_types );
		$query->whereIn( 'user_id', Arr::pluck( $students, 'ID' ) );
		$query->where( 'meta_key', '=', '_status' );

		$query->select( wpFluent()->raw( 'user_id, post_type, count(post_id) as count' ) );
		$query->groupBy( 'user_id' );
		$query->groupBy( 'post_type' );

		$counts = $query->get();

		$students = array_map(
			function ( $student ) use ( $counts, $post_types ) {
				$user_count = array_filter(
					$counts,
					function( $count ) use ( $student ) {
						return $count['user_id'] === $student['ID'];
					}
				);

				$user_count = Arr::pluck( $user_count, 'count', 'post_type' );

				foreach ( $post_types as $post_type ) {
					$student[ $post_type ] = Arr::get( $user_count, $post_type, 0 );
				}

				return $student;
			} ,
			$students
		);

		return $students;
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

