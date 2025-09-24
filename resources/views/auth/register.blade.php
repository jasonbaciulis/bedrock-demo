<x-layouts.auth
    title="{{ __('Create an account') }}"
    description="{{ __('Enter your details below to create account') }}"
>
    <form
        class="card__content gap-6"
        x-data="{
            form: $form('post', '{{ route('register') }}', {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
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
            <x-ui.label id="name" display="{{ __('Full name') }}" />
            <div class="grid gap-2">
                <x-ui.form.text
                    model="form.name"
                    handle="name"
                    id="name"
                    autocomplete="name"
                    placeholder="{{ __('John Doe') }}"
                    autofocus
                />
                <x-ui.input-error handle="name" id="name" />
            </div>
        </div>

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
                    autocomplete="new-password"
                    type="password"
                />
                <x-ui.input-error handle="password" id="password" />
            </div>
        </div>

        <div class="grid content-start gap-3">
            <x-ui.label id="password_confirmation" display="{{ __('Confirm password') }}" />
            <div class="grid gap-2">
                <x-ui.form.text
                    model="form.password_confirmation"
                    handle="password_confirmation"
                    id="password_confirmation"
                    autocomplete="new-password"
                    type="password"
                />
                <x-ui.input-error handle="password_confirmation" id="password_confirmation" />
            </div>
        </div>

        <x-ui.form.submit label="{{ __('Create account') }}" />
    </form>

    <p class="text-center text-sm">
        {{ __('Already have an account?') }}
        <a href="{{ route('login') }}" class="underline underline-offset-4 hover:no-underline">
            {{ __('Sign in') }}
        </a>
    </p>
</x-layouts.auth>
