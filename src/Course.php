<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class Course {
	public static function download() {
		$self    = new self();
		$filters = $_REQUEST;

		$filters['start']  = 0;
		$filters['length'] = 100;
		$data              = $self->get( $filters );

		if ( count( $data ) === 0 ) {
			wp_die( 'No Courses to export' );
		}

		$fileName = 'llms_course.csv';

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( "Content-Disposition: attachment; filename={$fileName}" );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		$df = fopen( 'php://output', 'w' );
		fputcsv( $df, array_keys( reset( $data ) ) );
		while ( count( $data ) ) {
			foreach ( $data as $row ) {
				fputcsv(
					$df,
					$row
				);
			}

			$filters['start'] += $filters['length'];
			$data              = $self->get( $filters );
		}

		fclose( $df );
		exit;
	}

	public function get( $filters = array() ) {
		$query = $this->apply_filters( $filters );

		$query->limit( Arr::get( $filters, 'length', 25 ) );
		$query->offset( Arr::get( $filters, 'start', 0 ) );

		$student_courses = $query->get();

			$student_courses = $this->get_memberships( $student_courses );
		$student_courses     = array_map( [ $this, 'map_course_status' ], $student_courses );

		return $student_courses;
	}

	public function apply_filters( $filters = array() ) {
		$query = $this->query();

		if ( Arr::get( $filters, 'school_id' ) ) {
			$school_query = wpFluent()->table( 'usermeta' )
						->where( 'meta_key', '=', 'school' )
						->where( 'meta_value', '=', Arr::get( $filters, 'school_id' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'user_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );

			$this->school = get_post( $filters['school_id'] );
		}

		if ( Arr::get( $filters, 'student_id' ) ) {
			$query->where( 'user_id', $filters['student_id'] );
		}

		if ( Arr::get( $filters, 'class' ) ) {
			$school_query = wpFluent()->table( 'usermeta' )
						->where( 'meta_key', '=', 'class' )
						->where( 'meta_value', '=', Arr::get( $filters, 'class' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'user_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( Arr::get( $filters, 'section' ) ) {
			$school_query = wpFluent()->table( 'usermeta' )
						->where( 'meta_key', '=', 'section' )
						->where( 'meta_value', '=', Arr::get( $filters, 'section' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'user_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( Arr::get( $filters, 'group_id' ) ) {
			$school_query = wpFluent()->table( 'lifterlms_user_postmeta' )
						->where( 'post_id', '=', Arr::get( $filters, 'group_id' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'user_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );
		}

		return $query;
	}

	public function query() {
		$query = wpFluent()->table( 'lifterlms_user_postmeta' )
			->join( 'posts', 'posts.ID', '=', 'lifterlms_user_postmeta.post_id' )
			->where( 'meta_key', '_status' )
			->where( 'posts.post_type', 'course' )
			->select( 'user_id', 'post_id', 'posts.ID', 'posts.post_title' );

		return $query;
	}

	public function get_memberships( $courses ) {
		$memberships = wpFluent()->table( 'posts' )
						  ->join( 'postmeta', 'postmeta.post_id', '=', 'posts.ID' )
						  ->where( 'posts.post_type', 'llms_membership' )
						  ->where( 'postmeta.meta_key', '_llms_auto_enroll' )
						  ->select( 'posts.ID', 'posts.post_title', 'postmeta.meta_value' );

		$memberships->where(
			function ( $q ) use ( $courses ) {
				foreach ( $courses as $course ) {
					$q->orWhere( 'meta_value', 'like', '%i:' . $course['ID'] . ';%' );
				}
			}
		);

		$memberships = collect( $memberships->get() )->map(
			function ( $membership ) {
				$membership['meta_value'] = unserialize( $membership['meta_value'] );
				return $membership;
			}
		);

		$groups = wpFluent()->table( 'posts' )
						  ->join( 'postmeta', 'postmeta.post_id', '=', 'posts.ID' )
							  ->where( 'meta_key', '_llms_post_id' )
							  ->whereIn( 'meta_value', $memberships->pluck( 'ID' )->toArray() )
						  ->select( 'posts.ID', 'posts.post_title', 'postmeta.meta_value' );

		$groups = collect( $groups->get() )->keyBy( 'meta_value' );


		$courses = array_map ( function ( $course ) use ($memberships, $groups){

			$course['membership'] = $memberships->first(
				function ( $membership ) use ( $course ) {
					return in_array( $course['ID'], $membership['meta_value'] );
				}
			);

			$course['group'] = [];

			if ( $course['membership'] ) {
				$course['group'] = $groups->get( $course['membership']['ID'] );
			}

			return $course;

		}, $courses);

		return $courses;
	}

	public function map_course_status( $student_course ) {
		$student = llms_get_student( $student_course['user_id'] );

		// $course = get_post( $student_course['post_id'] );

		$student_course['student_id']        = $student_course['user_id'];
		$student_course['student_manual_id'] = get_user_meta( $student_course['user_id'], 'manual_id' , true );
		$student_course['first_name']        = $student->first_name;
		$student_course['last_name']         = $student->last_name;
		$student_course['student_email']     = $student->user_email;

		$student_course['school_system_id'] = $this->school->ID;
		$student_course['school_manual_id'] = get_post_meta( $this->school, 'school_manual_id', true );
		$student_course['school_name']      = $this->school->post_title;
		$student_course['class']            = get_user_meta( $student_course['user_id'], 'class' , true );
		$student_course['section']          = get_user_meta( $student_course['user_id'], 'section' , true );


		$student_course['group_id']        = Arr::get( $student_course, 'group.ID', '' );
		$student_course['group_name']      = Arr::get( $student_course, 'group.post_title', '' );
		$student_course['membership_id']   = Arr::get( $student_course, 'membership.ID', '' );
		$student_course['membership_name'] = Arr::get( $student_course, 'membership.post_title', '' );

		$student_course['course_id']                 = $student_course['ID'];
		$student_course['course_name']               = $student_course['post_title'];
		$student_course['course_status']             = llms_get_enrollment_status_name( $student->get_enrollment_status( $student_course['ID'] ) );
		$student_course['course_enrollment_updated'] = $student->get_enrollment_date( $student_course['ID'], 'updated' );
		$student_course['course_completed']          = $student->get_completion_date( $student_course['ID'] );
		$student_course['course_progress']           = $student->get_progress( $student_course['ID'] );
		$student_course['course_grade']              = $student->get_grade( $student_course['ID'] );

		return $student_course;
	}

}


