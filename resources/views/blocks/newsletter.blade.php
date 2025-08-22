{{#
    Configure newsletter content inside Control Panel in /cp/globals/newsletter
    Configure email service provider logic inside app/Http/Controllers/NewsletterController.php
#}}

{{ once }}
    {{ push:scripts }}
        {{ vite src="resources/js/components/newsletter.js" }}
    {{ /push:scripts }}
{{ /once }}

<div
    x-cloak
    x-show="!isSubscribed"
    x-data="newsletter({
        form: $form('post', '{{ route:newsletter }}', {
            email: '',
            honeypot: '',
        }),
    })"
    class="m-section"
>
    <div class="container">
        {{ partial:partials/section-header :title="newsletter:title" :text="newsletter:text" margin="mb-10" }}

        <form
            x-ref="form"
            x-show="!success"
            @submit.prevent="submit()"
            class="mx-auto max-w-md grid gap-2"
        >
            <div class="flex flex-col md:flex-row gap-3">
                {{ partial:components/ui/form/honeypot model="form.honeypot" honeypot="fax_number" }}
                <input type="hidden" name="_token" value="{{ csrf_token }}">

                {{ partial:components/ui/label id="{type}-{count}-email" display="Email" hide_display="true" }}
                {{ partial:components/ui/form/text id="{type}-{count}-email" model="form.email" handle="email" input_type="email" autocomplete="email" :placeholder="newsletter:input_placeholder" }}

                {{ partial:components/ui/form/submit class="sm:w-auto shrink-0" :label="newsletter:button_label" }}
            </div>

            {{ partial:components/ui/input-error handle="email" }}
        </form>

        <template x-if="success">
            {{ partial:components/ui/alert style="success" :title="newsletter:success_message" class="mx-auto max-w-md mt-10" }}
        </template>
    </div>
</div>
