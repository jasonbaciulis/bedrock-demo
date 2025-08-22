{{--
    Component for outputting a Statamic form fields grouped in sections. A noteworthy thing is that we keep
    the form fields components inside the views/components/ui/form folder instead of publishing and customizing
    a vendor folder like the docs suggest: https://statamic.dev/tags/form-create#prerendered-field-html
    This gives us more control over the form fields and allows us to reuse the same form fields outside of
    the Statamic form. We, also use Laravel Precognition to handle the form submission and validation.

    Docs:
        https://statamic.dev/forms
        https://statamic.dev/tags/form-create
        https://statamic.dev/forms#precognition
        https://laravel.com/docs/12.x/precognition#using-alpine
--}}

@props(['form', 'success_message', 'submit_label'])

@once
    @push('scripts')
        <script src="/vendor/statamic/frontend/js/helpers.js" defer></script>
    @endpush
@endonce

<s:form:create
    :in="$form->handle"
    :id="'form-'. $form->handle"
    csrf="false"
    x-ref="form"
    js="alpine"
>
    <div
        x-data="{
            form: $form('post', $refs.form.action, JSON.parse($refs.form.getAttribute('x-data'))),
            success: false,
            init() {
                this.form.setValidationTimeout(100)
                $refs.form.addEventListener('submit', e => {
                    e.preventDefault();
                    this.form
                        .submit()
                        .then(response => {
                            if (response?.data?.success) {
                                this.success = true
                                this.form.reset()
                            }
                            this.$refs.form.scrollIntoView({ behavior: 'smooth' })
                        })
                        .catch(error => {
                            console.log(error)
                        })
                })
            },
        }"
    >
        <template x-if="success">
            <x-ui.alert style="success" :title="$success_message" class="mb-8" />
        </template>

        @csrf
        <x-ui.form.honeypot model="form.{{ $form->honeypot }}" handle="{{ $form->honeypot }}" />


        <div class="space-y-12 divide-y divide-gray-300">
            @foreach ($sections as $section)
                <div class="@class(['pb-12' => !$loop->last])">
                    @unless (empty($section['display']))
                        <h3 class="h6 mb-8">{!! $section['display'] !!}</h3>
                    @endunless

                    <div class="grid md:grid-cols-12 gap-y-6 gap-x-8">
                        @foreach ($section['fields'] as $field)

                            @php
                                $width = $field['width'] ?? '100';
                                $field_container_classes = match($width) {
                                    25, '25' => 'md:col-span-3',
                                    33, '33' => 'md:col-span-4',
                                    50, '50' => 'md:col-span-6',
                                    66, '66' => 'md:col-span-8',
                                    75, '75' => 'md:col-span-9',
                                    default => 'md:col-span-12'
                                };
                            @endphp


                            <x-form-field
                                :field="(object)$field"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-8">
            <x-ui.form.submit :label="$submit_label" />
        </div>
    </div>
</s:form:create>
