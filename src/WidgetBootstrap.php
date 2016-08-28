<?php

namespace PrivateIT\widgets\bootstrap;

use yii\base\ActionEvent;
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
            $widget = $request->post('widget', $request->get('widget'));
            if ($widget) {
                $cls = ArrayHelper::remove($widget, 'cls');
                if (null !== $cls && class_exists($cls)) {
                    Event::on(
                        Application::className(), Application::EVENT_BEFORE_ACTION,
                        function ($e) use ($cls, $app, $widget) {
                            /** @var ActionEvent $e */
                            /** @var AbstractWidget $widget */
                            $action = clone $e->action;
                            $widget = $cls::begin($widget);
                            $action->id = $widget->id;
                            $widget->bootstrap($widget->id, $action, $app);
                        }
                    );
                }
            }
        }
    }
}