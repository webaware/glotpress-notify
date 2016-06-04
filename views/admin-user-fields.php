<?php
// user profile fields for GlotPress notifications

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap">

	<h2><?php esc_html_e('GlotPress Notify Subscriptions', 'glotpress-notify'); ?></h2>
	<p><?php esc_html_e('Receive notifications for GlotPress translation projects.', 'glotpress-notify'); ?></p>

	<?php if ($update_message): ?>
	<div class="updated">
		<p><?php echo $update_message; ?></p>
	</div>
	<?php endif; ?>

	<form action="<?php echo $form_action; ?>" method="post">

		<table class="wp-list-table widefat fixed">

			<thead>
				<tr>
					<th><?php echo esc_html_x('Project name', 'subscription settings', 'glotpress-notify'); ?></th>
					<th><?php echo esc_html_x('Project slug', 'subscription settings', 'glotpress-notify'); ?></th>
					<th><?php echo esc_html_x('Language', 'subscription settings', 'glotpress-notify'); ?></th>
					<th><?php echo esc_html_x('Waiting', 'subscription settings', 'glotpress-notify'); ?></th>
				</tr>
			</thead>

			<tfoot>
				<tr>
					<th><?php echo esc_html_x('Project name', 'subscription settings', 'glotpress-notify'); ?></th>
					<th><?php echo esc_html_x('Project slug', 'subscription settings', 'glotpress-notify'); ?></th>
					<th><?php echo esc_html_x('Language', 'subscription settings', 'glotpress-notify'); ?></th>
					<th><?php echo esc_html_x('Waiting', 'subscription settings', 'glotpress-notify'); ?></th>
				</tr>
			</tfoot>

			<?php $i = 0; foreach ($projects as $project) {
				$tr_class = ($i % 2) ? '' : 'class="alternate"';

				$fieldbase = "gpnotify_projects_{$project->id}";
				$waiting = empty($project_options['waiting'][$project->id]) || is_array($project_options['waiting'][$project->id]) ? 0 : 1;
				?>

				<tr <?php echo $tr_class; ?>>
					<td><?php
						if (empty($project->project_uri)) {
							echo esc_html($project->name);
						}
						else {
							printf('<a href="%s" target="_blank">%s</a>', esc_url($project->project_uri), esc_html($project->name));
						}
					?></td>
					<td><?php echo esc_html($project->slug); ?></td>
					<td></td>
					<td><input type="checkbox" class="project-notify" id="project-<?php echo esc_attr($project->slug); ?>" name="<?php echo $fieldbase; ?>_waiting" <?php checked($waiting); ?> value="1" /></td>
				</tr>
				<tbody class="project-<?php echo esc_attr($project->slug); ?>-langs">
				<?php 
					foreach( $translation_sets[$project->id] as $translation_set ){
						$set_waiting = $waiting || !empty($project_options['waiting'][$project->id][$translation_set->locale]) ? 1 : 0;
						$set_fieldbase = "gpnotify_sets_{$project->id}[{$translation_set->locale}]";
						?>
						<tr <?php echo $tr_class; ?>>
							<td></td>
							<td></td>
							<td><?php echo esc_html($translation_set->name); ?></td>
							<td><input type="checkbox" name="<?php echo $set_fieldbase; ?>" <?php checked($set_waiting); ?> value="1" /></td>
						</tr>
						<?php
						
					}
					?>
				</tbody>
			<?php $i++; } ?>
		</table>
		<table>
			<tr>
				<p><?php esc_html_e('Notify me', 'glotpress-notify'); ?>&nbsp;&nbsp;
					<label>
						<input type="checkbox" name="frequency[]" value="day" <?php echo (in_array('day', $project_options['frequency'])) ? 'checked="checked"':'' ?> />
						<?php esc_html_e('Daily', 'glotpress-notify'); ?>
					</label> 
					<label>
						<input type="checkbox" name="frequency[]" value="week" <?php echo (in_array('week', $project_options['frequency'])) ? 'checked="checked"':'' ?> />
						<?php esc_html_e('Weekly', 'glotpress-notify'); ?>
					</label> 
					<label>
						<input type="checkbox" name="frequency[]" value="month" <?php echo (in_array('month', $project_options['frequency'])) ? 'checked="checked"':'' ?> />
						<?php esc_html_e('Monthly', 'glotpress-notify'); ?>
					</label></p>
		<p class="submit">
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Subscriptions', 'glotpress-notify'); ?>" />
			<?php wp_nonce_field('subscribe', 'gpnotify_nonce'); ?>
		</p>

	</form>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('input.project-notify:checkbox').click(function(){
				var box = $(this);
				$('tbody.'+ box.attr('id') +'-langs input:checkbox').prop('checked', box.prop("checked"));
			});
		});
	</script>
</div>

