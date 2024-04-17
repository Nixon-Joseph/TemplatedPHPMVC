# Templated PHP MVC
Awesome MVC framework modeled after .NET's razor.

Uses Liquid for front end template parsing, and dapper-like db access repositories.

For quick startup template, or test project, see the Demo.

Available in packagist via composer: composer require devpirates/templated-php-mvc

We highly recommend these email templates for your system's emails.

### Column Name
- [ ] Output Cache dependencies
  - [ ] Automatically break cache based on write time of files
    - [ ] Potentially look at db and/or redis integration
- [ ] Have controller methods actually return - rather than echoing - and let the app process the return
