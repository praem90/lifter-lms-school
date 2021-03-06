<div class="container-fluid mt-2">
	<div class="row d-inline-flex">
		<div class="col">
<?php acf_form(
	array(
		'id'     => 'student-search',
		'fields' => array( 'class' ),
		'form'   => false,
	)
); ?>
		</div>
		<div class="col">
<?php
acf_form(
	array(
		'id'     => 'student-section-search',
		'fields' => array( 'section' ),
		'form'   => false,
	)
);
?>
		</div>
		<div class="col align-self-end">
		<button href="
		<?php
		echo $this->get_school_details_url(
			$school,
			array(
				'school_id' => $school->ID,
				'tab'       => 'llms_students_export',
			)
		);
		?>
		" class="btn btn-primary" id="export_students">Export</button>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col">
			<table id="students-table" class="table"></table>
		</div>
	</div>
</div>
