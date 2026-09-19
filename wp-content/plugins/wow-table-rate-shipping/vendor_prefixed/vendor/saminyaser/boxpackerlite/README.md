BoxPackerLite
=========

BoxPackerLite is a compact PHP library for solving the "4D" bin packing/knapsack problem (3D dimensions plus weight).
Given a list of items and available box sizes, it determines how many boxes are required and how items should be
packed while respecting physical dimensions and weight limits.

This is especially useful for e-commerce and logistics workflows, such as selecting the right parcel sizes for shipping
rates or estimating the number of labels to print.

Requirements
------------
- **PHP 7.4 or higher**
- ext-json

The library is designed to work seamlessly in **WordPress environments** (5.6+) and includes built-in WordPress detection
and logging integration through `error_log()`.

Installation
------------
```bash
composer require saminyaser/boxpackerlite
```

Features
--------
- 3D bin packing algorithm with weight constraints
- Automatic box rotation and item orientation optimization
- Support for items that must be kept flat
- Weight redistribution across multiple boxes
- Layer stability calculation
- Custom sorting strategies for boxes and items
- WordPress-compatible logging
- Zero external dependencies (except ext-json)
- PHP 7.4+ typed properties and modern syntax

Credit
------
This project is based on the original BoxPacker library by Doug Wright (dvdoug/boxpacker). This lite version removes
tests, docs, and logging dependencies to keep the package minimal while preserving core packing behavior.

License
-------
BoxPackerLite is MIT-licensed. 
