<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'logdash' );
		// output setting sections and their fields
		// (sections are registered for "wporg", each field is registered to a specific section)
		do_settings_sections( 'logdash' );
		// output save settings button
		submit_button( 'Save Settings' );
		?>
	</form>
</div>