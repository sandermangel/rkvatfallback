# Rkvatfallback

Rkvatfallback adds extra check services to the VAT check of Magento by extending the Customer Helper.

# Supported services

- Built in Magento VIES check
- Custom VIES check
- vatlayer.com check
- Regex fallback check for following countries; AT, BE, CZ, DE, CY, DK, EE, GR, ES, FI, FR, GB, HU, IE, IT, LT, LU, LV, MT, NL, PL, PT, SE, SI, SK

# Compatibility

Tested on Mage CE 1.7.x, 1.8.x & 1.9.x

# Changelog
[0.3.0] Moved source files to src, added unit test in tests, moved shell script to magerun command, regex fix for ES and GB, added EL (Greece) country code

[0.2.1] Fixed BE regex

[0.2.0] Removed defunct Appspot check, added vatlayer, improved code quality, added regex check as fallback, updated readme, made built in Magento VIES check optional

[0.1.0] Added the EU VIES site and http://isvat.appspot.com/ as fallback APIs


# Disclaimer

Warning: Since all of the free VIES API's are slow and somewhat unreliable the checkout steps could become slow while checking.

# Authors

- Sander Mangel @sandermagenl
- Peter Jaap Blaakmeer @peterjaap
