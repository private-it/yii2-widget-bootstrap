<?php

namespace PrivateIT\widgets\bootstrap;

use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\web\Application;

class WidgetBootstrap implements BootstrapInterface
{
    /**
     * @param \yii\base\Application $app
     */
    public function bootstrap($app)
    {
        if ($app instanceof Application) {
            $request = $app->request;
            if ($request->isPost) {
                $widget = $request->post('widget');
                if ($widget) {
                    $cls = ArrayHelper::getValue($widget, 'cls');
                    $widgetId = ArrayHelper::getValue($widget, 'id');
                    Event::on(
                        Application::className(), Application::EVENT_BEFORE_REQUEST,
                        function () use ($cls, $app, $widgetId) {
                            call_user_func_array(
                                [$cls, 'bootstrap'],
                                [$app, $widgetId]
                            );
                        }
                    );
                }
            }
        }
    }
}