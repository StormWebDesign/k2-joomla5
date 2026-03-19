# K2 for Joomla 5 - Migration Notes

## Overview

This document outlines the migration of K2 from Joomla 3/legacy patterns to Joomla 5 native architecture.

## Key Changes

### Namespace Structure

All PHP classes have been migrated to proper PSR-4 namespaces:

```
Administrator Component:
Joomla\Component\K2\Administrator\Controller\*
Joomla\Component\K2\Administrator\Model\*
Joomla\Component\K2\Administrator\View\*
Joomla\Component\K2\Administrator\Table\*
Joomla\Component\K2\Administrator\Extension\K2Component

Site Component:
Joomla\Component\K2\Site\Controller\*
Joomla\Component\K2\Site\Model\*
Joomla\Component\K2\Site\View\*
Joomla\Component\K2\Site\Helper\*
Joomla\Component\K2\Site\Service\Router

System Plugin:
Joomla\Plugin\System\K2\Extension\K2Plugin

Modules:
Joomla\Module\K2Content\Site\Dispatcher\Dispatcher
Joomla\Module\K2Content\Site\Helper\K2ContentHelper
```

### Removed Legacy Code

The following legacy patterns have been removed:

1. **JFactory calls** - Replaced with:
   - `Factory::getApplication()`
   - `Factory::getContainer()->get(DatabaseInterface::class)`
   - `$app->getIdentity()` for user

2. **JRequest** - Replaced with `$app->input->get()`

3. **jimport()** - Removed entirely, using native PHP autoloading

4. **JControllerLegacy/JControllerForm** - Replaced with:
   - `Joomla\CMS\MVC\Controller\BaseController`
   - `Joomla\CMS\MVC\Controller\AdminController`
   - `Joomla\CMS\MVC\Controller\FormController`

5. **JModelLegacy/JModelList** - Replaced with:
   - `Joomla\CMS\MVC\Model\ListModel`
   - `Joomla\CMS\MVC\Model\AdminModel`
   - `Joomla\CMS\MVC\Model\ItemModel`

6. **JTable** - Replaced with `Joomla\CMS\Table\Table`

7. **JDispatcher** - Replaced with event subscriber pattern

8. **JError** - Replaced with PHP exceptions

9. **K2_JVERSION checks** - Removed all Joomla 1.5/2.5/3.x branching

### Database Query Changes

All database queries now use:
- `$db->getQuery(true)` with proper chaining
- `ParameterType::INTEGER`, `ParameterType::STRING` for bindings
- Named parameters with `bind()` method
- `quoteName()` and `quote()` for identifiers and values

Example:
```php
$query->select(['a.*', 'c.name AS category_name'])
    ->from($db->quoteName('#__k2_items', 'a'))
    ->where($db->quoteName('a.id') . ' = :id')
    ->bind(':id', $id, ParameterType::INTEGER);
```

### Component Architecture

#### Service Provider
New file: `administrator/components/com_k2/services/provider.php`

Registers:
- MVCFactory
- ComponentDispatcherFactory
- RouterFactory
- CategoryService

#### Extension Class
New file: `administrator/components/com_k2/src/Extension/K2Component.php`

Implements:
- `BootableExtensionInterface`
- `CategoryServiceInterface`
- `RouterServiceInterface`

### Router

Migrated to `RouterView` pattern:
- File: `components/com_k2/src/Service/Router.php`
- Extends `Joomla\CMS\Component\Router\RouterView`
- Uses `RouterViewConfiguration` for views
- Implements `MenuRules`, `StandardRules`, `NomenuRules`

### Installation Script

New file: `script.php`
- Implements `InstallerScriptInterface`
- Methods: `preflight()`, `postflight()`, `install()`, `update()`, `uninstall()`
- Typed properties for `$minimumPhp` and `$minimumJoomla`

### Plugin Migration

System plugin now uses:
- `SubscriberInterface` for event subscription
- `CMSPlugin` base class
- Service provider pattern (`services/provider.php`)
- Proper namespace in manifest

### Module Migration

Modules now use:
- `AbstractModuleDispatcher` for dispatching
- `HelperFactoryAwareInterface` for helper access
- Service provider pattern
- `<namespace>` tag in manifest

## File Structure Changes

### Before (Legacy)
```
administrator/components/com_k2/
├── controllers/
│   └── items.php (class K2ControllerItems)
├── models/
│   └── items.php (class K2ModelItems)
├── views/
│   └── items/
│       └── view.html.php
└── k2.php
```

### After (Joomla 5)
```
administrator/components/com_k2/
├── services/
│   └── provider.php
├── src/
│   ├── Controller/
│   │   └── ItemsController.php (Joomla\Component\K2\Administrator\Controller\ItemsController)
│   ├── Model/
│   │   └── ItemsModel.php
│   ├── View/
│   │   └── Items/
│   │       └── HtmlView.php
│   ├── Table/
│   │   └── ItemTable.php
│   └── Extension/
│       └── K2Component.php
└── k2.xml (with <namespace> tag)
```

## Compatibility Notes

### Minimum Requirements
- PHP 8.1+
- Joomla 5.0+
- MySQL 5.7+ / MariaDB 10.2+

### Template Overrides
Template overrides remain compatible. Place overrides in:
- `templates/{template}/html/com_k2/`

### Event Names
K2 plugin events remain the same:
- `onK2BeforeDisplay`
- `onK2AfterDisplay`
- `onK2AfterDisplayTitle`
- `onK2BeforeDisplayContent`
- `onK2AfterDisplayContent`
- `onK2UserDisplay`
- `onK2CommentsCounter`
- `onK2CommentsBlock`

### Constants
The following constants are still defined for backwards compatibility:
- `K2_CURRENT_VERSION` - Set to '3.0.0'
- `K2_JVERSION` - Set to '50'
- `DS` - Set to `DIRECTORY_SEPARATOR`

## Breaking Changes

1. **PHP 8.1 Required** - Code uses typed properties and modern PHP syntax
2. **No Joomla 3/4 Support** - This version is Joomla 5 only
3. **JoomFish Integration Removed** - Use Joomla's native multilanguage
4. **Legacy K2 User Profile** - The user profile extension system may need review

## Testing Checklist

- [ ] Fresh installation on Joomla 5
- [ ] Update from K2 2.x on Joomla 4 (if applicable)
- [ ] Create/Edit/Delete items
- [ ] Create/Edit/Delete categories
- [ ] Tag management
- [ ] Comment moderation
- [ ] User groups and permissions
- [ ] Extra fields (all types)
- [ ] Media manager
- [ ] Frontend item view
- [ ] Frontend category listing
- [ ] Frontend tag filtering
- [ ] Frontend user page
- [ ] Search functionality
- [ ] K2 Content module
- [ ] System plugin loading
- [ ] SEF URLs with new router
- [ ] Multi-language sites
- [ ] Template overrides

## Author

Russell English - Storm Web Design Ltd.
https://stormwebdesign.co.uk
