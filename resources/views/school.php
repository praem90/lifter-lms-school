<?php
$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'details';
?>
<!-- Our admin page content should all be inside .wrap -->
  <div class=" llms-school-wrapper container-fluid">
		<div class="row align-items-center">
		<div class="col">
		<h1><?php echo esc_html( $school->post_title ); ?></h1>
		</div>
		<div class="col">
		<div class="dropdown pull-right">
  <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButtonSchool" data-bs-toggle="dropdown" aria-expanded="false">
Reports
  </button>
  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonSchool">
		<li><a class="dropdown-item" href="<?php echo esc_url( $this->get_school_details_url( $school, array( 'tab' => 'llms_course_export' ) ) ); ?>">Course</a></li>
		<li><a class="dropdown-item" href="<?php echo esc_url( $this->get_school_details_url( $school, array( 'tab' => 'llms_quiz_export' ) ) ); ?>">Quiz</a></li>
		<li><a class="dropdown-item" href="<?php echo esc_url( $this->get_school_details_url( $school, array( 'tab' => 'llms_assignment_export' ) ) ); ?>">Assignment</a></li>
  </ul>
</div>
</div>
	</div>
	<input id="llms_school_id" type="hidden" value="<?php echo $school->ID; ?>"/>
	<!-- Print the page title -->
	<!-- Here are our tabs -->
	<ul class="nav nav-tabs">
		<li class="nav-item">
		<a href="<?php echo esc_url( $this->get_school_details_url( $school ) ); ?>" class="nav-link
							<?php
							if ( $tab === 'details' ) :
								?>
 active<?php endif; ?>">
			<?php echo esc_html__( 'School', 'llms-school' ); ?>
		</a>
</li>
		<li class="nav-item">
	  <a href="<?php echo esc_url( $this->get_school_details_url( $school, array( 'tab' => 'students' ) ) ); ?>" class="nav-link
						  <?php
							if ( $tab === 'students' ) :
								?>
active<?php endif; ?>">
			<?php echo esc_html__( 'Students', 'llms-school' ); ?>
	  </a>
</li>
		<li class="nav-item">
	  <a href="<?php echo esc_url( $this->get_school_details_url( $school, array( 'tab' => 'groups' ) ) ); ?>" class="nav-link
						  <?php
							if ( $tab === 'groups' ) :
								?>
 active<?php endif; ?>">
			<?php echo esc_html__( 'Groups', 'llms-school' ); ?>
	  </a>
</li>
	</ul>

	<div class="tab-content">
	<?php require __DIR__ . '/school-' . $tab . '.php'; ?>
	</div>
  </div>

