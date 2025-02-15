Image Optimizer - Joomla Plugin
Image Optimizer is a Joomla system plugin that automatically converts uploaded images to WebP format and optionally uploads them to a CDN for better performance and faster page load times.

Features
✅ Converts JPEG, PNG, and GIF images to WebP automatically
✅ Works with Joomla Media Manager uploads
✅ Replaces images in frontend rendering with WebP for optimized performance
✅ Optional CDN upload support
✅ Simple configuration via Joomla Plugin Manager

Installation
Download the latest version of the plugin.
Go to Extensions > Manage > Install in Joomla Admin.
Upload the plg_system_imageoptimizer.zip file.
Navigate to Extensions > Plugins and search for "Image Optimizer".
Click Enable to activate the plugin.
Configuration
After enabling the plugin, go to System > Plugins > Image Optimizer, and configure the following options:

Enable CDN Upload: Enable or disable automatic upload of WebP images to a CDN.
CDN Upload URL: Set the CDN endpoint URL where images should be uploaded.
How It Works
When an image is uploaded via Joomla Media Manager, the plugin converts it to WebP format.
When a page is rendered, the plugin scans <img> tags and replaces image URLs with their WebP versions if available.
If CDN upload is enabled, the WebP image is automatically sent to the specified CDN.
File Structure
bash
Copy
Edit
/imageoptimizer
│── imageoptimizer.php           # Main plugin file  
│── imageoptimizer.xml           # Plugin manifest file  
│── language/  
│   ├── en-GB/  
│   │   ├── en-GB.plg_system_imageoptimizer.ini    # Language strings  
│   │   ├── en-GB.plg_system_imageoptimizer.sys.ini  # System messages  
│── LICENSE.txt                    # License information  
│── README.md                       # Documentation  
License
This project is licensed under the GNU General Public License v2.0 (GPL-2.0).

Support & Issues
If you encounter any issues or need support, feel free to open an issue or contact the developer.