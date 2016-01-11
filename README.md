jqGrid Widget for Yii2
========================
Yii2 wrapper for a powerful ajax-enabled grid [free jqGrid](https://github.com/free-jqgrid/jqGrid) jQuery plugin.

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
            'scope' => function ($query) {
                /** @var \yii\db\ActiveQuery $query */
                $query->select(['title', 'author', 'language']);
            },
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
    'enableFilterToolbar' => true,
]) ?>
```

* Advanced usage

option 'requestId' => $model->id
will add GET parameter in URL /index.php?r=myurl&action=request&id=XXXX

option 'editurl' => 'clientArray'
will disable posting of data from grid to your server
use JS handlers as written below to get data

first row in colModel 'Edit Actions' will provide you inline editing and deleting of grid data

option 'inlineNav' => true,
will disable modal windows for adding and editing grid data and will provide you inline data management

```php
use himiklab\jqgrid\JqGridWidget;
use yii\helpers\Url;

<?= JqGridWidget::widget([
    'requestUrl' => Url::to('jqgrid'),
    'requestId' => $model->id,
    'gridSettings' => [
        'editurl' => 'clientArray',
        'datatype' => 'json',
        'colNames' => ['Actions', 'Title', 'Author', 'Language'],
        'colModel' => [
            [
                'label' => 'Edit Actions',
                'name' => 'actions',
                'width' => 100,
                'formatter' => 'actions',
                'formatoptions' => [
                    'keys' => true,
                    'editOptions' => [],
                    'addOptions' => [],
                    'delOptions' => []
                ]
            ],
            ['name' => 'title', 'index' => 'title', 'editable' => true],
            ['name' => 'author', 'index' => 'author', 'editable' => true],
            ['name' => 'language', 'index' => 'language', 'editable' => true]
        ],
        'rowNum' => 15,
        'autowidth' => true,
        'height' => 'auto',
        'loadonce' => true,
    ],
    'pagerSettings' => [
        'edit' => true,
        'add' => true,
        'del' => true,
        'search' => false
    ],
    'inlineNav' => true,
    'enableFilterToolbar' => false
]); ?>
```

* use the following PHP code in your view to get data as serialized object before form will be submitted

```php
   $js = <<<JS
   $('body').on('beforeSubmit', 'form#{$model->formName()}', function () {
       var form = $(this);
       if (form.find('.has-error').length) {
           return false;
       }

       var dataIds = jQuery("#jqGrid-w0").getDataIDs();
       var data = Array();
       for (i = 0; i < dataIds.length; i++)
       {
           data[i] = jQuery("#jqGrid-w0").getRowData(dataIds[i]);
       }

       // set result to some hidden input to send it to controller via standart ActiveForm component
       document.getElementById('hidden_input_id').value = JSON.stringify(data);

       return true; // form gets submitted
       //return false; // form does not get submitted

   });
   JS;

   $this->registerJs($js);
```