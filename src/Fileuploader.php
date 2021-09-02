<?php
// +----------------------------------------------------------------------
// | Fileuploader
// +----------------------------------------------------------------------
// | User: Lengnuan <25314666@qq.com>
// +----------------------------------------------------------------------
// | Date: 2021年08月31日
// +----------------------------------------------------------------------

namespace lengnuan\fileuploader;

use yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class Fileuploader extends InputWidget
{
    private $id;

    // theme : default or dragdropbox
    public $theme;

    public $domain;

    private $data = [];

    public $accept = 'image/*';

    public $clientOptions = [];

    public function run()
    {
        $html         = '';
        $this->domain = sprintf('%s/', rtrim($this->domain ?: Yii::$app->request->getHostInfo(), '/'));
        if ($this->hasModel()) {
            $model      = $this->model;
            $attribute  = $this->attribute;
            $this->id   = Html::getInputId($model, $attribute);
            $this->name = sprintf('%s[]', Html::getInputName($model, $attribute));
            $this->data = $this->fileInfo(Html::getAttributeValue($this->model, $this->attribute));
            $html = Html::fileInput('files[]', null, ['id' => $this->id, 'multiple' => 'multiple']);
            $html .= $this->renderInput($model, $attribute);
            $this->registerClientScript();
        }
        return $html;
    }

    /**
     * @param $model
     * @param $attribute
     * @return string
     */
    public function renderInput($model, $attribute)
    {
        $html = '';
        foreach ($this->data  as $key => $file) {
            $html .= Html::hiddenInput($this->name, str_replace($this->domain, '', $file['file']), ['class' => 'form-control', sprintf('%s-id', $this->id) => $key]);
        }
        return $html;
    }

    public function registerClientScript()
    {
        $_view = $this->getView();
        $cofig = $this->clientOptionsConfig();

        FileuploaderAsset::register($_view);

        $_view->registerJs("
            $(document).ready(function() {
                $('#$this->id').filer($cofig);
		$('.jFiler-item-thumb-image').chromaGallery({
			color:'#000',
			gridMargin:15,
			maxColumns:5,
			dof:true,
			screenOpacity:0.8
		});
            });
        ");
    }

    /**
     * @param array $fiels
     * @return array
     */
    private function fileInfo($fiels = [])
    {
        $data = [];
        if ($fiels) {
            foreach ($fiels as $k => $file) {
                $data[$k]['name'] = pathinfo(parse_url($file)['path'])['basename'];
                $data[$k]['size'] = 0;
                $data[$k]['type'] = $this->accept;
                $data[$k]['file'] = sprintf('%s%s',  $this->domain, $file);
            }
        }
        return $data;
    }

    /**
     * @return array|string
     */
    private function clientOptionsConfig()
    {
        $config = [];
        $clientOptions = $this->clientOptions;
        // 指定jQuery.filer的主题
        $config['theme']      = 'default';
        // 已经上传的文件
        $config['files']      = $this->data;
        // 允许选择多个文件
        $config['addMore']    = false;
        // 显示文件预览
        $config['showThumbs'] = true;
        // 最大上传文件的数量
        $config['limit']      = $clientOptions['limit'] ?: 1;
        // 上传文件的最大尺寸，单位MB
        $config['maxSize']    = $clientOptions['maxSize'] ?: 100;
        // 可上传的文件的文件扩展名
//        $config['extensions'] = is_array($clientOptions['extensions']) ? $clientOptions['extensions'] : ['jpg', 'jpeg', 'png', 'gif'];
        // 指定文件预览的模板
        $config['templates']  = $this->templates($this->theme);
        // 启用即时文件上传功能
        $config['uploadFile'] = $clientOptions['uploadUrl'] ? $this->uploadFile($clientOptions['uploadUrl']) : null;
        // 在移除一个文件之后触发
        $config['onRemove']   = $this->onRemove();
        return Json::encode($config);
    }

    /**
     * @param null $url
     * @return array
     */
    private function uploadFile($url = null)
    {
        $upload = [];
        $upload['url']        = $url ?: 'upload_file.php';
        $upload['data']       = null;
        $upload['type']       = 'POST';
        $upload['enctype']    = 'multipart/form-data';
        $upload['beforeSend'] = new JsExpression('function(){}');
        $upload['success']    = $this->uploadSuccess();
        $upload['error']      = new JsExpression("function(el, l, p, o, s, id, data, textStatus, errorThrown){
            var parent = el.find('.jFiler-jProgressBar').parent();
            el.find('.jFiler-jProgressBar').fadeOut('slow', function(){
                $('<div class=\"jFiler-item-others text-error\"><i class=\"icon-jfi-minus-circle\"></i> ' + data.responseText + '</div>').hide().appendTo(parent).fadeIn('slow');    
            });
        }");;
        $upload['statusCode'] = null;
        $upload['onProgress'] = null;
        $upload['onComplete'] = null;
        return $upload;
    }

    /**
     * @return JsExpression
     */
    private function uploadSuccess()
    {
       return new JsExpression("function(data, el, jqXHR){
            var parent = el.find('.jFiler-jProgressBar').parent();
            if (data.error) {
                el.find('.jFiler-jProgressBar').fadeOut('slow', function(){
                    $('<div class=\"jFiler-item-others text-error ml-1\"><i class=\"icon-jfi-minus-circle\"></i> ' + data.error + '</div>').hide().appendTo(parent).fadeIn('slow');    
                });
            } else {
            	setTimeout(function () {
            	    if ('$this->theme' == 'dragdropbox') {
                        el.find('.jFiler-item-title b').attr('title', data['name']).text(data['name']);
                    } else {
                        el.find('.jFiler-item-title').attr('title', data['name']).text(data['name']);
                    }
                    el.find('.jFiler-item-thumb-image img').attr('src', data['url']).attr('data-largesrc', data['url']);
                    $('.jFiler.jFiler-theme-default').after('<input type=\"hidden\" class=\"form-control\" name=\"$this->name\" {$this->id}-id=\"' + el[0].jfiler_id + '\" value=\"' + data['filename'] + '\">');
                    el.find('.jFiler-jProgressBar').fadeOut('slow', function(){
                        $('<div class=\"jFiler-item-others text-success ml-1\"><i class=\"icon-jfi-check-circle\"></i> Success</div>').hide().appendTo(parent).fadeIn('slow');    
                    });    
	            }, 500)       
            }
        }");
    }

    /**
     * @return JsExpression
     */
    private function onRemove()
    {
        return new JsExpression("function(el, file, id, listEl, boxEl, newInputEl, inputEl){
                $('input[{$this->id}-id=\"' + el[0].jfiler_id + '\"]').remove();
            }");
    }

    /**
     * @param string $theme
     * @return array
     */
    private function templates($theme)
    {
        $template = [];
        if ($theme == 'dragdropbox') {
            $template['box']         = '<ul class="jFiler-items-list jFiler-items-grid"></ul>';
            $template['item']        = '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-thumb"><div class="jFiler-item-status"></div><div class="jFiler-item-info"><span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span><span class="jFiler-item-others">{{fi-size2}}</span></div>{{fi-image}}</div><div class="jFiler-item-assets jFiler-row"><ul class="list-inline pull-left"><li>{{fi-progressBar}}</li></ul><ul class="list-inline pull-right"><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></li>';
            $template['itemAppend']  = '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-thumb"><div class="jFiler-item-status"></div><div class="jFiler-item-info"><span class="jFiler-item-title"><b title="{{fi-name}}">{{fi-name | limitTo: 25}}</b></span><span class="jFiler-item-others">{{fi-size2}}</span></div>{{fi-image}}</div><div class="jFiler-item-assets jFiler-row"><ul class="list-inline pull-left"><li><span class="jFiler-item-others">{{fi-icon}}</span></li></ul><ul class="list-inline pull-right"><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></li>';
        } else {
            $template['box']         = '<ul class="jFiler-items-list jFiler-items-default"></ul>';
            $template['item']        = '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-icon pull-left">{{fi-image}}</div><div class="jFiler-item-info pull-left"><div class="jFiler-item-title" title="{{fi-name}}">{{fi-name | limitTo:30}}</div><div class="jFiler-item-others"><span>size: {{fi-size2}}</span><span>type: {{fi-extension}}</span><span class="jFiler-item-status">{{fi-progressBar}}</span></div><div class="jFiler-item-assets"><ul class="list-inline"><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></div></li>';
            $template['itemAppend']  = '<li class="jFiler-item"><div class="jFiler-item-container"><div class="jFiler-item-inner"><div class="jFiler-item-icon pull-left">{{fi-image}}</div><div class="jFiler-item-info pull-left"><div class="jFiler-item-title">{{fi-name | limitTo:35}}</div><div class="jFiler-item-others"><span>size: {{fi-size2}}</span><span>type: {{fi-extension}}</span><span class="jFiler-item-status"></span></div><div class="jFiler-item-assets"><ul class="list-inline"><li><a class="icon-jfi-trash jFiler-item-trash-action"></a></li></ul></div></div></div></div></li>';
        }
        $template['progressBar'] = '<div class="bar"></div>';
        $template['itemAppendToEnd']    = false;
        $template['removeConfirmation'] = false;
        $template['_selectors']['list'] = '.jFiler-items-list';
        $template['_selectors']['item'] = '.jFiler-item';
        $template['_selectors']['progressBar'] = '.bar';
        $template['_selectors']['remove'] = '.jFiler-item-trash-action';
        return $template;
    }
}
