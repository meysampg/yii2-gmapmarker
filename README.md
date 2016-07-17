Yii2 Google Map Marker
======================
A simple google map marker widget for Yii2

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require meysampg/yii2-gmapmarker "*"
```

or add

```
"meysampg/yii2-gmapmarker": "*"
```

to the require section of your `composer.json` file.

Config
------

Easily you can set the asset needed values with as mentioned on http://www.yiiframework.com/doc-2.0/guide-structure-assets.html#customizing-asset-bundles. 
For example You can use this code on you `web.config`:
```php
return [
    // Some codes are here :)
   	'components' => [
   		// And also maybe here ;)
     	'assetManager' => [
     		'bundles' => [
     			'meysampg\gmap\GMapAsset' => [
     				'key' => 'YOU_API_KEY',
     				'language' => 'en'
     			],
     		],
  		],
    ],
];
```

Usage
-----

Once the extension is installed, simply use it in your code by:
```php
<?= GMapMarker::widget([
	'width' => '98', // Using pure number for 98% of width.
	'height' => '400px', // Or use number with unit (In this case 400px for height).
    'marks' => [35.6892, 51.3890],
    'zoom' => 5,
    'disableDefaultUI' => true
]); ?>
```
for single marker or use for multiple markers by:

```php
<?= GMapMarker::widget([
	'width' => '600px',
	'height' => '400px',
    'marks' => [
        [35.6892, 51.3890],
        [31.3183, 48.6706],
        [29.4850, 57.6439]
    ],
    'zoom' => 5,
    'disableDefaultUI' => true
]); ?>
```

Screenshot
----------
![Yii2 Google Map Marker Extension](https://cloud.githubusercontent.com/assets/1416085/16899844/fe3355c8-4c26-11e6-89c6-0f98294b6973.png)

ToDo
----
* Add support of label to makers.
* Add ability of showing custom icon instead of default marker.
* [Need more? Open an issue!]