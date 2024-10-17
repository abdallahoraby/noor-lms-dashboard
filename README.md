# Noorworld LMS Child Theme

**Noorworld LMS** is a child theme for [your parent theme] designed to provide enhanced functionality and customizations for e-learning platforms. This child theme inherits all the functionality and styling from its parent theme while allowing customizations to meet the specific needs of your LMS (Learning Management System).

## Features

- **Custom Design Adjustments**: Modify and extend the design of the parent theme without affecting core files.
- **Optimized for LMS**: Includes custom templates and features to enhance learning experiences.
- **Easy to Extend**: Add additional PHP, CSS, and JavaScript to customize your site as needed.
- **AJAX Functionality**: Improved interactivity with AJAX login and user actions.
- **Fully Responsive**: Mobile-friendly design that works across all devices.

## Installation

### Prerequisites
1. Ensure you have [WordPress](https://wordpress.org/) installed.
2. Make sure your parent theme, [Parent Theme Name], is installed and activated.

### Steps to Install

1. Download the **Noorworld LMS** child theme files from the GitHub repository.
2. Upload the child theme folder to the `/wp-content/themes/` directory on your WordPress installation.
3. Go to the WordPress admin dashboard, navigate to **Appearance > Themes**, and activate the **Noorworld LMS** child theme.

Alternatively, you can upload the theme ZIP file via the WordPress admin dashboard:
1. In the WordPress admin, navigate to **Appearance > Themes**.
2. Click **Add New**, then **Upload Theme**, and select the ZIP file.
3. After uploading, activate the theme.

## Customization

### 1. CSS Customizations
You can add your custom CSS in the `style.css` file of the child theme to override the parent theme’s styles.

### 2. PHP Customizations
Add custom PHP code by modifying `functions.php`. This file is used to extend the parent theme's functionality without modifying its core files.

For example:
- Add custom functions
- Override template files from the parent theme by copying them to the child theme

### 3. JavaScript Customizations
Custom JavaScript can be added in the `custom.js` file or enqueued via the `functions.php` file.

### Example: Enqueuing a Custom Script

You can enqueue your custom script like this in the `functions.php` file:

```php
function noorworld_lms_enqueue_scripts() {
    wp_enqueue_script('noorworld-custom-script', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery'), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'noorworld_lms_enqueue_scripts');
