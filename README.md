# Donation System

## Overview
The Donation System is a PHP-based application designed to manage donations efficiently. It provides an admin interface for managing donations, importing data, and synchronizing with Google Sheets. The system also includes features for generating visual representations of donation data through charts.

## Project Structure
```
donation
├── donation.php              // Main file containing core logic and initialization
├── includes
│   ├── admin-page.php       // Admin interface for managing donations and settings
│   ├── data-import.php      // Handles import of donor information and transaction records
│   ├── google-api.php       // Synchronizes with Google Sheets
│   ├── chart-generator.php   // Generates charts based on donation data
│   ├── shortcode-handler.php // Processes shortcodes for displaying donation information
│   └── license-check.php    // Checks validity of the license key
├── assets
│   ├── js
│   │   ├── admin.js         // JavaScript for admin interface
│   │   └── frontend.js      // JavaScript for frontend features
│   ├── css
│   │   ├── admin.css        // CSS styles for admin interface
│   │   └── frontend.css     // CSS styles for frontend display
├── vendor
│   └── autoload.php         // Autoloading classes and libraries
└── README.md                // Documentation and setup instructions
```

## Installation
1. Clone the repository to your local machine.
2. Navigate to the project directory.
3. Run `composer install` to install the required dependencies.

## Usage
- Access the admin interface through the designated URL to manage donations and settings.
- Use the data import feature to upload donor information and transaction records.
- Synchronize with Google Sheets to keep your data updated.
- Utilize shortcodes in your posts or pages to display donation information dynamically.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.