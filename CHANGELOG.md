# K2 for Joomla 5 - Changelog

## v3.0.0 - March 2026

**Complete Joomla 5 Migration**

This release represents a complete rewrite of K2 for Joomla 5, modernizing the entire codebase while maintaining backwards compatibility for template overrides and data.

### Major Changes

- **PHP 8.1+ Required** - Modern PHP syntax with typed properties
- **Joomla 5 Only** - Removed support for Joomla 3.x and 4.x
- **Namespaced Classes** - All classes now use PSR-4 namespaces
- **Service Providers** - Modern dependency injection throughout
- **New Router** - Migrated to Joomla 5's RouterView pattern
- **Event Subscribers** - Plugins use SubscriberInterface pattern
- **Web Asset Manager** - Frontend assets use Joomla's WAM

### Component Changes

- Migrated all admin controllers to `Joomla\Component\K2\Administrator\Controller\*`
- Migrated all admin models to `Joomla\Component\K2\Administrator\Model\*`
- Migrated all admin views to `Joomla\Component\K2\Administrator\View\*`
- Migrated all table classes to `Joomla\Component\K2\Administrator\Table\*`
- Migrated site controllers to `Joomla\Component\K2\Site\Controller\*`
- Migrated site models to `Joomla\Component\K2\Site\Model\*`
- Migrated site views to `Joomla\Component\K2\Site\View\*`
- Added `K2Component` extension class with proper interfaces
- Added service provider for component registration

### Plugin Changes

- System plugin migrated to `Joomla\Plugin\System\K2\Extension\K2Plugin`
- Uses `SubscriberInterface` for event subscription
- Added proper service provider
- Removed JoomFish integration (use Joomla's native multilanguage)

### Module Changes

- K2 Content module migrated to Joomla 5 dispatcher pattern
- Uses `HelperFactoryAwareInterface` for helper access
- Modern XML manifest with proper namespace declaration

### Database Changes

- All queries use parameter binding with `ParameterType` constants
- Proper use of `quoteName()` and `quote()` methods
- Named parameters throughout

### Removed

- `jimport()` calls - using native autoloading
- `JRequest` - using `$app->input`
- `JFactory` static calls - using Factory and container
- `JDispatcher` - using event subscribers
- `JError` - using PHP exceptions
- K2_JVERSION branching for Joomla 1.5/2.5/3.x
- JoomFish integration

### Compatibility

- Template overrides remain compatible
- K2 plugin events unchanged
- Database schema unchanged
- Media file structure unchanged

---

## Previous Versions

For changelog entries prior to v3.0.0 (K2 2.x for Joomla 3/4), please refer to the original K2 project:
https://github.com/getk2/k2/blob/master/CHANGELOG.md
