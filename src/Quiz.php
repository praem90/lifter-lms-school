<?php
namespace Lifter\MT;

use Illuminate\Support\Arr;

class Quiz {
	public static function download() {
		$self    = new self();
		$filters = $_REQUEST;

		$filters['start']  = 0;
		$filters['length'] = 100;

		$fileName = 'llms_quiz.csv';

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( "Content-Disposition: attachment; filename={$fileName}" );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		$df   = fopen( 'php://output', 'w' );
		$data = $self->get( $filters );
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

		$lesson = get_post( get_post_meta( $quiz_attempts['quiz_id'], '_llms_lesson_id', true ) );

		$quiz_item['student_id']    = $quiz_attempts['student_id'];
		$quiz_item['student_name']  = $student->display_name;
		$quiz_item['student_email'] = $student->user_email;
		$quiz_item['lesson_id']     = $lesson->ID;
		$quiz_item['lesson_name']   = $lesson->post_title;
		$quiz_item['quiz_id']       = $quiz_attempts['ID'];
		$quiz_item['quiz_name']     = $quiz_attempts['post_title'];
		$quiz_item['attempts']      = $quiz_attempts['attempt'];
		$quiz_item['grade']         = $quiz_attempts['grade'];
		$quiz_item['status']        = $quiz_attempts['status'];
		$quiz_item['start_data']    = $quiz_attempts['start_data'];
		$quiz_item['end_date']      = $quiz_attempts['end_date'];

		return $quiz_item;
	}

}


