<div class="container-fluid mt-2">
	<div class="row align-items-center">
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
		<div class="col">
		<button href="
		<?php
		echo $this->get_school_details_url(
			array(
				'post' => $school->ID,
			)
		);
		?>
		" class="btn btn-primary" id="export_students">Export</button>
		</div>
	</div>
	<div class="row">
		<div class="col">
			<table id="students-table" class="table"></table>
		</div>
	</div>
</div>
