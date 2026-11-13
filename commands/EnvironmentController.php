<?php
/**
 * 用途：只读检查 PHP / Yii / MySQL 实际版本及连接字符集、时区。
 * 作者：Clash727（W01）
 */

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class EnvironmentController extends Controller
{
    public function actionCheck(): int
    {
        $this->stdout('PHP ' . PHP_VERSION . PHP_EOL);
        $this->stdout('Yii ' . Yii::getVersion() . PHP_EOL);
        foreach (['ctype', 'mbstring', 'openssl', 'pdo_mysql'] as $extension) {
            $this->stdout($extension . ': ' . (extension_loaded($extension) ? 'enabled' : 'missing') . PHP_EOL);
        }

        $server = Yii::$app->db->createCommand(
            'SELECT VERSION() AS version, DATABASE() AS db_name, '
            . '@@session.time_zone AS time_zone, @@character_set_connection AS charset, '
            . '@@collation_database AS db_collation'
        )->queryOne();

        $this->stdout('MySQL ' . $server['version'] . PHP_EOL);
        $this->stdout('Database: ' . $server['db_name'] . PHP_EOL);
        $this->stdout('Connection charset: ' . $server['charset'] . PHP_EOL);
        $this->stdout('Database collation: ' . $server['db_collation'] . PHP_EOL);
        $this->stdout('MySQL session timezone: ' . $server['time_zone'] . PHP_EOL);
        $this->stdout('PHP / Yii timezone: ' . date_default_timezone_get() . ' / ' . Yii::$app->timeZone . PHP_EOL);

        return ExitCode::OK;
    }
}
