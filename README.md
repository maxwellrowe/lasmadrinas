# Las Madrinas Staging Site

This repository contains the custom WordPress code for the Las Madrinas staging site.

It is not a full WordPress install. The repo currently includes:

- A custom theme: `themes/JointsWP-CSS-master`
- A custom payment integration plugin: `plugins/gf-deepstack-payments`
- A custom member roster plugin: `plugins/lm-user-roster`

## Overview

The staging site appears to be built on a customized JointsWP/Foundation-based theme with additional templates, menus, shortcodes, and member-facing site sections.

Key pieces:

- `themes/JointsWP-CSS-master`
  - Main site theme
  - Uses Foundation 6.x assets
  - Contains custom templates for members, debutantes, press, endowments, calendars, and profiles
  - Registers multiple menu locations for public, member, debutante, stag, and footer navigation
  - Includes custom shortcodes and editor styling
- `plugins/gf-deepstack-payments`
  - Custom Gravity Forms integration for Deepstack payments
  - Contains form-specific handlers for dues, donations, ball payments, and other payment flows
- `plugins/lm-user-roster`
  - Adds the `[lm-user-roster]` shortcode
  - Displays member users with profile images and ACF-backed profile data

## Repository Structure

```text
.
├── plugins/
│   ├── gf-deepstack-payments/
│   └── lm-user-roster/
└── themes/
    └── JointsWP-CSS-master/
```

## Local / Staging Usage

Because this repo does not include WordPress core, it should be used inside an existing WordPress environment.

Typical setup:

1. Start with a working WordPress install for local or staging use.
2. Copy or symlink the theme into `wp-content/themes/`.
3. Copy or symlink the plugins into `wp-content/plugins/`.
4. Activate the theme and required plugins in the WordPress admin.
5. Confirm any environment-specific settings, payment credentials, and plugin dependencies before testing forms.

## WordPress Features In Use

From the code currently in this repo, the site relies on several WordPress features and conventions:

- Custom page templates and single templates
- Multiple navigation menus for different audience types
- Widget areas / sidebars
- Shortcodes for content blocks and calendar/press/endowment output
- Gravity Forms for payment-related workflows
- Advanced Custom Fields (or equivalent ACF functions) in at least part of the member/profile experience

## Theme Notes

The custom theme is based on `JointsWP - CSS` and includes:

- Foundation assets under `foundation-sites/dist/`
- Site CSS and responsive CSS under `assets/styles/`
- Site JavaScript under `assets/scripts/`
- Theme functionality split into `functions/` includes

Primary theme bootstrap file:

- `themes/JointsWP-CSS-master/functions.php`

## Plugin Notes

### `gf-deepstack-payments`

This plugin appears to power payment flows tied to Gravity Forms, including:

- make a payment
- annual appeal
- ball payments
- dues
- public support donations

Maintenance note:

- Payment secrets and API keys should ideally be managed outside committed source code for staging/production safety.

### `lm-user-roster`

This plugin provides a member roster shortcode:

```text
[lm-user-roster]
```

It supports theme template overrides via:

```text
yourtheme/lm-user-roster/template.php
```

## Editing Guidelines

When making updates in this repo:

- Theme PHP templates live in `themes/JointsWP-CSS-master/`
- Shared theme logic lives in `themes/JointsWP-CSS-master/functions/`
- Front-end styles live in `themes/JointsWP-CSS-master/assets/styles/`
- Front-end scripts live in `themes/JointsWP-CSS-master/assets/scripts/`
- Payment form logic lives in `plugins/gf-deepstack-payments/forms/`

If you are changing staging behavior, test:

- public navigation
- member/debutante/stag navigation
- custom shortcodes
- profile/member roster rendering
- Gravity Forms payment flows

## Recommended Follow-Ups

- Rename the theme from the generic `JointsWP - CSS` metadata to Las Madrinas-specific values
- Move payment credentials to environment-managed configuration
- Document required plugins and staging deployment steps in more detail as the workflow gets standardized

