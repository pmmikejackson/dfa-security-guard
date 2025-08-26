<?php
/**
 * Security Guard Webhook Data Formatter for Gravity Forms
 * 
 * File: securityGuardWebhook.php (Note: Consider renaming to security-guard-webhook.php for consistency)
 * Version: 1.0.0
 * Author: Mike Jackson
 * Company: DFA Insurance
 * 
 * PURPOSE:
 * --------
 * This WordPress filter transforms Gravity Forms webhook data from field ID-based structure 
 * to a human-readable label-based JSON structure for Momentum AMS (NowCerts) integration.
 * 
 * INTEGRATION DETAILS:
 * --------------------
 * - Target API: https://api.nowcerts.com/api/PushJsonQuoteApplications
 * - Form ID: 7 (Security Guard Application Form)
 * - Method: POST with JSON payload
 * - Authentication: Handled by Gravity Forms Webhooks Add-On settings
 * 
 * HOW IT WORKS:
 * -------------
 * 1. Intercepts the default Gravity Forms webhook payload before transmission
 * 2. Converts numeric field IDs (e.g., "208") to readable labels (e.g., "Company Name")
 * 3. Groups complex multi-input fields (Name, Address) into nested objects
 * 4. Excludes non-data fields (sections, HTML blocks, page breaks, captcha)
 * 5. Returns clean JSON structure compatible with Momentum AMS requirements
 * 
 * INSTALLATION:
 * -------------
 * 1. Add this file to your WordPress installation (mu-plugins or theme functions.php)
 * 2. Configure Gravity Forms Webhooks Add-On with Momentum API endpoint
 * 3. Set webhook to use "All Fields" in the body (this filter will override)
 * 4. Test with a form submission and check WordPress debug logs
 * 
 * DEBUGGING:
 * ----------
 * Enable WordPress debug logging in wp-config.php:
 *   define( 'WP_DEBUG', true );
 *   define( 'WP_DEBUG_LOG', true );
 * Check logs at: /wp-content/debug.log
 */

/**
 * Filter: gform_webhooks_request_data
 * 
 * Modifies the webhook payload structure for Security Guard form submissions.
 * This filter runs after form submission but before the webhook is sent.
 * 
 * @param array $request_data Original webhook data (typically field IDs as keys)
 * @param array $feed         Webhook feed configuration from GF settings
 * @param array $entry        Form entry data containing all submitted values
 * @param array $form         Complete form structure with field definitions
 * 
 * @return array Modified webhook data with field labels as keys
 * 
 * @see https://docs.gravityforms.com/gform_webhooks_request_data/
 */
