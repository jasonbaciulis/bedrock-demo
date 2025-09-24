<x-layouts.auth title="{{ __('Welcome back') }}" description="{{ __('Login to your account') }}">
    <form
        class="card__content gap-6"
        x-data="{
            form: $form('post', '{{ route('login') }}', {
                email: '',
                password: '',
                remember: false,
            }),

            success: false,

            init() {
                this.form.setValidationTimeout(100)
            },

            submit() {
                this.form
                    .submit()
                    .then(response => {
                        if (response?.data?.success) {
                            this.success = true
                            this.form.reset()
                            if (response.data?.redirect) {
                                window.location.href = response.data.redirect
                            }
                        }
                    })
                    .catch(error => {
                        console.log(error)
                    })
            },
        }"
        x-on:submit.prevent="submit()"
    >
        @csrf

        <div class="grid content-start gap-3">
            <x-ui.label id="email" display="{{ __('Email') }}" />
            <div class="grid gap-2">
                <x-ui.form.text
                    model="form.email"
                    handle="email"
                    id="email"
                    type="email"
                    placeholder="example@email.com"
                />
                <x-ui.input-error handle="email" id="email" />
            </div>
        </div>

        <div class="grid content-start gap-3">
            <x-ui.label id="password" display="{{ __('Password') }}" />
            <div class="grid gap-2">
                <x-ui.form.text
                    model="form.password"
                    handle="password"
                    id="password"
                    autocomplete="password"
                    type="password"
                />
                <x-ui.input-error handle="password" id="password" />
            </div>
        </div>

        <div class="flex items-center justify-between">
            <x-ui.form.toggle
                model="form.remember"
                inline_label="{{ __('Remember me') }}"
                handle="remember"
                id="remember"
            />

            @if (Route::has('password.request'))
                <a
                    href="{{ route('password.request') }}"
                    class="text-sm underline underline-offset-4 hover:no-underline"
                >
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-ui.form.submit label="{{ __('Log in') }}" />
    </form>

    @if (Route::has('register'))
        <div class="text-center text-sm">
            {{ __('Don\'t have an account?') }}
            <a
                href="{{ route('register') }}"
                class="underline underline-offset-4 hover:no-underline"
            >
                {{ __('Sign up') }}
            </a>
        </div>
    @endif
</x-layouts.auth>
