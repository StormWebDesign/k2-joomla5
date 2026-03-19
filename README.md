# K2 for Joomla 5

A complete migration of the popular K2 content extension to Joomla 5, featuring modern PHP 8.1+ code, namespaced classes, and full compatibility with Joomla 5's architecture.

## About This Fork

This is a **Joomla 5 only** version of K2, maintained by Storm Web Design Ltd. The codebase has been completely modernized to use:

- PSR-4 namespaces throughout
- PHP 8.1+ typed properties and modern syntax
- Joomla 5 MVC patterns and service providers
- Modern database query patterns with parameter binding
- Event subscriber pattern for plugins
- Web Asset Manager for frontend assets

## Features

K2 provides a complete replacement of the default article system in Joomla:

- **Rich Content Forms** - Additional fields for images, videos, image galleries, and attachments
- **Automatic Image Resizing** - Uploaded images auto-resize to 6 configurable dimensions
- **Comments System** - Built-in commenting with moderation
- **Tagging** - Comprehensive tag management
- **Extra Fields** - Extend content forms for product catalogs, portfolios, etc.
- **Frontend Editing** - Easy-to-use access control settings
- **Flexible Templating** - Powerful sub-templating system
- **User Profiles** - Extended user profiles and groups
- **Media Manager** - Drag and drop media management
- **Plugin API** - Extend item/category/user forms

## Requirements

- **Joomla 5.0+**
- **PHP 8.1+**
- **MySQL 5.7+ / MariaDB 10.2+**

## Installation

1. Download the latest release
2. Install via Joomla's Extension Manager
3. Navigate to Components > K2 to begin configuration

## Migration from K2 2.x

If migrating from an older K2 installation:

1. Ensure your Joomla installation is updated to version 5.0+
2. Backup your database and files
3. Install this version of K2 as an update
4. Review [MIGRATION_NOTES.md](MIGRATION_NOTES.md) for detailed changes

## Documentation

- [Migration Notes](MIGRATION_NOTES.md) - Technical details of the Joomla 5 migration
- [Changelog](CHANGELOG.md) - Version history and release notes

## Template Overrides

Template overrides remain compatible with previous versions. Place your overrides in:

```
templates/{your-template}/html/com_k2/
```

## Third-Party Extensions

K2-specific extensions from the Joomla Extensions Directory may require updates to work with this version. Contact the extension developers for Joomla 5 compatibility.

## Support

For issues and feature requests, please use the GitHub Issues tracker.

## Credits

- **Original K2** - JoomlaWorks Ltd. (https://getk2.org)
- **Joomla 5 Migration** - Russell English, Storm Web Design Ltd.

## License

GNU General Public License v2 or later
https://gnu.org/licenses/gpl.html

---

Copyright (C) 2026 Storm Web Design Ltd. All rights reserved.
https://stormwebdesign.co.uk
