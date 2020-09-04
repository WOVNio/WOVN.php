# Wovn PHP Core

Wovn PHP core is a library that contains the core functionalities of WOVN.io server-side libraries for PHP.

## Motivations

Wovn PHP Core is a complete rewrite of the code that powers WOVN.php, with the goal to provide a foundation that can be shared between WOVN.php and WordPress Plugin. 

### Object-oriented

The library makes an effort to organize its code in an object-oriented fashion. Apparent visual changes to the codebase include:

- elimination of static functions
- inherentence and abstract classes
- strict visibility settings on functions and variables

### Fail Fast

The library makes extensive use of exceptions to communicate errors, and does not make any effort to handle exceptions that would require behavior-altering fallback values to be used.

## File Structure

- `WovnAPICaller.php` The class that handles the API calls to `htmlswapper`.
- `WovnHTMLHandler.php` The class that handles updating HTML.
- `WovnLang.php` The class that represents a single language, together with contextual information related to the language. You should never directly interact with this class.
- `WovnLangDirectory.php` A factory class for `WovnLang`, also contains language definitions for languages. 
- `WovnOptions.php` The class that represents user configurations and settings, with built-in data validation.
- `WovnRequest.php` The entry point of Wovn PHP Core.
- `WovnURL.php` The class that represents URLs, contains all URL related operations.

