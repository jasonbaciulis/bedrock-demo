<x-layouts.auth
    title="{{ __('Reset password') }}"
    description="{{ __('Please enter your new password below') }}"
>
    <form
        class="card__content gap-6"
        x-data="{
            form: $form('post', '{{ route('password.store') }}', {
                email: '{{ $email }}',
                token: '{{ $token }}',
                password: '',
                password_confirmation: '',
            }),

            status: '',
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
                            this.status = response.data.status
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

        <x-ui.alert type="success" x-show="success" title="<span x-text='status'></span>" />

        <div class="grid content-start gap-3">
            <x-ui.label id="email" display="{{ __('Email') }}" />
            <div class="grid gap-2">
                <x-ui.form.text
                    model="form.email"
                    handle="email"
                    id="email"
                    type="email"
                    readonly
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

        <x-ui.form.submit label="{{ __('Reset password') }}" />
    </form>
</x-layouts.auth>