add_filter( 'gform_webhooks_request_data', function ( $request_data, $feed, $entry, $form ) {

	/**
	 * FORM ID CONFIGURATION
	 * Update this array to include additional forms that should use this formatter
	 * Current: Security Guard Form (ID: 7)
	 * 
	 * To add more forms:
	 * $allowed = array( 7, 8, 9 ); // Multiple form IDs
	 */
	$allowed = array( 7 ); // Security Guard form only
	
	// Only process specified forms, return original data for others
	if ( ! in_array( (int) rgar( $form, 'id' ), $allowed, true ) ) {
		return $request_data;
	}

	/**
	 * DEBUG LOGGING
	 * Logs webhook processing to WordPress debug.log
	 * Useful for troubleshooting integration issues
	 * View logs at: /wp-content/debug.log (when WP_DEBUG_LOG is enabled)
	 */
	error_log( sprintf('[GF Webhooks] Processing Security Guard form %d, entry %s for Momentum AMS', 
		(int)$form['id'], 
		rgar($entry,'id') 
	));

	/**
	 * CONFIGURATION OPTIONS
	 * 
	 * $include_empty: Controls whether empty fields are included in payload
	 *   - false (default): Only sends fields with values (reduces payload size)
	 *   - true: Sends all fields even if empty (use if Momentum requires complete field list)
	 * 
	 * Adjust based on Momentum AMS requirements
	 */
	$include_empty = false;
	
	// Initialize the formatted output array
	$out = array();

	/**
	 * FIELD PROCESSING LOOP
	 * Iterates through all form fields and formats them for the API
	 */
	foreach ( $form['fields'] as $field ) {
		
		/**
		 * SKIP NON-DATA FIELDS
		 * These field types don't contain user input:
		 * - section: Visual dividers in the form
		 * - html: Static content blocks
		 * - page: Multi-page form navigation
		 * - captcha: Anti-spam verification
		 */
		if ( in_array( $field->type, array( 'section', 'html', 'page', 'captcha' ), true ) ) {
			continue;
		}

		/**
		 * DETERMINE FIELD LABEL
		 * Priority: Admin Label > Field Label
		 * 
		 * Admin Labels are preferred because they:
		 * - Are typically more API-friendly (no spaces, consistent naming)
		 * - Can be customized without affecting form display
		 * - Example: Display "Company Name" to user, send as "company_name" to API
		 */
		$label = trim( (string) $field->adminLabel ) !== '' ? $field->adminLabel : $field->label;

		/**
		 * HANDLE COMPLEX MULTI-INPUT FIELDS
		 * 
		 * Some fields have multiple inputs that should be grouped:
		 * - Name field: First Name, Last Name, Prefix, Suffix
		 * - Address field: Street, City, State, ZIP, Country
		 * - Checkbox field: Multiple selected options
		 * 
		 * These are identified by having a non-empty inputs array
		 */
		if ( is_array( $field->inputs ) && $field->inputs ) {
			$group = array();
			
			foreach ( $field->inputs as $input ) {
				/**
				 * INPUT ID FORMAT
				 * Gravity Forms uses decimal IDs for sub-inputs
				 * Example: Field 208 with 3 inputs → 208.1, 208.2, 208.3
				 */
				$iid      = (string) $input['id'];
				
				// Retrieve raw value from form entry
				$raw      = rgar( $entry, $iid );
				
				/**
				 * FORMAT DISPLAY VALUE
				 * GFCommon::get_lead_field_display handles special formatting:
				 * - Date fields: Formats according to field settings
				 * - Price fields: Adds currency formatting
				 * - File uploads: Converts to URL
				 * - Choice fields: Shows selected label (not value)
				 */
				$display  = GFCommon::get_lead_field_display( 
					$field, 
					$raw, 
					rgar( $entry, 'currency' ), 
					false, // Use value, not URL (except for file uploads)
					'text' // Output as plain text, not HTML
				);
				
				// Use input's specific label if available, otherwise use field label
				$sublabel = isset( $input['label'] ) && $input['label'] !== '' ? $input['label'] : $label;

				// Add to group if value exists or if including empty fields
				if ( $include_empty || ($display !== '' && $display !== null) ) {
					$group[ $sublabel ] = $display;
				}
			}
			
			// Add grouped field to output if it contains data
			if ( ! empty( $group ) || $include_empty ) {
				$out[ $label ] = $group;
			}
			
		} else {
			/**
			 * HANDLE SIMPLE SINGLE-INPUT FIELDS
			 * 
			 * Most fields fall into this category:
			 * - Text, Email, Phone, Number
			 * - Dropdown, Radio buttons
			 * - Textarea, Date, Time
			 * - File upload, Website, etc.
			 */
			$raw     = rgar( $entry, (string) $field->id );
			$display = GFCommon::get_lead_field_display( 
				$field, 
				$raw, 
				rgar( $entry, 'currency' ), 
				false, // Use value, not URL
				'text' // Format as text
			);

			// Add to output if value exists or if including empty fields
			if ( $include_empty || ($display !== '' && $display !== null) ) {
				$out[ $label ] = $display;
			}
		}
	}

	/**
	 * RETURN FORMATTED DATA
	 * 
	 * This replaces the default Gravity Forms webhook payload
	 * The data will be sent as JSON to Momentum AMS endpoint
	 * 
	 * Example output structure:
	 * {
	 *   "Company Name": "ABC Security Inc",
	 *   "Contact Name": {
	 *     "First": "John",
	 *     "Last": "Doe"
	 *   },
	 *   "Phone": "(555) 123-4567",
	 *   "Email": "john@abcsecurity.com",
	 *   ...
	 * }
	 */
	return $out;

}, 10, 4 );