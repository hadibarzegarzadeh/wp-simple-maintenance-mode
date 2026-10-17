# Simple Maintenance Mode

A WordPress plugin that shows a maintenance page (HTTP 503) to visitors while administrators keep full access. Design your maintenance pages with Elementor, create as many as you like, and choose which one is shown.

## Features
- Enable or disable from the **Maintenance** menu in the admin
- Design maintenance pages visually with **Elementor** (full-width Canvas template by default)
- Create multiple maintenance pages and pick the active one from a dropdown
- The selected page is shown on every URL of the site until maintenance mode is turned off
- Administrators (`manage_options`) always see the real site, with an admin bar notice while maintenance is on
- Fallback text message when no page is selected
- Works without Elementor using the classic editor

## Installation
1. Copy `simple-maintenance-mode.php` into `wp-content/plugins/simple-maintenance-mode/`
2. Activate the plugin
3. (Recommended) Install and activate Elementor

## Usage
1. Go to **Maintenance** and click **Create new page with Elementor**
2. Design the page in Elementor and click **Publish**
3. Back in **Maintenance**, select the page, tick **Enable maintenance mode** and save

## Notes
- Only published pages can be selected
- Visitors receive a 503 status with `Retry-After`, which is search-engine friendly
- Not yet tested against all Elementor versions

## License
GPL-2.0-or-later
