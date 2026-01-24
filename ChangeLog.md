# CHANGELOG CHTVAEXCHANGERATES FOR [DOLIBARR ERP CRM](https://www.dolibarr.org)

## 1.0.1 - Unreleased

### Changed
- Cleaned up legacy template code (MYOBJECT/MYMODULE references)
- Updated permissions to use translation keys
- Cleaned up language file
- Updated build workflow with release trigger
- Added version bump workflow with PR creation

### Fixed
- Typo in language file (linked vs liked)

## 1.0.0 - 2024

### Added
- Initial version
- Cron job for automatic exchange rate synchronization
- Support for Swiss customs (BAZG) API
- ExchangeRateParser class for XML parsing
- Unit tests for parser class
- Build workflow for module packaging
- Test workflow for PHP syntax and unit tests
