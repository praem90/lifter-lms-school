<table class="table">
	<tr>
		<th><?php echo esc_attr__( 'School ID', 'llms-school' ) . ' (' . esc_attr__( 'System', 'llms-school' ) . ')' ; ?></th>
		<td><?php echo esc_html( $school->ID ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'School ID', 'llms-school' ) . ' (' . esc_attr__( 'Manual', 'llms-school' ) . ')' ; ?></th>
		<td><?php echo esc_html( get_post_meta( $school->ID, 'school_id_manual', $school->ID ) ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'School Name', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( $school->post_title, 'llms-school' ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Contact Name', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( get_post_meta( $school->ID, 'contact_name', true ) ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Contact Email', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( get_post_meta( $school->ID, 'contact_email', true ) ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Contact Mobile', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( get_post_meta( $school->ID, 'contact_mobile', true ) ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Address', 'llms-school' ); ?></th>
		<td>
			<?php echo esc_html__( get_post_meta( $school->ID, 'line_1', true ) ); ?><br>
			<?php echo esc_html__( get_post_meta( $school->ID, 'line_2', true ) ); ?><br>
			<?php echo esc_html__( 'City', 'llms-school' ) . ': ' . esc_attr( get_post_meta( $school->ID, 'city', true ) ); ?>  <br>
			<?php echo esc_html__( 'State', 'llms-school' ) . ': ' . esc_attr( get_post_meta( $school->ID, 'state', true ) ); ?>  <br>
			<?php echo esc_html__( 'Pincode', 'llms-school' ) . ': ' . esc_attr( get_post_meta( $school->ID, 'pincode', true ) ); ?>  <br>
		</td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Status', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( get_post_meta( $school->ID, 'status', true ), 'llms-school' ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Status Updated Date', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( get_post_datetime( $school->ID, 'status_update_date', 'local' )->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ), 'llms-school' ); ?></td>
	</tr>
	<tr>
		<th><?php echo esc_attr__( 'Remarks', 'llms-school' ); ?></th>
		<td><?php echo esc_html__( get_post_meta( $school->ID, 'remarks', true ), 'llms-school' ); ?></td>
	</tr>
</table>
