<?php
/**
 * Serves coursework attachments from the two public coursework directories.
 */

namespace app\controllers;

use FilesystemIterator;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DownloadController extends Controller
{
    private const SECTIONS = [
        'team' => ['团队作业', ['pdf', 'doc', 'docx', 'ppt', 'pptx']],
        'personal' => ['个人作业', ['zip']],
    ];

    public function behaviors(): array
    {
        return ['verbs' => [
            'class' => VerbFilter::class,
            'actions' => ['index' => ['GET'], 'file' => ['GET']],
        ]];
    }

    public function actionIndex(): string
    {
        $sections = [];
        foreach (self::SECTIONS as $category => [$label, $extensions]) {
            $sections[$category] = [$label, array_keys($this->files($category, $extensions))];
        }

        return $this->render('index', ['sections' => $sections]);
    }

    public function actionFile(string $category, string $filename): Response
    {
        $section = self::SECTIONS[$category] ?? null;
        $path = $section === null ? null : ($this->files($category, $section[1])[$filename] ?? null);
        if ($path === null) {
            throw new NotFoundHttpException('没有找到这份作业。');
        }

        return Yii::$app->response->sendFile($path, $filename);
    }

    /** @return array<string, string> Actual file names mapped to their canonical paths. */
    private function files(string $category, array $extensions): array
    {
        $directory = realpath(Yii::getAlias('@app')) . DIRECTORY_SEPARATOR
            . 'data' . DIRECTORY_SEPARATOR . $category;
        if (!is_dir($directory) || realpath($directory) !== $directory) {
            return [];
        }

        $files = [];
        foreach (new FilesystemIterator($directory) as $entry) {
            $name = $entry->getFilename();
            if ($name[0] === '.' || str_contains($name, '..') || strpbrk($name, "/\\%\r\n") !== false
                || !in_array(strtolower($entry->getExtension()), $extensions, true)
                || $entry->isLink() || !$entry->isFile() || !$entry->isReadable()) {
                continue;
            }

            $path = $entry->getRealPath();
            if ($path !== false && dirname($path) === $directory) {
                $files[$name] = $path;
            }
        }

        ksort($files, SORT_NATURAL);
        return $files;
    }
}
