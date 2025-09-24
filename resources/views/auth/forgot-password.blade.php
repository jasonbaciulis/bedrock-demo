<x-layouts.auth
    title="{{ __('Forgot password') }}"
    description="{{ __('Enter your email to receive a password reset link') }}"
>
    <form
        class="card__content gap-6"
        x-data="{
            form: $form('post', '{{ route('password.email') }}', {
                email: '',
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

        <x-ui.alert type="success" x-show="success" title='<span x-text="status"></span>' />

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

        <x-ui.form.submit label="{{ __('Email password reset link') }}" />
    </form>

    <div class="text-center text-sm">
        {{ __('Or, return to') }}
        <a href="{{ route('login') }}" class="underline underline-offset-4 hover:no-underline">
            {{ __('log in') }}
        </a>
    </div>
</x-layouts.auth>
