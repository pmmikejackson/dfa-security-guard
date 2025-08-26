# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This codebase contains Gravity PDF templates and webhook integrations for insurance application forms for DFA Insurance. The project includes PHP-based PDF generation templates and webhook handlers for integration with Momentum AMS (NowCerts).

### Insurance Application Types

1. **Security Guard Quote** 
   - PDF Template: `securityGuard/nc-security-guard-template.php`
   - Webhook Handler: `securityGuard/suecurityGuardWebhook.php`
   - Config: `securityGuard/config/nc_security_guard_config.php`
   - Form ID: 7
   
2. **Private Investigator Quote**
   - PDF Template: `PI/private-investigator-template.php`
   - Form ID: TBD
   
3. **Alarm and Monitoring Quote**
   - PDF Template: `Alarm/alarm-template.php`
   - Form ID: TBD

## Architecture

### Template Structure
- Templates follow Gravity PDF v4/v5/v6 conventions
- Each template checks for GFForms class to prevent direct access
- Templates have access to standard Gravity PDF variables: `$form`, `$entry`, `$form_data`, `$settings`, `$fields`, `$config`
- Field values are accessed using merge tags like `{field_name:field_id}`

### Common Template Components
1. **Header Section**: DFA logo and contact information
2. **Form Sections**: Organized into numbered sections with yellow background headers
3. **Field Placeholders**: Uses Gravity Forms merge tags format `{field_name:id}`
4. **Tables**: Extensive use of HTML tables for layout with inline styles
5. **Signatures**: Image-based signatures using `<img src={signature:id}>`

## Key Implementation Details

- Templates are licensed under GPLv2
- Required PDF Version: 4.0
- Templates include inline CSS styling within `<style>` tags
- Page breaks handled with `<pagebreak/>` tag
- All templates include DFA branding with logo at: https://forms.dfainsure.com/wp-content/uploads/2024/01/DFAlogo-187x100.png
- Company address: PO Box 1868, Rockwall, Texas 75087

## Webhook Integration

### Security Guard Webhook
The Security Guard application includes webhook integration with Momentum AMS (NowCerts) for automatic data transfer upon form submission.

#### Configuration
- **Endpoint**: `https://api.nowcerts.com/api/PushJsonQuoteApplications`
- **Method**: POST with JSON payload
- **Form ID**: 7 (Security Guard Application)
- **File**: `securityGuard/suecurityGuardWebhook.php`

#### How It Works
1. The webhook filter intercepts Gravity Forms submissions for Form ID 7
2. Transforms field ID-based data structure to human-readable label-based JSON
3. Groups complex fields (Name, Address) into nested objects
4. Excludes non-data fields (sections, HTML, page breaks, captcha)
5. Sends formatted JSON to Momentum AMS API

#### Field Mapping
The webhook automatically converts Gravity Forms field IDs to field labels or admin labels. For example:
- Field ID "208" → "Company Name"
- Field ID "209" → "Contact Information"
- Multi-input fields are grouped (e.g., Name → {First: "John", Last: "Doe"})

#### Installation
1. Add webhook file to WordPress (mu-plugins or theme functions.php)
2. Configure Gravity Forms Webhooks Add-On with the Momentum API endpoint
3. Set webhook to use "All Fields" in the body configuration
4. The filter will automatically transform the data structure

#### Debugging
Enable WordPress debug logging to troubleshoot:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
```
Check logs at `/wp-content/debug.log` for entries prefixed with `[GF Webhooks]`

### Future Webhook Integrations
Private Investigator and Alarm Monitoring forms will follow similar patterns once their form IDs and Momentum endpoints are configured.