### v3
## New Features
* Access to local part and domain part from EmailParser
* Extended validations will be added in its own folder

## Breacking changes
* PHP version upgrades using new language features. PHP version requirements will be upgraded via MINOR (3.x) version upgrades.
* DNSCheckValidation now fails for missing MX records. While the RFC allows for A records to be valid, for the purpose of this library, they will be considered invalid.
* Emails domain part are now intenteded to be RFC 1030 compliant, rendering previous valid emails (e.g example@examp&) invalid

