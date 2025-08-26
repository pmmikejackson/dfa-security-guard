# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This codebase contains Gravity PDF templates for insurance application forms for DFA Insurance. The templates are PHP-based and generate PDF documents for three types of insurance applications:

1. **Alarm and Monitoring Quote** (`alarm-template.php`)
2. **Private Investigator Quote** (`private-investigator-template.php`)  
3. **Security Guard Quote** (`security-guard-template.php`)

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