# Changelog
All notable changes to `laravel-dag-manager` will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- Minor documentation update/clean-up.
- ...

## [0.0.7] - 2018-05-18
### Added
- Introduced an Eloquent model trait, `Telkins\Dag\Models\Traits\IsDagManaged` to assist in some basic, helpful functionality for "DAG managed" models.

## [0.0.6] - 2018-05-09
### Fixed
- Corrected migration filename error.  Third time's the charm...?!

## [0.0.5] - 2018-05-09
### Fixed
- Corrected copy/paste filename error related to migration filename and publishing.

## [0.0.4] - 2018-05-09
### Added
- Introduced the ability to publish the migration and updated [README](README.md) accordingly.

### Changed
- Updated the credits section in [README](README.md).

## [0.0.3] - 2018-05-04
### Added
- Introduced some new tests for adding/deleting using different sources.
- Added implicit requirements for Illuminate DB and Support dependencies.
- Update minimum PHP and Laravel requirements to 7.1.3 and 5.6, respectively.

### Changed
- Indexed `dag_edges.source`.

### Fixed
- Fixed a minor section title error in [README](README.md).

## [0.0.2] - 2018-05-03
### Added
- Basic usage section in [README](README.md).
- Installation section content in [README](README.md).
- Warning section in [README](README.md).

## 0.0.1 - 2018-05-03
### Added
- Basic add/remove functionality.
- A set of tests.
- Minimal documentation.

[Unreleased]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.7...HEAD
[0.0.7]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.6...v0.0.7
[0.0.6]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.5...v0.0.6
[0.0.5]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.4...v0.0.5
[0.0.4]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.3...v0.0.4
[0.0.3]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.2...v0.0.3
[0.0.2]: https://github.com/telkins/laravel-dag-manager/compare/v0.0.1...v0.0.2
