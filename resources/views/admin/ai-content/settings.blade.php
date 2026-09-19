@extends('admin.layouts.app')

@section('title', 'تنظیمات AI / تلگرام')
@section('heading', 'تنظیمات AI / تلگرام')

@section('content')
    <div class="card">
        <p class="muted">
            این تنظیمات برای پایپ‌لاین تلگرام → LLM → آفر استفاده می‌شود.
            Endpoint OpenAI-compatible می‌تواند OpenAI، OpenRouter، DeepSeek، Ollama، vLLM،
            یا هر پروکسی LiteLLM باشد.
        </p>

        <form method="POST" action="{{ route('admin.ai-content.settings.update') }}">
            @csrf
            @method('patch')

            <fieldset>
                <legend>🧠 LLM (OpenAI-compatible)</legend>
                <div class="form-grid">
                    <div class="field field--col-12">
                        <label class="field__label" for="llm_base_url">Base URL</label>
                        <input id="llm_base_url" name="llm_base_url" value="{{ $values['llm_base_url'] }}"
                               dir="ltr" placeholder="https://api.openai.com/v1" required>
                        <p class="field__help">مثلا <code>https://api.openai.com/v1</code> یا <code>http://localhost:11434/v1</code> (Ollama)</p>
                    </div>
                    <div class="field field--col-12">
                        <label class="field__label" for="llm_api_key">API Key</label>
                        <input id="llm_api_key" name="llm_api_key" value="{{ $values['llm_api_key'] }}"
                               dir="ltr" placeholder="sk-…" autocomplete="off">
                    </div>
                    <div class="field field--col-6">
                        <label class="field__label" for="llm_model">Model</label>
                        <input id="llm_model" name="llm_model" value="{{ $values['llm_model'] }}"
                               dir="ltr" placeholder="gpt-4o-mini" required>
                    </div>
                    <div class="field field--col-6">
                        <label class="field__label" for="llm_temperature">Temperature</label>
                        <input id="llm_temperature" name="llm_temperature" type="number" step="0.1" min="0" max="2"
                               value="{{ $values['llm_temperature'] ?? '0' }}">
                        <p class="field__help">۰ = قطعی، ۱ = خلاقانه‌تر</p>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn--ghost" type="button"
                            data-action="{{ route('admin.ai-content.test-llm') }}">
                        🔌 تست اتصال LLM
                    </button>
                </div>
                <div class="muted small" dir="ltr">
                    @if(session('test_result') === 'ok')
                        ✅ اتصال موفق بود.
                    @elseif(session('test_result') === 'fail')
                        ❌ شکست: {{ session('error') }}
                    @endif
                </div>
            </fieldset>

            <fieldset>
                <legend>📣 تلگرام</legend>
                <div class="form-grid">
                    <div class="field field--col-12">
                        <label class="field__label" for="telegram_bot_token">Bot Token</label>
                        <input id="telegram_bot_token" name="telegram_bot_token" value="{{ $values['telegram_bot_token'] }}"
                               dir="ltr" placeholder="123456789:ABC…" autocomplete="off">
                        <p class="field__help">از @BotFather بگیرید.</p>
                    </div>
                    <div class="field field--col-6">
                        <label class="field__label" for="telegram_webhook_secret">Webhook Secret</label>
                        <input id="telegram_webhook_secret" name="telegram_webhook_secret" value="{{ $values['telegram_webhook_secret'] }}"
                               dir="ltr" placeholder="یک رشته رندوم">
                    </div>
                    <div class="field field--col-6">
                        <label class="field__label" for="telegram_allowed_chat_ids">Chat IDهای مجاز</label>
                        <input id="telegram_allowed_chat_ids" name="telegram_allowed_chat_ids" value="{{ $values['telegram_allowed_chat_ids'] }}"
                               dir="ltr" placeholder="123456, 789012 (خالی = همه)">
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn--ghost" type="button"
                            data-action="{{ route('admin.ai-content.register-webhook') }}">
                        📡 ثبت Webhook در تلگرام
                    </button>
                    <a class="btn btn--sm btn--primary" target="_blank"
                       href="https://t.me/TelegramWebhookDocs">📖 راهنما</a>
                </div>
            </fieldset>

            <div class="form-actions">
                <button class="btn btn--primary" type="submit">ذخیره تنظیمات</button>
            </div>
        </form>
    </div>

    <script>
        // Test LLM button.
        document.querySelectorAll('[data-action]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.action;
                if (!confirm('اتصال به LLM را تست کنیم؟')) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';
                const token = document.querySelector('meta[name="csrf-token"]').content;
                form.innerHTML = '<input type="hidden" name="_token" value="' + token + '">';
                document.body.appendChild(form);
                form.submit();
            });
        });
    </script>
@endsection
