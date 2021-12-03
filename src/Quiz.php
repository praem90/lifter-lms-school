<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class Quiz {
	public static function download() {
		$self    = new self();
		$filters = $_REQUEST;

		$filters['start']  = 0;
		$filters['length'] = 100;
		$data              = $self->get( $filters );

		if ( count( $data ) === 0 ) {
			wp_die( 'No Quiz to export' );
		}

		$fileName = 'llms_quiz.csv';

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
				fputcsv( $df, $row );
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

		$student_courses = array_map( [ $this, 'format_item' ], $student_courses );

		return $student_courses;
	}

	public function apply_filters( $filters = array() ) {
		$query = $this->query();

		if ( Arr::get( $filters, 'school_id' ) ) {
			$school_query = wpFluent()->table( 'usermeta' )
						->where( 'meta_key', '=', 'school' )
						->where( 'meta_value', '=', Arr::get( $filters, 'school_id' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'student_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );

			$this->school = get_post( $filters['school_id'] );
		}

		if ( Arr::get( $filters, 'student_id' ) ) {
			$query->where( 'student_id', $filters['student_id'] );
		}

		if ( Arr::get( $filters, 'class' ) ) {
			$school_query = wpFluent()->table( 'usermeta' )
						->where( 'meta_key', '=', 'class' )
						->where( 'meta_value', '=', Arr::get( $filters, 'class' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'student_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( Arr::get( $filters, 'section' ) ) {
			$school_query = wpFluent()->table( 'usermeta' )
						->where( 'meta_key', '=', 'section' )
						->where( 'meta_value', '=', Arr::get( $filters, 'section' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'student_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );
		}

		if ( Arr::get( $filters, 'group_id' ) ) {
			$school_query = wpFluent()->table( 'lifterlms_user_postmeta' )
						->where( 'post_id', '=', Arr::get( $filters, 'group_id' ) )
						->select( 'user_id' );

			$query->where( wpFluent()->raw( 'student_id in (' . $school_query->getQuery()->getRawSql() . ')' ) );
		}

		return $query;
	}

	public function query() {
		$query = wpFluent()->table( 'lifterlms_quiz_attempts' )
					 ->join( 'posts', 'posts.ID', '=', 'quiz_id' )
					 ->select( 'lifterlms_quiz_attempts.*', 'posts.ID', 'posts.post_title' );

		return $query;
	}

	public function format_item( $quiz_attempts ) {
		$student = llms_get_student( $quiz_attempts['student_id'] );

		$lesson = get_post( $quiz_attempts['lesson_id'] );
		$course = get_post( get_post_meta( $lesson->ID, '_llms_parent_course', true ) );

		$user_post_meta = wpFluent()->table( 'lifterlms_user_postmeta' )->select( 'post_id' )->where( 'user_id', $quiz_attempts['student_id'] )->get();
		$user_post_meta = Arr::pluck( $user_post_meta, 'post_id' );

		$membership = wpFluent()->table( 'posts' )->join( 'postmeta', 'post_id', '=', 'posts.ID' )
					->where( 'postmeta.meta_key', '=', '_llms_auto_enroll' )
					->where( 'posts.post_type', '=', 'llms_membership' )
					->where( 'postmeta.meta_value', 'like', '%i:' . $course->ID . ';%' )
					->whereIn( 'posts.ID', $user_post_meta )
					->select( 'posts.ID', 'posts.post_title' )
					->first();

		if ( $membership ) {

			$group = wpFluent()->table( 'posts' )->join( 'postmeta', 'post_id', '=', 'posts.ID' )
					->where( 'posts.post_type', '=', 'llms_group' )
					->where( 'postmeta.meta_key', '=', '_llms_post_id' )
					->where( 'postmeta.meta_value', '=', Arr::get( $membership, 'ID' ) )
					->select( 'posts.ID', 'posts.post_title' )
					->first();
		} else {
			$membership = array();
		}

		if ( ! $group ) {
			$group = array();
		}

		$quiz_item['student_id']    = $quiz_attempts['student_id'];
		$quiz_item['first_name']    = $student->first_name;
		$quiz_item['last_name']     = $student->last_name;
		$quiz_item['student_email'] = $student->user_email;

		$quiz_item['school_system_id'] = $this->school->ID;
		$quiz_item['school_manual_id'] = get_post_meta( $this->school, 'school_id_manual', true );
		$quiz_item['school_name']      = $this->school->post_title;
		$quiz_item['class']            = get_user_meta( $quiz_attempts['student_id'], 'class' , true );
		$quiz_item['section']          = get_user_meta( $quiz_attempts['student_id'], 'section' , true );

		$quiz_item['group_id']   = Arr::get( $group, 'ID', 'N/A' );
		$quiz_item['group_name'] = Arr::get( $group, 'post_title', 'N/A' );

		$quiz_item['membership_id']   = Arr::get( $membership, 'ID', 'N/A' );
		$quiz_item['membership_name'] = Arr::get( $membership, 'post_title', 'N/A' );

		$quiz_item['course_id']   = $course->ID;
		$quiz_item['course_name'] = $course->post_title;

		$quiz_item['lesson_id']   = $lesson->ID;
		$quiz_item['lesson_name'] = $lesson->post_title;
		$quiz_item['quiz_id']     = $quiz_attempts['ID'];
		$quiz_item['quiz_name']   = $quiz_attempts['post_title'];
		$quiz_item['attempts']    = $quiz_attempts['attempt'];
		$quiz_item['grade']       = $quiz_attempts['grade'];
		$quiz_item['status']      = $quiz_attempts['status'];
		$quiz_item['start_date']  = $quiz_attempts['start_date'];
		$quiz_item['end_date']    = $quiz_attempts['end_date'];

		return $quiz_item;
	}

}


