{{--
    Configure newsletter content inside Control Panel in /cp/globals/newsletter
    Configure email service provider logic inside app/Http/Controllers/NewsletterController.php
--}}

@once
    @push('scripts')
        @vite('resources/js/components/newsletter.js')
    @endpush
@endonce

<div
    x-cloak
    x-show="!isSubscribed"
    x-data="newsletter({
        form: $form('post', '{{ route('newsletter') }}', {
            email: '',
            honeypot: '',
        }),
    })"
    class="m-section"
>
    <div class="container">
        @include('partials.section-header', [
            'title' => $newsletter->title,
            'text' => $newsletter->text ?? null,
            'margin' => 'mb-10',
        ])

        <form
            x-ref="form"
            x-show="!success"
            x-on:submit.prevent="submit()"
            class="mx-auto max-w-md grid gap-2"
        >
            <div class="flex flex-col md:flex-row gap-3">
                <x-ui.form.honeypot model="form.honeypot" handle="fax_number" />
                <input type="hidden" name="_token" value="{{ csrf_token() }}" />

                <x-ui.label id="{{ $block->type }}-{{ $loop->iteration }}-email" display="Email" hide_display="true" />
                <x-ui.form.text id="{{ $block->type }}-{{ $loop->iteration }}-email" model="form.email" handle="email" input_type="email" autocomplete="email" :placeholder="$newsletter->input_placeholder" />

                <x-ui.button class="sm:w-auto shrink-0" type="submit" as="button">{{ $newsletter->button_label }}</x-ui.button>
            </div>

            <x-ui.input-error handle="email" id="{{ $block->type }}-{{ $loop->iteration }}-email" />
        </form>

        <template x-if="success">
            <x-ui.alert style="success" class="mx-auto max-w-md mt-10" :title="$newsletter->success_message" />
        </template>
    </div>
</div>
