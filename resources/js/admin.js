jQuery(function() {
	jQuery('#students-table').dataTable({
		"processing": true,
		"serverSide": true,
		"ajax": {
			"url": ajaxurl,
		},
		columns: [],
	});
});
