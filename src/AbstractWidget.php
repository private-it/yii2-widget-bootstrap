<?php

namespace PrivateIT\widgets\bootstrap;


use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\Application;

abstract class AbstractWidget extends Widget
{
    /**
     * @var array
     */
    public $options;

    /**
     * @param Application $app
     * @param string $widgetId
     */
    static public function bootstrap($app, $widgetId = '0')
    {
    }

    /**
     * Initializes the view.
     */
    public function init()
    {
        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
        Html::addCssClass($this->options, $this->getWidgetCssClass());
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
     * @inheritdoc
     */
    public function run()
    {
        return Html::tag('div', $this->getContent(), $this->options);
    }

    abstract public function getContent();
}