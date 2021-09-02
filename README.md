yii2-fileuploader
=================
yii2, fileuploader, jQuery, filer

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist lengnuan-v/yii2-fileuploader "dev-master"
```

or add

```
"lengnuan-v/yii2-fileuploader": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
    <?= $form->field($model, 'files')->widget(\lengnuan\fileuploader\Fileuploader::className(), [
            'clientOptions' => [
                'uploadUrl' => Url::to(['file']),
            ]
    ]);?>```