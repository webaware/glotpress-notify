<?php
// email template for waiting summary notification

if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap">

	<h2><?php esc_html_e('GlotPress Notify Projects', 'glotpress-notify'); ?></h2>
	
	<?php 
	$scope_links = array();
	$scopes = array('all' => __('View All', 'glotpress-notify'), 'day' => __('Today','glotpress-notify'), 'week' => __('This Week', 'glotpress-notify'), 'month' => __('This Month', 'glotpress-notify'));
	foreach( $scopes as $s => $v){
		$class_active = ( $scope == $s ) ? ' style="font-weight:bold"' : '';
		$scope_links[] = '<a href="'.esc_url(add_query_arg('scope', $s)) ."\"$class_active>".esc_html($v).'</a>';
	}
	?>
	<p><?php echo implode(' | ', $scope_links); ?></p>

	<?php if (count($waiting) === 0): ?>

		<p><?php esc_html_e('There are no GlotPress projects with translations waiting for approval.', 'glotpress-notify'); ?></p>

	<?php else: ?>

		<?php foreach ($waiting as $project_id => $translations) { ?>

		<h3><a href="<?php echo esc_url(gp_url_project( $projects[$project_id]->slug )); ?>"><?php echo esc_html($projects[$project_id]->name); ?></a></h3>

		<table class="wp-list-table widefat fixed">
			<thead>
			<tr>
				<th><?php echo esc_html_x('Language', 'translation list heading', 'glotpress-notify'); ?></th>
				<th><?php echo esc_html_x('Locale', 'translation list heading', 'glotpress-notify'); ?></th>
				<th class="num"><?php echo esc_html_x('Current', 'translation list heading', 'glotpress-notify'); ?></th>
				<th class="num"><?php echo esc_html_x('Waiting', 'translation list heading', 'glotpress-notify'); ?></th>
			</tr>
			</thead>

			<tbody>
			<?php $i = 0; foreach ($translations as $translation) {
				$tr_class = ($i % 2) ? '' : 'class="alternate"';
				?>
				<tr <?php echo $tr_class; ?>>
					<td><a href="<?php echo esc_url(gp_url_project_locale( $projects[$project_id]->slug, $translation->locale, $translation->slug )); ?>"><?php echo esc_html($translation->locale_name); ?></td>
					<td><?php echo esc_html($translation->locale); ?></td>
					<td class="num"><?php echo esc_html($translation->current); ?></td>
					<td class="num"><?php echo esc_html($translation->waiting); ?></td>
				</tr>
			<?php $i++; } ?>
			</tbody>

		</table>

		<?php } ?>

	<?php endif; ?>

</div>

