jqGrid Widget for Yii2
========================
Yii2 wrapper for a powerful ajax-enabled grid [jqGrid](http://www.trirand.com/blog/) jQuery plugin.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require "himiklab/yii2-jqgrid-widget" "*"
```

or add

```json
"himiklab/yii2-jqgrid-widget" : "*"
```

to the require section of your application's `composer.json` file.

* Add action in the controller (optional):

```php
use himiklab\jqgrid\JqGridAction;

public function actions()
{
    return [
        'jqgrid' => [
            'class' => JqGridAction::className(),
            'model' => Page::className(),
            'columns' => ['title', 'author', 'language']
        ],
    ];
}
```

* In view:

```php
use himiklab\jqgrid\JqGridWidget;

<?= JqGridWidget::widget([
    'gridSettings' => [
        'colNames' => ['Title', 'Author', 'Language'],
        'colModel' => [
            ['name' => 'title', 'index' => 'title', 'editable' => true],
            ['name' => 'author', 'index' => 'author', 'editable' => true],
            ['name' => 'language', 'index' => 'language', 'editable' => true]
        ],
        'rowNum' => 15,
        'autowidth' => true,
        'height' => 'auto',
    ],
    'pagerSettings' => [
        'edit' => true,
        'add' => true,
        'del' => true,
        'search' => ['multipleSearch' => true]
    ],
    'enableFilterToolbar' => true
]); ?>
```
