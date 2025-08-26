<?php
/**
 * Gravity Forms Webhooks → send label-keyed JSON (replaces "All Fields" body).
 */
add_filter( 'gform_webhooks_request_data', function ( $request_data, $feed, $entry, $form ) {

	// Limit to specific form(s)
	$allowed = array( 7 ); // ← your form ID(s)
	if ( ! in_array( (int) rgar( $form, 'id' ), $allowed, true ) ) {
		return $request_data;
	}

	// Breadcrumb so you can see it fired
	error_log( sprintf('[GF Webhooks] Rewriting payload for form %d, entry %s', (int)$form['id'], rgar($entry,'id') ) );

	$include_empty = false; // set true to include empty fields
	$out = array();

	foreach ( $form['fields'] as $field ) {
		// Skip non-data fields
		if ( in_array( $field->type, array( 'section', 'html', 'page', 'captcha' ), true ) ) {
			continue;
		}

		$label = trim( (string) $field->adminLabel ) !== '' ? $field->adminLabel : $field->label;

		// Multi-input fields (Name, Address, etc.)
		if ( is_array( $field->inputs ) && $field->inputs ) {
			$group = array();
			foreach ( $field->inputs as $input ) {
				$iid      = (string) $input['id'];   // e.g., 208.3
				$raw      = rgar( $entry, $iid );
				$display  = GFCommon::get_lead_field_display( $field, $raw, rgar( $entry, 'currency' ), false, 'text' );
				$sublabel = isset( $input['label'] ) && $input['label'] !== '' ? $input['label'] : $label;

				if ( $include_empty || ($display !== '' && $display !== null) ) {
					$group[ $sublabel ] = $display;
				}
			}
			if ( ! empty( $group ) || $include_empty ) {
				$out[ $label ] = $group;
			}
		} else {
			$raw     = rgar( $entry, (string) $field->id );
			$display = GFCommon::get_lead_field_display( $field, $raw, rgar( $entry, 'currency' ), false, 'text' );

			if ( $include_empty || ($display !== '' && $display !== null) ) {
				$out[ $label ] = $display;
			}
		}
	}

	return $out; // replaces the default ID-keyed blob

}, 10, 4 );