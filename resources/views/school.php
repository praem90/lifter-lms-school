<?php
$tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'details';
$post = isset( $_GET['post'] ) ? sanitize_text_field( $_GET['post'] ) : null;
$post = get_post( $post );
?>
<!-- Our admin page content should all be inside .wrap -->
  <div class="wrap">
	<!-- Print the page title -->
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<!-- Here are our tabs -->
	<nav class="nav-tab-wrapper">
		<a href="<?php echo esc_url( $this->get_school_details_url( $post ) ); ?>" class="nav-tab
							<?php
							if ( $tab === 'details' ) :
								?>
 nav-tab-active<?php endif; ?>">
			<?php echo esc_html__( 'School', 'llms-school' ); ?>
		</a>
	  <a href="<?php echo esc_url( $this->get_school_details_url( $post, array( 'tab' => 'students' ) ) ); ?>" class="nav-tab
						  <?php
							if ( $tab === 'students' ) :
								?>
 nav-tab-active<?php endif; ?>">
			<?php echo esc_html__( 'Students', 'llms-school' ); ?>
	  </a>
	  <a href="<?php echo esc_url( $this->get_school_details_url( $post, array( 'tab' => 'groups' ) ) ); ?>" class="nav-tab
						  <?php
							if ( $tab === 'group' ) :
								?>
 nav-tab-active<?php endif; ?>">
			<?php echo esc_html__( 'Groups', 'llms-school' ); ?>
	  </a>
	</nav>

	<div class="tab-content">
	<?php require __DIR__ . '/school-' . $tab . '.php'; ?>
	</div>
  </div>

