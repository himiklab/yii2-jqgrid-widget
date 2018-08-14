jqGrid Widget for Yii2
========================
Yii2 wrapper for a powerful ajax-enabled grid [free jqGrid](https://github.com/free-jqgrid/jqGrid) jQuery plugin.

[![Packagist](https://img.shields.io/packagist/dt/himiklab/yii2-jqgrid-widget.svg)]() [![Packagist](https://img.shields.io/packagist/v/himiklab/yii2-jqgrid-widget.svg)]()  [![license](https://img.shields.io/badge/License-MIT-yellow.svg)]()

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require --prefer-dist "himiklab/yii2-jqgrid-widget" "*"
```

or add

```json
"himiklab/yii2-jqgrid-widget" : "*"
```

to the require section of your application's `composer.json` file.

* Add action in the controller (optional), for example:

```php
use himiklab\jqgrid\actions\JqGridActiveAction;

public function actions()
{
    return [
        'jqgrid' => [
            'class' => JqGridActiveAction::className(),
            'model' => Page::className(),
        ],
    ];
}
```

* View's example:

```php
use himiklab\jqgrid\JqGridWidget;
use yii\helpers\Url;

<?= JqGridWidget::widget([
    'requestUrl' => Url::to('jqgrid'),
    'gridSettings' => [
        'colNames' => ['ID', 'Title', 'Author', 'Language'],
        'colModel' => [
            [
                'name' => 'id', 'index' => 'id',
                'formatter' => 'integer',
                'searchoptions' => [
                    'searchhidden' => true,
                    'sopt' => ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'nu', 'nn'],
                ],
                'hidden' => false, 'editable' => false
            ],
            [
                'name' => 'title', 'index' => 'title',
                'searchoptions' => [
                    'searchhidden' => true,
                    'sopt' => ['cn', 'nc', 'bw', 'bn', 'eq', 'ne', 'ew', 'en', 'nu', 'nn'],
                ],
                'hidden' => false, 'editable' => true
            ],
            [
                'name' => 'author', 'index' => 'author',
                'searchoptions' => [
                    'searchhidden' => true,
                    'sopt' => ['cn', 'nc', 'bw', 'bn', 'eq', 'ne', 'ew', 'en', 'nu', 'nn'],
                ],
                'hidden' => false, 'editable' => true
            ],
            [
                'name' => 'language', 'index' => 'language',
                'formatter' => 'select',
                'stype' => 'select',
                'edittype' => 'select',
                'searchoptions' => [
                    'clearSearch' => false,
                    'searchhidden' => true,
                    'value' => ':;en:English;ru:Русский;cn:汉语',
                    'sopt' => ['eq', 'ne', 'nu', 'nn'],
                ],
                'editoptions' => ['value' => [
                    'en' => 'English',
                    'ru' => 'Русский',
                    'cn' => '汉语',
                ]],
                'hidden' => false, 'editable' => true
            ],
        ],
        'rowNum' => 30,
        'rowList' => [30, 60, 90],
        'autowidth' => true,
        'multiselect' => true,
        'multiSort' => true,
        'rownumbers' => true,
        'viewrecords' => true,
        'cmTemplate' => ['autoResizable' => true],
        'autoresizeOnLoad' => true,
    ],
    'pagerSettings' => [
        'edit' => true,
        'add' => true,
        'del' => true,
        'search' => [
            'multipleSearch' => true,
            'multipleGroup' => true,
            'closeAfterSearch' => true,
            'showQuery' => true,
        ]
    ],
    'enableFilterToolbar' => true,
    'enableColumnChooser' => true,
    'filterToolbarSettings' => [
        'stringResult' => true,
    ],
]) ?>
```

or

```php
use app\models\Page;
use himiklab\jqgrid\JqGridHelper;
use himiklab\jqgrid\JqGridWidget;
use yii\helpers\Url;

<?php
$columns = [
    'id' => ['type' => 'integer',],
    'title',
    'author',
    'language' => [
        'type' => 'list',
        'data' => Page::getAllLanguages(),
    ],
    'visible' => ['type' => 'boolean',],
];

$columnsIsVisible = ['id', 'title', 'author', 'language', 'visible',];
$columnsIsEditable = ['title', 'author', 'language', 'visible',];

echo JqGridWidget::widget([
    'requestUrl' => Url::to('jqgrid'),
    'gridSettings' => [
        'colNames' => ['ID', 'Title', 'Author', 'Language', 'Visible'],
        'colModel' => JqGridHelper::jqgridColModel($columns, $columnsIsVisible, $columnsIsEditable),
        'rowNum' => 30,
        'rowList' => [30, 60, 90],
        'autowidth' => true,
        'multiselect' => true,
        'multiSort' => true,
        'rownumbers' => true,
        'viewrecords' => true,
        'cmTemplate' => ['autoResizable' => true],
        'autoresizeOnLoad' => true,
    ],
    'pagerSettings' => [
        'edit' => true,
        'add' => true,
        'del' => true,
        'search' => [
            'multipleSearch' => true,
            'multipleGroup' => true,
            'closeAfterSearch' => true,
            'showQuery' => true,
        ]
    ],
    'enableFilterToolbar' => true,
    'enableColumnChooser' => true,
    'filterToolbarSettings' => [
        'stringResult' => true,
    ],
]) ?>
```
