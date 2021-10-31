<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class Student {

	public function download() {
	}

	public function get( $filters = array() ) {
		$query    = $this->apply_filters( $filters );
		$students = $query->get();

		$this->get_meta( $students );
		$this->get_school( $students );
		$this->get_course_group_membership_count( $students );

		return $students;
	}

	public function apply_filters( $filters = array() ) {
		$query = $this->get_query();

		if ( isset( $filters['school_id'] ) ) {
			$meta_query = wpFluent()->table( 'usermeta' )->where( 'school', '=', $filters['school_id'] )->select( 'user_id' );
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

		$per_page     = Arr::get( $filters, 'per_page', 25 );
		$current_page = Arr::get( $filters, 'page_no', 1 );

		$query->limit( $per_page );
		$query->offset( ( $current_page - 1 ) * $per_page );

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

