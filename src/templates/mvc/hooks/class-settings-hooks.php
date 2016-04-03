<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class PluginPrefixSettingsHooks extends PluginPrefixHooks {

	public function migrate__register__activate() {
		// anything to setup?

		// migration ?
		$this->service('model')->structure();
	}

	public function clean_up__register__deactivate() {
		// anything to wind down or remove?
	}

	public function main__admin_menu__action() {
		// add_menu_page( __('Page Title', 'plugin-folder'), __('Menu Ttile', 'plugin-folder'), 'read', 'plugin-folder-settings', array( $this, 'settings_page'));
	}

	public function settings_page() {
		// link to settings controller
		// $this->service('settings_controller')->index();
	}
}
