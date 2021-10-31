jQuery(function() {
	jQuery('#students-table').dataTable({
		"processing": true,
		"serverSide": true,
		"ajax": {
			"url": ajaxurl,
			data: function(d) {
				d.action = 'llms_school_get'
				d.school_id = jQuery('input#llms_school_id').val()
			},
		},
		columns: [
			{data: 'ID', title: 'User ID'},
			{data: 'manual_id', title: 'User Manual ID', render: data => data || ''},
			{data: 'first_name', title: 'First Name'},
			{data: 'last_name', title: 'Last Name'},
			{data: 'class', title: 'Class'},
			{data: 'section', title: 'Section'},
			{data: 'llms_membership', title: 'Groups'},
			{data: 'llms_membership', title: 'Membership'},
			{data: 'llms_enrollment', title: 'Enrollment', render: () => 0},
			{data: 'llms_completion', title: 'Completion', render: data => data || 'N/A'},
			{data: 'llms_overall_progress', title: 'Overall Progress'},
			{data: 'llms_overall_grade', title: 'Overall Grade'},
			{data: 'user_registered', title: 'Registered Date'},
			{data: 'llms_last_seen', title: 'Last seen', render: () => ''},
			{data: 'action', title: 'Action', render: function (d, i, row) {
				const url = new URL(location.href);

				url.searchParams.set('page', 'llms_course_export')
				url.searchParams.set('student_id', row.ID)
				url.searchParams.set('school_id', jQuery('input#llms_school_id').val());
				const course_url = url.href;
				url.searchParams.set('page', 'llms_quiz_export');
				const quiz_url = url.href;
				url.searchParams.set('page', 'llms_assignment_export');
				const assignment_url = url.href;

				const html = `<div class="dropdown">
  	  	  	  	  	  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">Reports</button>
  	  	  	  	  	  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
    					<li><a class="dropdown-item" href="${course_url}">Course</a></li>
    					<li><a class="dropdown-item" href="${quiz_url}">Quiz</a></li>
    					<li><a class="dropdown-item" href="${assignment_url}">Assignment</a></li>
  	  	  	  	  	  </ul>
					</div>`;
				return html;
				}
			},
		],
	});

	jQuery('#groups-table').dataTable({
		"processing": true,
		"serverSide": true,
		"ajax": {
			"url": ajaxurl,
			data: function(d) {
				d.action = 'llms_group_get'
				d.school_id = jQuery('input#llms_school_id').val()
			},
		},
		columns: [
			{data: 'ID', title: 'Group ID'},
			{data: 'post_title', title: 'Group Name', render: (d, i, row) => {
				let html = `<a href="${row.guid}" target="_blank">${d}</a>`;
				return html;
			}},
			{data: 'membership', title: 'Membership', render: (d, i, row) => {
				if (!d) {
					return 'N/A';
				}
				let html = `<a href="${d.guid}" target="_blank">${d.post_title}</a>`;
				return html;
			}},
			{data: 'courses_count', title: 'No of Courses'},
			{data: 'class', title: 'Class', render: data => data || ''},
			{data: 'section', title: 'Section', render: data => data || ''},
			{data: 'students_count', title: 'Students Count'},
			{data: 'action', title: 'Action', render: function (d, i, row) {
				const url = new URL(lifter_mt.admin_url);

				url.searchParams.set('page', 'llms_course_export')
				url.searchParams.set('group_id', row.ID)

				const html = `<div class="dropdown">
  <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">

  </button>
  <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
    <li><a class="dropdown-item" href="${url.href}">Course</a></li>
    <li><a class="dropdown-item" href="#">Another action</a></li>
    <li><a class="dropdown-item" href="#">Something else here</a></li>
  </ul>
</div>`;
				return html;
			}},
		],
	});

	jQuery(document).on('click', '#export_students', function () {
		const url = new URL(jQuery(this).attr('href'));

		url.searchParams.delete('post');
		url.searchParams.append('post',jQuery('input#llms_school_id').val() );
		url.searchParams.delete('page');
		url.searchParams.append('page', 'llms_students_export');
		url.searchParams.append('class', jQuery(this).parents('.row').find('select').eq(0).val());
		url.searchParams.append('section', jQuery(this).parents('.row').find('select').eq(1).val());

		window.location = url;
	});

	jQuery(document).on('click', '#export_groups', function () {
		const url = new URL(jQuery(this).attr('href'));

		url.searchParams.delete('post');
		url.searchParams.append('post',jQuery('input#llms_school_id').val() );
		url.searchParams.delete('page');
		url.searchParams.append('page', 'llms_groups_export');
		url.searchParams.append('class', jQuery(this).parents('.row').find('select').eq(0).val());
		url.searchParams.append('section', jQuery(this).parents('.row').find('select').eq(1).val());

		window.location = url;
	});
});
