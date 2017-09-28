# Category Updation

Contributors: Abstain Solution

Tags: category, updation, category updation, category updation via api

Requires at least: 4.8

Tested up to: 4.8.2

Stable tag: 1.0

License: GPLv2 or later

License URI: https://www.gnu.org/licenses/gpl-2.0.html


# Description

User is required to add the respective API url in order to get the desired data.

Once Category Updation plugin is activated it gets categories data using an 
REST API and save them in the system.

A cron is scheduled for every 30 minutes to fetch the data from REST API and 
then save the new categories in data into database, ignoring the existing one.

Also, admin user can trigger the request explicitly by clicking "Update" button 
in adjacent to "Update categories now?" label in "General" settings form.

On deactivating the plugin, scheduled cron and the button in "General" 
settings form will be removed.

# Installation

1. Upload the entire `category-updation` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress. 

# Disclaimer

This plugin is developed on Wordpress v4.8.2. Compatibility with lower versions 
is not guaranteed.
