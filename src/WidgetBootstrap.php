<?php

namespace PrivateIT\widgets\bootstrap;

use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;
use yii\web\Application;

class WidgetBootstrap implements BootstrapInterface
{
    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app) {
        if ($app instanceof Application) {
            $request = $app->request;
            if ($request->isPost) {
                $widget = $request->post('widget');
                if ($widget) {
                    $cls = ArrayHelper::getValue($widget, 'cls');
                    $widgetId = ArrayHelper::getValue($widget, 'id');
                    call_user_func([$cls, 'bootstrap'], $app, $widgetId);
                }
            }
        }
    }
}