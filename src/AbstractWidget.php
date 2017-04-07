<?php

namespace PrivateIT\widgets\bootstrap;

use yii\base\Action;
use yii\base\ActionEvent;
use yii\base\Model;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\Json;
use yii\web\Application;
use yii\web\AssetBundle;
use yii\web\Response;

abstract class AbstractWidget extends Widget
{
    /**
     * @event ActionEvent an event raised right before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be false to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * @event ActionEvent an event raised right after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var array
     */
    public $options;
    /**
     * @var array
     */
    public $clientOptions = [];
    /**
     * @var AssetBundle
     */
    public $assets;
    /**
     * @var bool
     */
    public $enableWidgetFormData = true;
    /**
     * @var bool
     */
    public $enableAutoInitJS = true;
    /**
     * @var Model[]
     */
    static public $models;
    /**
     * @var integer a counter used to generate [[id]] for widgets.
     * @internal
     */
    public static $counter = 0;
    /**
     * @var string
     */
    private $_id;

    /**
     * @return Model
     */
    static function createModel()
    {
        return new Model();
    }

    /**
     * @param string $widgetId
     * @param Action $action
     * @param Application $app
     * @return bool
     * @throws \yii\base\ExitException
     */
    public function bootstrap($widgetId, $action, $app)
    {
        $this->id = $widgetId;
        if ($this->beforeAction($action)) {

            $requestType = explode('-', $widgetId);
            $requestType = array_shift($requestType);

            if (in_array($requestType, ['ajax', 'json'])) {

                $name = Inflector::camelize(str_replace($requestType . '-', '', $widgetId));
                $method = $requestType . $name;

                if (method_exists($this, $method)) {

                    if ($requestType == 'ajax') {
                        $this->endAjax(
                            call_user_func([$this, $method], $app)
                        );
                    }

                    if ($requestType == 'json') {
                        $this->endJson(
                            call_user_func([$this, $method], $app)
                        );
                    }

                }

            } else {

                $result = false;
                $model = $this->getModel($widgetId);
                if ($model->load($app->request->post())) {
                    if ($model->submit()) {
                        $result = true;
                    }
                }
                return $result;

            }
        }
        return false;
    }

    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * Initializes the view.
     */
    public function init()
    {
        $this->initOptions();
    }

    /**
     * Initializes the view.
     */
    public function initOptions()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        Html::addCssClass($this->options, $this->getWidgetCssClass());
    }

    /**
     * Initializes the view.
     */
    public function initJs()
    {
        $id = $this->options['id'];
        $options = $this->getClientOptions();
        $options = sizeof($options) ? Json::htmlEncode($options) : '';
        $this->getView()->registerJs('jQuery(\'#' . $id . '\').' . $this->getWidgetJsClass() . '(' . $options . ');');
    }

    /**
     * @inheritdoc
     */
    public function getId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_id === null) {
            $this->_id = $this->getWidgetCssClass() . '-' . static::$counter++;
        }

        return $this->_id;
    }

    /**
     * @inheritdoc
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    /**
     * Generate css class
     *
     * @param string $cls
     * @return string
     */
    public function getWidgetJsClass($cls = null)
    {
        if (null === $cls) {
            $cls = get_class($this);
        }
        return Inflector::variablize(basename(str_replace('\\', DIRECTORY_SEPARATOR, $cls)));
    }

    /**
     * Generate css class
     *
     * @param string $cls
     * @return string
     */
    public function getWidgetCssClass($cls = null)
    {
        if (null === $cls) {
            $cls = get_class($this);
        }
        return Inflector::camel2id(basename(str_replace('\\', DIRECTORY_SEPARATOR, $cls)));
    }


    /**
     * Generate css class
     *
     * @param string $cls
     * @return string
     */
    public function getWidgetAssetClass($cls = null)
    {
        if (null === $cls) {
            $cls = get_class($this);
        }
        return $cls . 'Asset';
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if (class_exists($this->getWidgetAssetClass())) {
            $this->assets = call_user_func([$this->getWidgetAssetClass(), 'register'], $this->view);
            if ($this->enableAutoInitJS) {
                if (in_array('js/' . $this->getWidgetCssClass() . '.js', $this->assets->js)) {
                    $this->initJs();
                }
            }
        }
        $content = trim($this->getContent());
        if (!strlen($content)) {
            return null;
        }
        if ($this->enableWidgetFormData) {
            $content = static::addFormData($content);
        }
        return Html::tag('div', $content, $this->options);
    }

    abstract public function getContent();

    public function endAjax($content)
    {
        ob_start();
        ob_implicit_flush(false);
        $view = \Yii::$app->view;
        $view->beginPage();
        $view->head();
        $view->beginBody();
        echo $content;
        $view->endBody();
        $view->endPage(true);
        \Yii::$app->response->data = ob_get_clean();
        \Yii::$app->end();


    }

    public function endJson($content)
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        \Yii::$app->response->data = $content;
        \Yii::$app->end();
    }

    public function addFormData($content)
    {
        if (stristr($content, '<form')) {
            $data = implode([
                Html::hiddenInput('widget[cls]', $this::className()),
                Html::hiddenInput('widget[id]', $this->getId())
            ]);
            $content = preg_replace('~<form[^\>]+>~', '$0' . $data, $content);
        }
        return $content;
    }

    /**
     * Returns the options for the grid view JS widget.
     * @return array the options
     */
    public function getClientOptions()
    {
        return ArrayHelper::merge(
            [
                'widget' => [
                    'id' => $this->getId(),
                    'cls' => get_class($this),
                ]
            ],
            $this->clientOptions
        );
    }

    /**
     * Get base model form
     *
     * @param string|null $widgetId
     * @return Model
     */
    public function getModel($widgetId = null)
    {
        if (null === $widgetId) {
            $widgetId = $this->getId();
        }
        if (!isset(static::$models[$widgetId])) {
            static::$models[$widgetId] = static::createModel();
        }
        return static::$models[$widgetId];
    }
}