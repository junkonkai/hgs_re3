<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;

abstract class Controller
{
    protected bool $isOver18 = false;

    /**
     * コンストラクタ
     *
     * @return void
     */
    public function __construct()
    {
        if (App::environment('local')) {
            //Auth::guard('admin')->attempt(['email' => 'webmaster@horragame.net', 'password' => 'huckle'], true);
        }

        // 現在のURLにクエリ文字列でover18=1があったら、cookieにover18=1をセットする
        if (request()->query('over18', 0) == 1) {
            Cookie::queue('is_over_18', 1, 60 * 60 * 24 * 30);
            $this->isOver18 = true;
        } else {
            // cookieからis_over_18を取得
            $this->isOver18 = intval(Cookie::get('is_over_18', 0)) === 1;
        }
    }
    
    /**
     * Ajaxリクエストかどうかを判定する
     *
     * @return bool
     */
    private static function isAjax(): bool
    {
        return request()->ajax() || (request()->query('a', 0) == 1);
    }

    /**
     * nodes HTML から最初の section.node 1 個分の HTML を抽出する（internal_node 用）
     *
     * @param string $nodesHtml
     * @return string
     */
    private static function extractFirstNodeSection(string $nodesHtml): string
    {
        if ($nodesHtml === '') {
            return '';
        }
        $len = strlen($nodesHtml);
        $pos = 0;
        while (($pos = stripos($nodesHtml, '<section', $pos)) !== false) {
            $tagEnd = strpos($nodesHtml, '>', $pos);
            if ($tagEnd === false || $tagEnd - $pos > 200) {
                $pos++;
                continue;
            }
            $tag = substr($nodesHtml, $pos, $tagEnd - $pos + 1);
            if (preg_match('/\bclass\s*=\s*["\']([^"\']*)["\']/', $tag, $m) && preg_match('/\bnode\b/', $m[1])) {
                $depth = 1;
                $i = $tagEnd + 1;
                while ($i < $len && $depth > 0) {
                    $nextOpen = stripos($nodesHtml, '<section', $i);
                    $nextClose = stripos($nodesHtml, '</section>', $i);
                    if ($nextClose === false) {
                        break;
                    }
                    if ($nextOpen !== false && $nextOpen < $nextClose) {
                        $depth++;
                        $i = $nextOpen + 8;
                    } else {
                        $depth--;
                        if ($depth === 0) {
                            return substr($nodesHtml, $pos, $nextClose + strlen('</section>') - $pos);
                        }
                        $i = $nextClose + 10;
                    }
                }
                break;
            }
            $pos++;
        }
        return '';
    }

    /**
     * ツリーの生成
     *
     * @param View $view
     * @param array $options
     * @return JsonResponse|View
     * @throws \Throwable
     */
    protected function tree(View $view, array $options = []): JsonResponse|View
    {
        $view->with('isOver18', $this->isOver18)
             ->with('components', $options['components'] ?? []);

        if ($options['ratingCheck'] ?? false) {
            if (!$this->isOver18) {
                $view = $this->ratingCheck(request()->fullUrl());
            }
        }

        // javascriptのFetch APIでアクセスされていたら、layoutを使わずにテキストを返す
        if (self::isAjax()) {
            $viewData = $view->getData();
            $rendered = $view->renderSections();
            $nodes = $rendered['nodes'] ?? '';

            $updateType = 'full';
            if (request()->query('internal_node', 0) == 1) {
                $updateType = 'node';
            } elseif (request()->query('children_only', 0) == 1) {
                $updateType = 'children';
            }

            $json = [
                'updateType'         => $updateType,
                'title'              => $rendered['title'],
                'currentNodeTitle'   => $rendered['current-node-title'],
                'currentNodeContent' => $rendered['current-node-content'] ?? '',
                'nodes'              => $nodes,
                'popup'              => $rendered['popup'] ?? '',
                'url'                => $options['url'] ?? '',
                'colorState'         => $viewData['colorState'] ?? '',
                'components'         => $options['components'] ?? [],
                'csrfToken'          => $options['csrfToken'] ?? '',
            ];
            if ($updateType === 'node') {
                $json['internalNodeHtml'] = self::extractFirstNodeSection($nodes);
                $json['targetNodeId'] = request()->query('source_node_id', '');
            }
            if ($updateType === 'children') {
                $json['currentChildrenHtml'] = $nodes;
            }
            return response()->json($json);
        }

        return $view->with('viewerType', 'tree');
    }

