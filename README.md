<h1 align="center">Sherlockode Sylius promotion plugin</h1>

This plugin add : 
- Threshold promotion
- Free product promotion
## Installation

1. require the bundle with Composer:

```bash
$ composer require sherlockode/sylius-promotion-plugin
```

2. enable the bundle :

```php
<?php

# config/bundles.php

return [
    // ...
    Sherlockode\SyliusPromotionPlugin\SherlockodeSyliusPromotionPlugin::class => ['all' => true],
    // ...
];
```

Now in you admin panel you have 2 new promotions actions : 
- Threshold promotion
- Free product promotion
