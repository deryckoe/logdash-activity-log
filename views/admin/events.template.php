<div class="wrap">

	<div id="my-react-app"></div>

	<h1 style="display: flex; align-items: center; gap: 5px;">
		<?php _e( 'LogDash Activity', LOGDASH_DOMAIN ); ?>
	</h1>
	<div id="nds-wp-list-table-demo">
		<div id="nds-post-body">
			<form method="GET">

				<?php $user_list_table->search_box( __( 'Search' ), 's' ); ?>

				<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">
				<?php $user_list_table->display(); ?>
			</form>
		</div>
	</div>
</div>