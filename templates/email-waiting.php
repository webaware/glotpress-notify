<?php
// email template for waiting summary notification

if (!defined('ABSPATH')) {
	exit;
}
?>
<!DOCTYPE>
<html>
<head>
<title><?php echo esc_html($subject); ?></title>
<style>
body { sans-serif; color: #333; }
table { }
td, th { }
th {  }
.num { text-align: right; }
</style>
</head>

<body>
	<p><?php echo sprintf(esc_html__('Below are new translations awaiting approval over the past %s.', 'glotpress-notify'), $scope); ?></p>

	<table style="width:440px; text-align:center; border-collapse: collapse; border-spacing: 0;">
		<thead>
		<tr style="text-align: center;">
			<th style="border: 1px solid #ccc; padding: 2px;"><?php echo esc_html_x('Language', 'translation list heading', 'glotpress-notify'); ?></th>
			<th style="border: 1px solid #ccc; padding: 2px;"><?php echo esc_html_x('Locale', 'translation list heading', 'glotpress-notify'); ?></th>
			<th style="border: 1px solid #ccc; padding: 2px; text-align: center;" class="num"><?php echo esc_html_x('Current', 'translation list heading', 'glotpress-notify'); ?></th>
			<th style="border: 1px solid #ccc; padding: 2px; text-align: center;" class="num"><?php echo esc_html_x('Waiting', 'translation list heading', 'glotpress-notify'); ?></th>
		</tr>
		</thead>

		<tbody>
		<?php foreach ($translations as $translation) { ?>
		<tr>
			<td style="border: 1px solid #ccc; padding: 2px;"><?php
				if (empty($translation->translation_uri)) {
					echo esc_html($translation->locale_name);
				}
				else {
					printf('<a href="%s" target="_blank">%s</a>', esc_url($translation->translation_uri), esc_html($translation->locale_name));
				}
			?></td>
			<td style="border: 1px solid #ccc; padding: 2px;"><?php echo esc_html($translation->locale); ?></td>
			<td class="num" style="text-align:center; border: 1px solid #ccc; padding: 2px;"><?php echo esc_html($translation->current); ?></td>
			<td class="num" style="text-align:center; border: 1px solid #ccc; padding: 2px;"><?php echo esc_html($translation->waiting); ?></td>
		</tr>
		<?php } ?>
		</tbody>
		<?php if( count($translations) > 10 ) : ?>
		<tfoot>
		<tr>
			<th style="border: 1px solid #ccc; padding: 2px;"><?php echo esc_html_x('Language', 'translation list heading', 'glotpress-notify'); ?></th>
			<th style="border: 1px solid #ccc; padding: 2px;"><?php echo esc_html_x('Locale', 'translation list heading', 'glotpress-notify'); ?></th>
			<th style="border: 1px solid #ccc; padding: 2px; text-align: center;" class="num"><?php echo esc_html_x('Current', 'translation list heading', 'glotpress-notify'); ?></th>
			<th style="border: 1px solid #ccc; padding: 2px; text-align: center;" class="num"><?php echo esc_html_x('Waiting', 'translation list heading', 'glotpress-notify'); ?></th>
		</tr>
		</tfoot>
		<?php endif; ?>

	</table> 
	
	<p><?php echo sprintf(esc_html__('You have received this email because you have subscribed to receive notifcations of new translations on %s.', 'glotpress-notify'), get_bloginfo('url')); ?></p>
	
	<p><?php echo sprintf(esc_html__('To change your notification settings, please visit your account here - %s', 'glotpress-notify'), admin_url('admin.php?page=gpnotify-profile')); ?></p>
</body>

</html>