    private function ratingCheck(string $currentUrl): View
    {
        // URLを分解
        $parsedUrl = parse_url($currentUrl);
        
        // クエリパラメータを配列に変換
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        
        // a パラメータを除外し、over18=1 を追加
        unset($queryParams['a']);
        $queryParams['over18'] = 1;
        
        // URLを再構築
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $path = $parsedUrl['path'] ?? '';
        $query = http_build_query($queryParams);
        
        $currentUrl = "{$scheme}://{$host}{$port}{$path}?{$query}";

        return view('rating_check', compact('currentUrl'));
    }

    /**
     * グローバル例外処理（staticメソッド）
     *
     * @param Throwable $e
     * @param Request $request
     * @return JsonResponse|View|Response|null
     */
    public static function handleGlobalException(Throwable $e, Request $request): JsonResponse|View|Response|null
    {
        // APP_URL/admin配下の場合はLaravelのデフォルト処理に委譲
        $appUrl = rtrim(config('app.url'), '/');
        $adminUrl = $appUrl . '/admin';
        if (str_starts_with($request->url(), $adminUrl)) {
            return null;
        }

        // ValidationExceptionの場合はLaravelのデフォルト処理に委譲（リダイレクトしてエラーメッセージを表示）
        if ($e instanceof ValidationException) {
            return null;
        }

        // 例外の種類に応じてステータスコードとビューを決定
        $statusCode = 500;
        $viewName = 'errors.500';
        
        // HTTPステータスコードの判定
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
        } elseif ($e instanceof AuthenticationException) {
            $statusCode = 401;
        } elseif ($e instanceof AuthorizationException) {
            $statusCode = 403;
        } elseif ($e instanceof ModelNotFoundException) {
            $statusCode = 404;
        } elseif ($e instanceof TokenMismatchException) {
            $statusCode = 419;
        }

        // ビュー名の決定（対応するビューが存在する場合）
        $viewName = "errors.{$statusCode}";
        if (!view()->exists($viewName)) {
            // 429エラーの場合は429ビューを使用（存在する場合）
            if ($statusCode === 429 && view()->exists('errors.429')) {
                $viewName = 'errors.429';
            } else {
                $viewName = 'errors.500';
                $statusCode = 500;
            }
        }

        // ログに記録（500系エラーのみ）
        if ($statusCode >= 500) {
            Log::error('Exception occurred', [
                'status_code' => $statusCode,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);
        } else if ($statusCode != 404) {
            // 404を除き400系エラーは警告レベルで記録
            Log::warning('Client error occurred', [
                'status_code' => $statusCode,
                'message' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // デバッグモードの場合は詳細なエラー情報を表示
        if (config('app.debug')) {
            $errorMessage = $e->getMessage();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            $errorTrace = $e->getTraceAsString();
        } else {
            $errorMessage = self::getErrorMessage($statusCode);
            $errorFile = '';
            $errorLine = '';
            $errorTrace = '';
        }

        // Ajaxリクエストかどうかを判定
        $isAjax = $request->ajax() || ($request->query('a', 0) == 1);

        /** @var View $view */
        $view = view($viewName, compact('errorMessage', 'errorFile', 'errorLine', 'errorTrace'))
            ->with('colorState', 'error');

        // Ajaxリクエストの場合はJSONで返す
        if ($isAjax) {
            $rendered = $view->renderSections();
            return response()->json([
                'title'              => $rendered['title'],
                'currentNodeTitle'   => $rendered['current-node-title'],
                'currentNodeContent' => $rendered['current-node-content'] ?? '',
                'nodes'              => $rendered['nodes'],
                'popup'              => $rendered['popup'] ?? '',
                'url'                => '',
                'colorState'         => 'error',
                'statusCode'         => $statusCode,
            ], $statusCode);
        }

        // 通常のリクエストの場合はエラーページを表示
        return response($view, $statusCode);
    }

    /**
     * ステータスコードに応じたエラーメッセージを取得
     *
     * @param int $statusCode
     * @return string
     */
    private static function getErrorMessage(int $statusCode): string
    {
        return match ($statusCode) {
            401 => '認証が必要です。',
            403 => 'アクセス権限がありません。',
            404 => 'ページが見つかりません。',
            419 => 'ページの有効期限が切れました。',
            502 => 'ゲートウェイエラーが発生しました。',
            503 => 'サービスが一時的に利用できません。',
            default => 'システムエラーが発生しました。',
        };
    }

    /**
     * カラーステートを取得
     * セッションにerrorがあればerror、warningがあればwarning、errorsがあればwarning、それ以外は空文字列を返す
     *
     * @return string
     */
    protected function getColorState(): string
    {
        if (session()->has('error')) {
            return 'error';
        }
        if (session()->has('warning')) {
            return 'warning';
        }

        /** @var ViewErrorBag $errors */
        $errors = session('errors');
        if ($errors && $errors->any()) {
            return 'warning';
        }

        return '';
    }
}
