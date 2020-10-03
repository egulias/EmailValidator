### v3
## New Features
* Access to local part and domain part from EmailParser
* Extended validations (TO-DO)

## Breacking changes
* PHP version upgrades using new language features
* DNSCheckValidation now fails for missing MX records (TO-DO)
* Emails domain part are now intenteded to be RFC 1030 compliant, rendering previous valid emails (e.g example@examp&) invalid

