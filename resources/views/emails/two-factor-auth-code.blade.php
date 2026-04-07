<x-mail::message>
# ログイン認証コードのご案内

{{ config('app.name') }}へのログイン認証コードをお送りします。

以下の6桁のコードをログイン画面に入力してください。

<x-mail::panel>
## {{ $code }}
</x-mail::panel>

**このコードの有効期限は15分です。**

このメールに心当たりがない場合は、このメールを無視してください。
何度もメールが送られてくる場合は、当サイトの問い合わせフォームよりご連絡ください。

Thanks,<br>
{{ config('app.name') }}<br>
<a href="https://horrorgame.net/">https://horrorgame.net/</a>
</x-mail::message>
