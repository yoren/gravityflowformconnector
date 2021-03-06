<?php

/**
 * Gravity Flow Update Entry Step
 *
 *
 * @package     GravityFlow
 * @subpackage  Classes/Step
 * @copyright   Copyright (c) 2015-2017, Steven Henty S.L.
 * @license     http://opensource.org/licenses/gpl-3.0.php GNU Public License
 * @since       1.0
 */

if ( class_exists( 'Gravity_Flow_Step' ) ) {

	class Gravity_Flow_Step_Update_Entry extends Gravity_Flow_Step_New_Entry {
		public $_step_type = 'update_entry';

		public function get_label() {
			return esc_html__( 'Update an Entry', 'gravityflowformconnector' );
		}

		/**
		 * Returns the array of settings for this step.
		 *
		 * @return array
		 */
		public function get_settings() {

			$forms          = $this->get_forms();
			$form_choices[] = array(
				'label' => esc_html__( 'Select a Form', 'gravityflowformconnector' ),
				'value' => '',
			);
			foreach ( $forms as $form ) {
				$form_choices[] = array( 'label' => $form->title, 'value' => $form->id );
			}

			$action_choices = $this->action_choices();

			$settings = array(
				'title'  => esc_html__( 'Update an Entry', 'gravityflow' ),
				'fields' => array(
					array(
						'name'          => 'server_type',
						'label'         => esc_html__( 'Site', 'gravityflowformconnector' ),
						'type'          => 'radio',
						'default_value' => 'local',
						'horizontal'    => true,
						'onchange'      => 'jQuery(this).closest("form").submit();',
						'choices'       => array(
							array( 'label' => esc_html__( 'This site', 'gravityflowformconnector' ), 'value' => 'local' ),
							array( 'label' => esc_html__( 'A different site', 'gravityflowformconnector' ), 'value' => 'remote' ),
						),
					),
					array(
						'name'       => 'remote_site_url',
						'label'      => esc_html__( 'Site Url', 'gravityflowformconnector' ),
						'type'       => 'text',
						'dependency' => array(
							'field'  => 'server_type',
							'values' => array( 'remote' ),
						),
					),
					array(
						'name'       => 'remote_public_key',
						'label'      => esc_html__( 'Public Key', 'gravityflowformconnector' ),
						'type'       => 'text',
						'dependency' => array(
							'field'  => 'server_type',
							'values' => array( 'remote' ),
						),
					),
					array(
						'name'       => 'remote_private_key',
						'label'      => esc_html__( 'Private Key', 'gravityflowformconnector' ),
						'type'       => 'text',
						'dependency' => array(
							'field'  => 'server_type',
							'values' => array( 'remote' ),
						),
					),
					array(
						'name'     => 'target_form_id',
						'label'    => esc_html__( 'Form', 'gravityflowformconnector' ),
						'type'     => 'select',
						'onchange' => "jQuery('#action').val('update');jQuery(this).closest('form').submit();",
						'choices'  => $form_choices,
					),
					array(
						'name'       => 'action',
						'label'      => esc_html__( 'Action', 'gravityflowformconnector' ),
						'type'       => count( $action_choices ) == 1 ? 'hidden' : 'select',
						'default_value' => 'update',
						'horizontal' => true,
						'onchange'   => "jQuery(this).closest('form').submit();",
						'choices'    => $action_choices,
					),
				),
			);

			$entry_id_field = array(
				'name'       => 'update_entry_id',
				'label'      => esc_html__( 'Entry ID Field', 'gravityflowformconnector' ),
				'type'       => 'field_select',
				'tooltip'    => __( 'Select the field which will contain the entry ID of the entry that will be updated. This is used to lookup the entry so it can be updated.', 'gravityflowformconnector' ),
				'required'   => true,
				'dependency' => array(
					'field'  => 'action',
					'values' => array( 'update', 'approval', 'user_input' ),
				),
			);

			if ( function_exists( 'gravity_flow_parent_child' ) ) {
				$parent_form_choices = array();
				$entry_meta          = gravity_flow_parent_child()->get_entry_meta( array(), rgget( 'id' ) );

				foreach ( $entry_meta as $meta_key => $meta ) {
					$parent_form_choices[] = array( 'value' => $meta_key, 'label' => $meta['label'] );
				}

				if ( ! empty( $parent_form_choices ) ) {
					$entry_id_field['args']['append_choices'] = $parent_form_choices;
				}
			}

			if ( $this->get_setting( 'target_form_id' ) == $this->get_form_id() ) {
				$self_entry_id_choice = array( array( 'label' => esc_html__( 'Entry ID (Self)', 'gravityflowformconnector' ), 'value' => 'id' ) );
				if ( ! isset( $entry_id_field['args']['append_choices'] ) ) {
					$entry_id_field['args']['append_choices'] = array();
				}
				$entry_id_field['args']['append_choices'] = array_merge( $entry_id_field['args']['append_choices'], $self_entry_id_choice );
			}

			$settings['fields'][] = $entry_id_field;
			$settings['fields'][] = array(
				'name'       => 'approval_status_field',
				'label'      => esc_html__( 'Approval Status Field', 'gravityflowformconnector' ),
				'type'       => 'field_select',
				'dependency' => array(
					'field'  => 'action',
					'values' => array( 'approval' ),
				),
			);

			$mapping_field = array(
				'name'                => 'mappings',
				'label'               => esc_html__( 'Field Mapping', 'gravityflowformconnector' ),
				'type'                => 'generic_map',
				'enable_custom_key'   => false,
				'enable_custom_value' => true,
				'key_field_title'     => esc_html__( 'Field', 'gravityflowformconnector' ),
				'value_field_title'   => esc_html__( 'Value', 'gravityflowformconnector' ),
				'value_choices'       => $this->value_mappings(),
				'key_choices'         => $this->field_mappings(),
				'tooltip'             => '<h6>' . esc_html__( 'Mapping', 'gravityflowformconnector' ) . '</h6>' . esc_html__( 'Map the fields of this form to the selected form. Values from this form will be saved in the entry in the selected form', 'gravityflowformconnector' ),
				'dependency'          => array(
					'field'  => 'action',
					'values' => array( 'update', 'user_input' ),
				),
			);

			$settings['fields'][] = $mapping_field;

			$action = $this->get_setting( 'action' );

			if ( $this->get_setting( 'server_type' ) == 'remote' && in_array( $action, array(
					'approval',
					'user_input',
				) )
			) {
				$target_form_id = $this->get_setting( 'target_form_id' );
				if ( ! empty ( $target_form_id ) ) {
					$settings['fields'][] = array(
						'name'    => 'remote_assignee',
						'label'   => esc_html__( 'Assignee', 'gravityflowformconnector' ),
						'type'    => 'select',
						'choices' => $this->get_remote_assignee_choices( $target_form_id ),
					);
				}
			} elseif ( $this->get_setting( 'server_type' ) == 'local' && $this->get_setting( 'action' ) == 'user_input' ) {
				$target_form_id = $this->get_setting( 'target_form_id' );
				if ( ! empty ( $target_form_id ) ) {
					$settings['fields'][] = array(
						'name'    => 'local_assignee',
						'label'   => esc_html__( 'Assignee', 'gravityflowformconnector' ),
						'type'    => 'select',
						'choices' => $this->get_local_assignee_choices( $target_form_id ),
					);
				}
			}

			return $settings;
		}

		/**
		 * Returns the array of choices for the action setting.
		 *
		 * @return array
		 */
		public function action_choices() {
			$choices = array(
				array( 'label' => esc_html__( 'Update an Entry', 'gravityflow' ), 'value' => 'update' ),
			);

			$target_form_id = $this->get_setting( 'target_form_id' );

			if ( empty( $target_form_id ) ) {
				return $choices;
			}

			$has_approval_step   = false;
			$has_user_input_step = false;

			if ( $this->get_setting( 'server_type' ) == 'remote' ) {
				$steps = $this->get_remote_steps( $target_form_id );
				if ( $steps ) {
					foreach ( $steps as $step ) {
						if ( $step['type'] == 'approval' ) {
							$has_approval_step = true;
						} elseif ( $step['type'] == 'user_input' ) {
							$has_user_input_step = true;
						}
					}
				}
			} else {

				$api   = new Gravity_Flow_API( $target_form_id );
				$steps = $api->get_steps();

				foreach ( $steps as $step ) {
					if ( $step->get_type() == 'approval' ) {
						$has_approval_step = true;
					} elseif ( $step->get_type() == 'user_input' ) {
						$has_user_input_step = true;
					}
				}
			}

			if ( $has_approval_step ) {
				$choices[] = array( 'label' => esc_html__( 'Approval', 'gravityflow' ), 'value' => 'approval' );
			}
			if ( $has_user_input_step ) {
				$choices[] = array( 'label' => esc_html__( 'User Input', 'gravityflow' ), 'value' => 'user_input' );
			}

			return $choices;
		}

		/**
		 * Updates a local entry.
		 *
		 * @return bool Has the step finished?
		 */
		public function process_local_action() {
			$entry = $this->get_entry();

			$target_form_id = $this->target_form_id;

			$api = new Gravity_Flow_API( $target_form_id );

			$steps = $api->get_steps();

			$form = $this->get_form();

			$target_entry_id = rgar( $entry, $this->update_entry_id );

			$target_entry_id = apply_filters( 'gravityflowformconnector_update_entry_id', $target_entry_id, $target_form_id, $entry, $form, $this );

			if ( empty( $target_entry_id ) ) {
				return true;
			}

			$target_entry = GFAPI::get_entry( $target_entry_id );

			if ( is_wp_error( $target_entry ) ) {
				return true;
			}

			$new_entry = $this->do_mapping( $form, $entry );

			$new_entry['form_id'] = $this->target_form_id;

			if ( in_array( $this->action, array( 'update', 'user_input' ) ) ) {
				if ( ! is_wp_error( $target_entry ) ) {
					foreach ( $new_entry as $key => $value ) {
						$target_entry[ (string) $key ] = $value;
					}
					GFAPI::update_entry( $target_entry );
				}
			}

			if ( in_array( $this->action, array( 'approval', 'user_input' ) ) && $steps ) {

				if ( empty( $target_entry['workflow_final_status'] ) || $target_entry['workflow_final_status'] == 'pending' ) {
					$current_step = $api->get_current_step( $target_entry );

					if ( $current_step ) {

						$status = ( $this->action == 'approval' ) ? strtolower( rgar( $entry, $this->approval_status_field ) ) : 'complete';

						if ( empty( $this->local_assignee ) || $this->local_assignee == 'created_by') {
							$assignee_key = gravity_flow()->get_current_user_assignee_key();
							if ( ! $assignee_key && rgar( $entry, 'created_by' ) ) {
								$assignee_key = 'user_id|' . $entry['created_by'];
							}
						} else {
							$assignee_key = $this->local_assignee;
						}

						$assignees = array();

						if ( $assignee_key ) {
							$is_assignee = $current_step->is_assignee( $assignee_key );

							if ( $is_assignee ) {
								$assignee = new Gravity_Flow_Assignee( $assignee_key, $current_step );
								$assignees = array( $assignee );
							} else {
								// Assignee not set by the local_assignee setting or by current user.
								// Could be legacy settings triggered by cron or anonymous form submission.
								// Complete step for all assignees.
								$assignees = $current_step->get_assignees();
							}
						}

						$form = GFAPI::get_form( $this->target_form_id );

						$process_required = false;
						foreach ( $assignees as $assignee ) {
							$result = $current_step->process_assignee_status( $assignee, $status, $form );
							if ( $result ) {
								$process_required = true;
							}
						}

						if ( $process_required ) {
							$api->process_workflow( $target_entry_id );
						}
					}
				}
			}

			return true;
		}

		/**
		 * Updates a remote entry.
		 *
		 *
		 * @return bool Has the step finished?
		 */
		public function process_remote_action() {
			$entry = $this->get_entry();

			$form = $this->get_form();

			$new_entry = $this->do_mapping( $form, $entry );

			$target_form_id = $this->target_form_id;

			$new_entry['form_id'] = $target_form_id;

			$target_entry_id = rgar( $entry, $this->update_entry_id );

			$target_entry_id = apply_filters( 'gravityflowformconnector_update_entry_id', $target_entry_id, $target_form_id, $entry, $form, $this );

			if ( empty( $target_entry_id ) ) {
				return true;
			}

			switch ( $this->action ) {
				case 'update' :
				case 'user_input' :
					$target_entry = $this->get_remote_entry( $target_entry_id );

					foreach ( $new_entry as $key => $value ) {
						$target_entry[ (string) $key ] = $value;
					}

					$result = $this->update_remote_entry( $target_entry );

					$this->log_debug( __METHOD__ . '(): update result - ' . print_r( $result, true ) );

					if ( $this->action == 'user_input' ) {
						$assignee_key = strtolower( urlencode( sanitize_text_field( $this->remote_assignee ) ) );
						$route = 'entries/' . $target_entry_id . '/assignees/' . $assignee_key;
						$body  = json_encode( array( 'status' => 'complete' ) );

						$assignee_update_result = $this->remote_request( $route, 'POST', $body );
						$this->log_debug( __METHOD__ . '(): update assignee result - ' . print_r( $assignee_update_result, true ) );
					}

					break;
				case 'approval' :
					$assignee_key    = strtolower( urlencode( sanitize_text_field( $this->remote_assignee ) ) );
					$status          = sanitize_text_field( strtolower( rgar( $entry, $this->approval_status_field ) ) );
					$route           = sprintf( 'entries/%d/assignees/%s', $target_entry_id, $assignee_key );
					$body            = json_encode( array( 'status' => $status ) );
					$this->remote_request( $route, 'POST', $body );
			}

			return true;
		}

		/**
		 * Returns a remote entry.
		 *
		 * @param $entry_id
		 *
		 * @return bool
		 */
		public function get_remote_entry( $entry_id ) {
			$route  = 'entries/' . $entry_id;
			$result = $this->remote_request( $route );

			return $result;
		}

		/**
		 * Updates a remote entry.
		 *
		 * @param $entry
		 *
		 * @return bool
		 */
		public function update_remote_entry( $entry ) {
			$route  = 'entries/' . absint( $entry['id'] );
			$method = 'PUT';
			$body   = json_encode( $entry );

			$result = $this->remote_request( $route, $method, $body );

			return $result;
		}

		/**
		 * Returns the steps for the remote entry.
		 *
		 * @param $form_id
		 *
		 * @return bool
		 */
		public function get_remote_steps( $form_id ) {
			$route = 'forms/' . $form_id . '/steps';
			$steps = $this->remote_request( $route );

			return $steps;
		}

		/**
		 * Returns the remote assignees.
		 *
		 * @param $form_id
		 *
		 * @return array
		 */
		public function get_remote_assignee_choices( $form_id ) {
			$steps         = $this->get_remote_steps( $form_id );
			if ( empty( $steps ) ) {
				return array();
			}
			$assignee_keys = $choices = array();
			foreach ( $steps as $step ) {
				foreach ( $step['assignees'] as $assignee ) {
					$assignee_keys[ $assignee['key'] ] = $assignee['display_name'];
				}
			}

			foreach ( $assignee_keys as $assignee_key => $display_name ) {
				$choices[] = array( 'label' => $display_name, 'value' => $assignee_key );
			}

			return $choices;
		}

		/**
		 * Returns the remote assignees.
		 *
		 * @param $form_id
		 *
		 * @return array
		 */
		public function get_local_assignee_choices( $form_id ) {

			$steps = gravity_flow()->get_steps( $form_id );

			if ( empty( $steps ) ) {
				return array();
			}

			$assignee_keys = $choices = array();

			foreach ( $steps as $step ) {
				$assignees = $step->get_assignees();
				foreach ( $assignees as $assignee ) {
					$assignee_keys[ $assignee->get_key() ] = $assignee->get_display_name();
				}
			}

			$source_form = $this->get_form();

			$choices[] = array(
				'label' => __( 'Select an assignee', 'gravityflow' ),
				'value' => '',
			);

			if ( rgar( $source_form, 'requireLogin' ) ) {
				$choices[] = array(
					'label' => __( 'User (created_by)', 'gravityflow' ),
					'value' => 'created_by',
				);
			}

			foreach ( $assignee_keys as $assignee_key => $display_name ) {
				$choices[] = array( 'label' => $display_name, 'value' => $assignee_key );
			}

			return $choices;
		}
	}
}



