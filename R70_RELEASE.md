# R70 – CRM render + mobile menu production fix

- Fixed `customers` view variable collision: layout Nia briefing no longer overwrites the page `$q` search string with a PDOStatement.
- Fixed `icon()` scope bug: icons are now resolved from a closure instead of `$GLOBALS`.
- Fixed mobile hamburger to use one click handler; no duplicate pointer/click toggling.
- This prevents the fatal `/customers` render error and allows the page JavaScript, including the mobile drawer, to load.
