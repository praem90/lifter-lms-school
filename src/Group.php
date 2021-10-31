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

	public function download() {
	}

	public function get( $filters = array() ) {
		$query  = $this->apply_filters( $filters );
		$groups = $query->get();

		if ( count( $groups ) === 0 ) {
			return $groups;
		}
		$this->get_meta( $groups );

		$this->get_membership( $groups );
		$this->get_courses_count( $groups );
		$this->get_students_count( $groups );

		return $groups;
	}

	public function apply_filters( $filters = array() ) {
		$query = $this->get_query();

		if ( isset( $filters['school_id'] ) ) {
			$meta_query = wpFluent()->table( 'postmeta' )
						   ->where( 'meta_key', '=', 'school' )
						   ->where( 'meta_value', '=', $filters['school_id'] )
						   ->select( 'post_id' );
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
				'id__in'    => Arr::pluck( $groups, '_llms_post_id' ),
			)
		);

		$groups = array_map(
			function ( $group ) use ( $memberships ) {
				$group['membership'] = Arr::first(
					$memberships,
					function( $school ) use ( $group ) {
						return $school->ID === (int) Arr::get( $group, '_llms_post_id' );
					},
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
						return $course['post_id'] === $student['ID'];
					}
				);

				$student['courses_count'] = $user_count ? count( unserialize( $user_count['_llms_post_id'] ) ) : 0;

				return $student;
			} ,
			$groups
		);

		return $groups;
	}

	public function get_students_count( &$groups ) {
		$membership_id = array_filter( Arr::pluck( $groups, 'membership.ID' ) );

		$courses = wpFluent()->table( 'lifterlms_user_postmeta' )
			->where( 'meta_key', '_status' )
			->where( 'meta_value', 'enrolled' )
			->whereIn( 'post_id', $membership_id )
			->select( wpFluent()->raw( 'count(user_id) as count, post_id' ) )
			->groupBy( 'post_id' )
			->get();

		$groups = array_map(
			function ( $student ) use ( $courses ) {
				$user_count = Arr::first(
					$courses,
					function( $course ) use ( $student ) {
						$membership = Arr::get( $student, 'membership' );
						return $membership && $membership->ID === (int) $course['post_id'] ;
					}
				);

				$student['students_count'] = $user_count ? $user_count['count'] : 0;

				return $student;
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

