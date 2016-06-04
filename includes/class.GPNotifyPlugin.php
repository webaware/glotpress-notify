<?php

if (!defined('ABSPATH')) {
	exit;
}

/**
* plugin controller class
*/
class GPNotifyPlugin {

	/**
	* static method for getting the instance of this singleton object
	* @return self
	*/
	public static function getInstance() {
		static $instance = null;

		if (is_null($instance)) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	* hook into WordPress
	*/
	protected function __construct() {
		// clean up after deactivation
		register_deactivation_hook(GPNOTIFY_PLUGIN_FILE, array($this, 'deactivate'));

		// actions and filters
		add_action('init', array($this, 'init'));
		add_action(GPNOTIFY_TASK_NOTIFY_WAITING, array($this, 'taskNotifyWaiting'));
		add_action('gpnotify_waiting_weekly', array($this, 'taskNotifyWaitingWeekly'));
		add_action('gpnotify_waiting_monthly', array($this, 'taskNotifyWaitingMonthly'));
		add_action('admin_init', array($this, 'adminInit'));
		add_action('admin_menu', array($this, 'adminMenu'));
		add_action('plugin_action_links_' . GPNOTIFY_PLUGIN_NAME, array($this, 'pluginActionLinks'));
		add_filter('plugin_row_meta', array($this, 'addPluginDetailsLinks'), 10, 2);

		add_filter( 'cron_schedules', array($this, 'custom_cron_job_custom_recurrence') );
	}

	/**
	* deactivate the plug-in
	*/
	public function deactivate() {
		// remove scheduled tasks
		wp_clear_scheduled_hook(GPNOTIFY_TASK_NOTIFY_WAITING);
		wp_clear_scheduled_hook('gpnotify_waiting_weekly');
	}

	/**
	* init
	*/
	public function init() {
		// load translation strings
		load_plugin_textdomain('glotpress-notify', false, basename(dirname(__FILE__)) . '/languages/');

		// load required classes
		require GPNOTIFY_PLUGIN_ROOT . 'includes/class.GPNotifyData.php';

		// run the notify task daily from the activated site
		if (!wp_next_scheduled(GPNOTIFY_TASK_NOTIFY_WAITING)) {
			wp_schedule_event(time() + 5, 'daily', GPNOTIFY_TASK_NOTIFY_WAITING);
		}
		if (!wp_next_scheduled('gpnotify_waiting_weekly')) {
			$next_week = strtotime(date('Y-m-d 00:00:00', strtotime('next Monday')));
			wp_schedule_single_event($next_week, 'gpnotify_waiting_weekly');
		}
		if (!wp_next_scheduled('gpnotify_waiting_monthly')) {
			$next_month = strtotime(date('Y-m-d 00:00:00', strtotime('first day of next month')));
			wp_schedule_single_event($next_month, 'gpnotify_waiting_monthly');
		}
	}
	
	// Custom Cron Recurrences
	public function custom_cron_job_custom_recurrence( $schedules ) {
		$schedules['weekly'] = array(
				'display' => __( 'Once Weekly', 'glotpress-notify' ),
				'interval' => 604800,
		);
		return $schedules;
	}

	/**
	* initialise settings for admin
	*/
	public function adminInit() {
		add_settings_section(GPNOTIFY_OPTIONS, false, false, GPNOTIFY_OPTIONS);
		register_setting(GPNOTIFY_OPTIONS, GPNOTIFY_OPTIONS, array($this, 'settingsValidate'));
	}

	/**
	* admin menu items
	*/
	public function adminMenu() {
		$label = __('GlotPress Notify', 'glotpress-notify');
		add_menu_page($label, $label, 'read', 'gpnotify', array($this, 'listProjects'));

		$options = get_option(GPNOTIFY_OPTIONS);
		if (!empty($options['gp_prefix'])) {
			$label = __('Subscriptions', 'glotpress-notify');
			add_submenu_page('gpnotify', $label, $label, 'read', 'gpnotify-profile', array($this, 'userNotifySettings'));
		}

		$label = __('Settings', 'glotpress-notify');
		add_submenu_page('gpnotify', $label, $label, 'manage_options', 'gpnotify-settings', array($this, 'settingsPage'));
	}

	/**
	* list GlotPress projects with some statistics
	*/
	public function listProjects() {
		$options = get_option(GPNOTIFY_OPTIONS);

		if (empty($options['gp_prefix'])) {
			require GPNOTIFY_PLUGIN_ROOT . 'views/admin-no-prefix.php';
		}
		else {
			try {
				$glotpress = GPNotifyData::getInstance($options['gp_prefix']);
				$projects = $glotpress->listProjects();

				// get list of projects with strings waiting for approval / rejection
				$scope = !empty($_REQUEST['scope']) ? $_REQUEST['scope']:'all';
				$waiting = $glotpress->listWaitingByProject( $scope );

				require GPNOTIFY_PLUGIN_ROOT . 'views/admin-list-projects.php';
			}
			catch (GPNotifyException $e) {
				require GPNOTIFY_PLUGIN_ROOT . 'views/admin-bad-prefix.php';
			}
		}
	}

	/**
	* settings admin
	*/
	public function settingsPage() {
		$options = get_option(GPNOTIFY_OPTIONS, array('gp_prefix' => '', 'email_from' => ''));

		if (!isset($options['email_from'])) {
			$options['email_from'] = '';
		}

		require GPNOTIFY_PLUGIN_ROOT . 'views/settings-form.php';
	}

	/**
	* validate settings on save
	* @param array $input
	* @return array
	*/
	public function settingsValidate($input) {
		$output = array();

		$output['email_from'] = trim($input['email_from']);
		$output['gp_prefix'] = trim($input['gp_prefix']);

		if (!empty($input['gp_prefix'])) {
			try {
				$glotpress = GPNotifyData::getInstance($input['gp_prefix']);
			}
			catch (GPNotifyException $e) {
				add_settings_error(GPNOTIFY_OPTIONS, 'settings_updated', $e->getMessage());
			}
		}

		return $output;
	}

	/**
	* user profile notifications settings page
	*/
	public function userNotifySettings() {
		$user = wp_get_current_user();
		$options = get_option(GPNOTIFY_OPTIONS);

		if (!empty($options['gp_prefix'])) {
			try {
				$glotpress = GPNotifyData::getInstance($options['gp_prefix']);
				$projects = $glotpress->listProjects();
				$translation_sets = $glotpress->listTranslationSets();
				
				$project_options = $this->userOptionProjectsGet($user->ID, $options['gp_prefix']);

				// are we saving?
				$update_message = false;
				if (!empty($_POST['submit'])) {
					check_admin_referer('subscribe', 'gpnotify_nonce');

					$project_options['waiting'] = array();
					$project_options['frequency'] = array();
					if( !empty($_POST['frequency']) && is_array($_POST['frequency']) ){
						foreach( $_POST['frequency'] as $f ){
							if( in_array($f, array('day','week','month')) ){
								$project_options['frequency'][] = $f;
							}
						}
					}
					foreach ($projects as $project) {
						if (!empty($_POST["gpnotify_projects_{$project->id}_waiting"])) {
							$project_options['waiting'][$project->id] = 1;
						}else{
							foreach( $translation_sets[$project->id] as $translation_set ){
								if (!empty($_POST["gpnotify_sets_{$project->id}"][$translation_set->locale])) {
									if( empty($project_options['waiting'][$project->id]) ) $project_options['waiting'][$project->id] = array();
									$project_options['waiting'][$project->id][$translation_set->locale] = 1;
								}
							}
						}
					}
					$this->userOptionProjectsUpdate($user->ID, $options['gp_prefix'], $project_options);
					$update_message = esc_html(__('Subscriptions saved.', 'glotpress-notify'));
				}

				$form_action = admin_url('admin.php?page=gpnotify-profile');
				require GPNOTIFY_PLUGIN_ROOT . 'views/admin-user-fields.php';
			}
			catch (GPNotifyException $e) {
				require GPNOTIFY_PLUGIN_ROOT . 'views/admin-bad-prefix.php';
			}
		}
	}

	/**
	* get user option for GlotPress projects
	* @param int $user_id
	* @param string $gp_prefix
	* @return string
	*/
	protected function userOptionProjectsGet($user_id, $gp_prefix) {
		$projects = get_user_option("gpnotify_{$gp_prefix}projects", $user_id);

		if (!is_array($projects)) {
			$projects = array(
				'waiting' => array(),
				'frequency' => array('day')
			);
		}

		return $projects;
	}

	/**
	* get user option for GlotPress projects
	* @param int $user_id
	* @param string $gp_prefix
	* @param array $projects
	*/
	protected function userOptionProjectsUpdate($user_id, $gp_prefix, $projects) {
		update_user_option($user_id, "gpnotify_{$gp_prefix}projects", $projects);
	}

	/**
	* run scheduled task to notify if there are new waiting strings
	*/
	public function taskNotifyWaiting( $scope = 'day') {
		$options = get_option(GPNOTIFY_OPTIONS, array());
		if (!empty($options['gp_prefix'])) {

			require GPNOTIFY_PLUGIN_ROOT . 'includes/class.GPNotifyWaiting.php';

			try {
				$glotpress = GPNotifyData::getInstance($options['gp_prefix']);
				$projects = $glotpress->listProjects();

				// get list of projects with strings waiting for approval / rejection
				$waiting = $glotpress->listWaitingByProject($scope);

				// get list of notification subscribers
				$users = $glotpress->listSubscribers();
 
				if (!class_exists('GPNotifyFilterUsers')) {
					require GPNOTIFY_PLUGIN_ROOT . 'includes/class.GPNotifyFilterUsers.php';
				}
				$filterUsers = new GPNotifyFilterUsers();

				foreach ($waiting as $project_id => $translations) {
					// see if any users want to be notified
					$users_waiting = $filterUsers->execute($users, $project_id);
					if (!empty($users_waiting)) {
						$notifier = new GPNotifyWaiting($users_waiting, $options['email_from'], false, $scope);
						$subject = sprintf(__('Notification of translations for "%s"', 'glotpress-notify'), $projects[$project_id]->name);
						$notifier->compose($subject, $translations, $scope);
						$notifier->send();
					}
					//now go through users subscribed to specific locales and send per-local notifications
					foreach( $translations as $locale => $translation_set ){
						$users_waiting_by_locale = $filterUsers->execute($users, $project_id, $locale, $scope);
						if( !empty($users_waiting_by_locale) ){
							$notifier = new GPNotifyWaiting($users_waiting_by_locale, $options['email_from']);
							$subject = sprintf(__('Notification of translations for "%1$s" in %2$s', 'glotpress-notify'), $projects[$project_id]->name, $translation_set->locale_name);
							$notifier->compose($subject, array($translation_set), $scope);
							$notifier->send();
						}
					}
				}
			}
			catch (GPNotifyException $e) {
				error_log($e->getMessage());
			}
		}
	}
	public function taskNotifyWaitingWeekly( $scope = 'week' ){
		$this->taskNotifyWaiting($scope);
		//schedule for next week again
		$next_week = strtotime(date('Y-m-d 00:00:00', strtotime('next Monday')));
		wp_schedule_single_event($next_week, 'gpnotify_waiting_weekly');
	}	
	
	public function taskNotifyWaitingMonthly( $scope = 'month' ){
		$this->taskNotifyWaiting($scope);
		//schedule for next month again
		$next_month = strtotime(date('Y-m-d 00:00:00', strtotime('first day of next month')));
		wp_schedule_single_event($next_month, 'gpnotify_waiting_monthly');
	}

	/**
	* add plugin action links
	*/
	public function pluginActionLinks($links) {
		// add settings link
		$settings_link = sprintf('<a href="%s">%s</a>', esc_url(admin_url('admin.php?page=gpnotify-settings')), __('Settings', 'glotpress-notify'));
		array_unshift($links, $settings_link);

		return $links;
	}

	/**
	* action hook for adding plugin details links
	*/
	public function addPluginDetailsLinks($links, $file) {
		if ($file == GPNOTIFY_PLUGIN_NAME) {
			$links[] = sprintf('<a href="https://wordpress.org/support/plugin/glotpress-notify">%s</a>', _x('Get help', 'plugin details links', 'glotpress-notify'));
			$links[] = sprintf('<a href="https://wordpress.org/plugins/glotpress-notify/">%s</a>', _x('Rating', 'plugin details links', 'glotpress-notify'));
			$links[] = sprintf('<a href="https://translate.wordpress.org/projects/wp-plugins/glotpress-notify">%s</a>', _x('Translate', 'plugin details links', 'glotpress-notify'));
			$links[] = sprintf('<a href="http://shop.webaware.com.au/donations/?donation_for=GlotPress+Notify">%s</a>', _x('Donate', 'plugin details links', 'glotpress-notify'));
		}

		return $links;
	}

}
