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
        Html::addCssClass($this->options, Inflector::camel2id(basename(__CLASS__)));
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