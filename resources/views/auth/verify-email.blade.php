<x-layouts.auth
    title="{{ __('Verify your email address') }}"
    description="{!! __('Please verify your email address by clicking on the link we just emailed to you.') !!}"
>
    @if (session('status') === 'verification-link-sent')
        <x-ui.alert
            type="success"
            title="{{ __('Sent') }}"
            description="{{ __('A new verification link has been sent to the email address you provided during registration.') }}"
        />
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn--primary w-full">
            {{ __('Resend verification email') }}
        </button>
    </form>

    <div class="text-center">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn--link">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</x-layouts.auth>
