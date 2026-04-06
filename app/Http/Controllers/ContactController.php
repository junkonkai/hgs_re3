<?php

namespace App\Http\Controllers;

use App\Enums\ContactResponderType;
use App\Enums\ContactStatus;
use App\Enums\DiscordChannel;
use App\Http\Requests\ContactResponseRequest;
use App\Http\Requests\ContactSubmitRequest;
use App\Models\Contact;
use App\Models\ContactResponse;
use App\Services\Discord\DiscordWebhookService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * お問い合わせフォーム表示
     *
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function form(): JsonResponse|Application|Factory|View
    {
        return $this->tree(view('contact.form'));
    }

    /**
     * お問い合わせ送信処理
     *
     * @param ContactSubmitRequest $request
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     * @throws \Throwable
     */
    public function submit(ContactSubmitRequest $request): JsonResponse|Application|Factory|View|RedirectResponse
    {
        $validated = $request->validated();

        // スパム対策：メッセージに全角ひらがなまたは全角カタカナが1文字以上含まれているかチェック
        $message = $validated['message'];
        $hasHiraganaOrKatakana = preg_match('/[\x{3041}-\x{3096}\x{30A1}-\x{30F6}]/u', $message);

        if (!$hasHiraganaOrKatakana) {
            // スパムと判断された場合：DBには登録せず、ダミーのトークンを生成
            $tokenSource = 'Not registered due to spam prevention';
            $token = base64_encode($tokenSource);
            
            // 完了画面で使用するためのダミーオブジェクトを作成
            $contact = new Contact([
                'name' => $validated['name'] ?? 'No Name',
                'category' => $validated['category'] ?? null,
                'message' => $message,
                'status' => ContactStatus::PENDING,
                'token' => $token,
                'created_at' => now(),
            ]);
            // IDがないとエラーになる可能性があるので、ダミーIDを設定
            $contact->id = 0;
        } else {
            // お問い合わせ内容を保存
            $contact = Contact::create([
                'name' => $validated['name'] ?? 'No Name',
                'category' => $validated['category'] ?? null,
                'message' => $validated['message'],
                'status' => ContactStatus::PENDING,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => null,
                'token' => '', // 一旦空で保存
            ]);

            // ID + タイムスタンプからtokenを生成してupdate
            $tokenSource = $contact->id . '_' . $contact->created_at->timestamp;
            $token = hash('sha256', $tokenSource);
            $contact->update(['token' => $token]);

            // Discordに通知
            $adminUrl = route('Admin.Manage.Contact.Show', ['contact' => $contact->id]);
            $category  = $contact->category?->label() ?? 'カテゴリなし';
            $preview   = mb_strimwidth($contact->message, 0, 200, '…');
            $hr = '─────────────────────';
            app(DiscordWebhookService::class)
                ->to(DiscordChannel::Contact)
                ->send("新しいお問い合わせが届きました\n{$hr}\n名前: {$contact->name}\nカテゴリ: {$category}\n内容: {$preview}\n管理画面: {$adminUrl}\n{$hr}");
        }

        $url = route('Contact.Show', ['token' => $contact->token]);

        // 完了画面を表示
        return $this->tree(view('contact.complete', compact('contact')), options: ['url' => $url]);
    }

    /**
     * お問い合わせ内容表示
     *
     * @param string $token
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function show(string $token): JsonResponse|Application|Factory|View
    {
        // tokenで問い合わせを検索（削除済みは除外される）
        $contact = Contact::where('token', $token)->first();

        // 見つからない場合、または取り消し済み・クローズ済みの場合
        if (!$contact || 
            $contact->status === ContactStatus::CANCELLED || 
            $contact->status === ContactStatus::CLOSED) {
            return $this->tree(view('contact.not_found'));
        }

        // レスポンスを取得（作成日時順）
        $responses = $contact->responses()->orderBy('created_at', 'asc')->get();

        return $this->tree(view('contact.show', compact('contact', 'responses')));
    }

    /**
     * レス投稿処理
     *
     * @param string $token
     * @param ContactResponseRequest $request
     * @return RedirectResponse
     */
    public function storeResponse(string $token, ContactResponseRequest $request): RedirectResponse
    {
        // tokenで問い合わせを検索
        $contact = Contact::where('token', $token)->first();

        // 見つからない場合、または取り消し済み・クローズ済みの場合
        if (!$contact || 
            $contact->status === ContactStatus::CANCELLED || 
            $contact->status === ContactStatus::CLOSED) {
            return redirect()->route('Contact')
                ->with('error', 'この問い合わせには返信できません。');
        }

        $validated = $request->validated();

        // レスポンスを保存
        ContactResponse::create([
            'contact_id' => $contact->id,
            'message' => $validated['message'],
            'responder_type' => ContactResponderType::USER,
            'user_id' => null,
            'responder_name' => $validated['responder_name'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Discordに通知
        $adminUrl = route('Admin.Manage.Contact.Show', ['contact' => $contact->id]);
        $preview  = mb_strimwidth($validated['message'], 0, 200, '…');
        $hr = '─────────────────────';
        app(DiscordWebhookService::class)
            ->to(DiscordChannel::Contact)
            ->send("お問い合わせに返信がありました\n{$hr}\n名前: {$validated['responder_name']}\n内容: {$preview}\n管理画面: {$adminUrl}\n{$hr}");

        return redirect()->route('Contact.Show', ['token' => $token])
            ->with('success', '返信を投稿しました。');
    }

    /**
     * お問い合わせ取り消し処理
     *
     * @param string $token
     * @return RedirectResponse|JsonResponse|Application|Factory|View
     */
    public function cancel(string $token): RedirectResponse|JsonResponse|Application|Factory|View
    {
        // tokenで問い合わせを検索
        $contact = Contact::where('token', $token)->first();

        // 見つからない場合、またはクローズ済みの場合
        if (!$contact || $contact->status === ContactStatus::CLOSED) {
            return $this->tree(view('contact.form'), options: ['url' => route('Contact')]);
        }

        // PENDINGの場合のみ取り消し可能
        if ($contact->status !== ContactStatus::PENDING) {
            return redirect()->route('Contact.Show', ['token' => $token])
                ->with('error', '取り消しできるのは未対応の問い合わせのみです。');
        }

        // ステータスを取り消しに変更
        $contact->update(['status' => ContactStatus::CANCELLED]);

        // Discordに通知
        $adminUrl = route('Admin.Manage.Contact.Show', ['contact' => $contact->id]);
        $hr = '─────────────────────';
        app(DiscordWebhookService::class)
            ->to(DiscordChannel::Contact)
            ->send("お問い合わせが取り消されました\n{$hr}\n名前: {$contact->name}\n管理画面: {$adminUrl}\n{$hr}");

        return $this->tree(view('contact.form'), options: ['url' => route('Contact')]);
    }
}
