<section class="submit-section" id="submit">
    <details class="submit-details" @if(session('submit_success') || $errors->any()) open @endif>
        <summary>
            <span class="submit-details__title">@include('public.partials.icon', ['name' => 'inbox']){{ __('ui.submit_title') }}</span>
            <span class="submit-details__hint">{{ __('ui.submit_toggle_hint') }}</span>
        </summary>

        <div class="submit-details__body">
            @if(session('submit_success'))
                <p class="flash flash--success" role="status">{{ session('submit_success') }}</p>
            @endif

            @if($errors->any())
                <p class="flash flash--error" role="alert">{{ __('ui.submit_fix_errors') }}:</p>
                <ul class="flash flash--error flash--list">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('public.submit') }}" class="submit-form" id="submit-form">
                @csrf
                <div class="submit-form__grid">
                    <label class="field">{{ __('ui.submit_service') }}
                        <input type="text" name="provider_name" value="{{ old('provider_name') }}" required maxlength="191" placeholder="{{ __('ui.submit_service_ph') }}">
                    </label>
                    <label class="field">{{ __('ui.submit_url') }}
                        <input type="url" name="url" value="{{ old('url') }}" required maxlength="500" dir="ltr" placeholder="{{ __('ui.submit_url_ph') }}">
                    </label>
                </div>
                <label class="field">{{ __('ui.submit_note') }}
                    <textarea name="offer_note_fa" rows="3" maxlength="2000" placeholder="{{ __('ui.submit_note_ph') }}">{{ old('offer_note_fa') }}</textarea>
                </label>
                <label class="field">{{ __('ui.submit_email') }}
                    <input type="text" name="contact" value="{{ old('contact') }}" maxlength="191" placeholder="ایمیل یا آیدی تلگرام">
                </label>

                {{-- Honeypot: hidden from humans, irresistible to bots --}}
                <div class="hp" aria-hidden="true">
                    <label>این فیلد را پر نکنید
                        <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
                    </label>
                </div>

                <button type="submit" class="btn btn--primary">{{ __('ui.submit_button') }}</button>
            </form>
        </div>
    </details>
</section>
