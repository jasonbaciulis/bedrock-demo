@once
    @push('scripts')
        <script src="/vendor/statamic/frontend/js/helpers.js" defer></script>
    @endpush
@endonce

<section class="py-8 md:py-16 lg:py-20">
    <div class="container flex flex-col items-center gap-4 text-center xl:gap-6">
        <h1 class="h1 text-balance">{{ $block->title }}</h1>
        <p class="content-lg max-w-3xl text-balance text-muted-foreground">{{ $block->text }}</p>
        <x-buttons :buttons="$block->buttons" class="gap-x-3" size="xl" />
    </div>

    <div id="components" class="container max-w-screen-2xl site-grid pt-16 md:pt-24 lg:pt-32 gap-4 items-start">
        <div class="col-span-full xl:col-span-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-4">
                {{-- Contact Form --}}
                <div
                    x-data="{
                        form: $form('post', '', {
                            name: '',
                            email: '',
                            message: '',
                        }),
                    }"
                    class="col-span-full md:col-span-6 lg:col-span-3"
                >
                    {{-- <div class="card shadow-none">
                        <div class="card__header">
                            <p class="leading-none font-semibold">Contact us</p>
                            <p class="text-muted-foreground text-sm">Get in touch and we'll respond within 24 hours.</p>
                        </div>
                        <div class="flex flex-col gap-4 flex-1">
                            <div class="grid gap-3">
                                <x-ui.label id="contact_name" display="Name" />
                                <x-ui.form.text model="form.name" handle="contact_name" id="contact_name" placeholder="Your full name" />
                            </div>
                            <div class="grid gap-3">
                                <x-ui.label id="contact_email" display="Email" />
                                <x-ui.form.text model="form.email" handle="contact_email" id="contact_email" autocomplete="email" placeholder="your@email.com" />
                            </div>
                            <div class="grid gap-3">
                                <x-ui.label id="contact_message" display="Message" />
                                <x-ui.form.textarea model="form.message" handle="contact_message" id="contact_message" class="min-h-24" placeholder="Tell us about your project…" />
                            </div>
                            <div class="flex justify-end">
                                <x-ui.button as="button">Submit</x-ui.button>
                            </div>
                        </div>
                    </div> --}}
                </div>

                 {{-- Newsletter Form --}}
                <div
                    x-data="{
                        form: $form('post', '', {
                            email: '',
                            frequency: 'weekly',
                            topics: [],
                            marketing: false,
                        }),
                    }"
                    class="col-span-full md:col-span-6 lg:col-span-3"
                >
                    {{-- <div class="card shadow-none">
                        <div class="card__header">
                            <p class="leading-none font-semibold">Newsletter</p>
                            <p class="text-muted-foreground text-sm">Stay updated with our latest insights and tips.</p>
                        </div>
                        <div class="flex flex-col gap-4 flex-1">
                            <div class="grid gap-3">
                                <x-ui.label id="newsletter_email" display="Email Address" />
                                <x-ui.form.text model="form.email" handle="email" id="newsletter_email" autocomplete="email" placeholder="your@email.com" />
                            </div>
                            <div class="grid gap-3">
                                <x-ui.label id="frequency" display="Frequency" />
                                <x-ui.form.select model="form.frequency" handle="frequency" id="frequency" :options="[
                                    'daily' => 'Daily updates',
                                    'weekly' => 'Weekly digest',
                                    'monthly' => 'Monthly newsletter'
                                ]" />
                            </div>
                            <div class="grid gap-3">
                                <x-ui.form.checkboxes model="form.topics" handle="topics" id="topics" display="Topics" :options="[
                                    'design' => 'Design tips',
                                    'development' => 'Development news',
                                    'marketing' => 'Marketing insights'
                                ]" />
                            </div>
                            <div class="flex justify-end">
                                <x-ui.button as="button">Subscribe</x-ui.button>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>

            <div class="space-y-4">
                {{-- Carousel --}}
                <div class="col-span-full md:col-span-6 lg:col-span-3">
                    <div class="card shadow-none">
                        <div class="card__header">
                            <p class="leading-none font-semibold">Featured posts</p>
                            <p class="text-muted-foreground text-sm">Browse our most popular posts.</p>
                        </div>

                        <div class="px-8 flex-1 flex items-center">
                            <x-ui.carousel class="w-full max-w-full" opts="loop: true, align: center" />
                                <x-slot:content>
                                    @for ($i = 1; $i <= 5; $i++)
                                        <x-ui.carousel.slide>
                                            <div class="p-1">
                                                <div class="card aspect-square lg:aspect-3/2 xl:aspect-square items-center justify-center">
                                                    <span class="text-4xl font-semibold">{{ $i }}</span>
                                                </div>
                                            </div>
                                        </x-ui.carousel.slide>
                                    @endfor
                                </x-slot:content>

                                <x-slot:navigation>
                                    <x-ui.carousel.previous class="btn--outline btn--round" />
                                    <x-ui.carousel.next class="btn--outline btn--round" />
                                </x-slot:navigation>
                            </x-ui.carousel>
                        </div>
                    </div>
                </div>

                {{-- Preferences Form --}}
                <div
                    x-data="{
                        form: $form('post', '', {
                            theme: 'system',
                            language: 'en',
                            notifications: false,
                        }),
                    }"
                    class="col-span-full md:col-span-6 lg:col-span-3"
                >
                    <div class="card shadow-none">
                        <div class="card__header">
                            <p class="leading-none font-semibold">Preferences</p>
                            <p class="text-muted-foreground text-sm">Customize your experience.</p>
                        </div>
                        <div class="flex flex-col gap-6 flex-1">
                            <div class="grid gap-6">
                                <div class="grid gap-3">
                                    <x-ui.form.radio id="theme" handle="theme" model="form.theme" display="Theme" :options="[
                                        'light' => 'Light mode',
                                        'dark' => 'Dark mode',
                                        'system' => 'System default'
                                    ]" />
                                </div>
                                <div class="grid gap-3">
                                    <x-ui.label id="language" display="Language" />
                                    @php
                                    $languages = [
                                        'en' => 'English',
                                        'es' => 'Spanish',
                                        'fr' => 'French',
                                        'de' => 'German',
                                        'it' => 'Italian',
                                        'pt' => 'Portuguese',
                                        'ru' => 'Russian',
                                        'zh' => 'Chinese',
                                        'ja' => 'Japanese',
                                        'ko' => 'Korean',
                                        'ar' => 'Arabic',
                                    ]
                                    @endphp
                                    <x-ui.form.combobox id="language" handle="language" model="form.language" placeholder="Select language…" :options="$languages" />
                                </div>
                                <div class="grid gap-3">
                                    <x-ui.form.toggle model="form.notifications" handle="notifications" id="notifications" inline_label="Notifications" />
                                </div>
                            </div>
                            <x-ui.button class="w-full" button_type="outline">Save preferences</x-ui.button>
                        </div>
                    </div>
                </div>

                {{-- Alert --}}
                <div class="col-span-full md:col-span-6 lg:col-span-3">
                    <x-ui.alert style="success" title="Success! Your changes have been saved." description="This is an alert with icon, title and description." />
                </div>
            </div>
        </div>

        <div class="col-span-full xl:col-span-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Tabs --}}
            <div class="xl:col-span-full">
                @php
                $services_tabs = [
                    (object)[
                        'title' => 'AI agents',
                        'description' => 'AI agents can help you with a wide range of tasks, from customer service to data analysis.',
                        'features' => ['Automated workflows', 'Data analysis', 'Customer service']
                    ],
                    (object)[
                        'title' => 'Membership platforms',
                        'description' => 'Create a membership platform for your business.',
                        'features' => ['Membership management', 'Payment processing', 'User management']
                    ],
                    (object)[
                        'title' => 'Web apps',
                        'description' => 'Build apps for your business.',
                        'features' => ['Filament dashboards', 'SPA apps with Inertia and Vue/React', 'Laravel apps']
                    ],
                    (object)[
                        'title' => 'Marketing websites',
                        'description' => 'Build high-converting websites.',
                        'features' => ['Beautiful design', 'Lightning-fast loading', 'SEO-friendly']
                    ],
                ]
                @endphp

                <x-ui.tabs>
                    <x-slot:list class="bg-muted text-muted-foreground flex min-h-9 w-fit items-center justify-center rounded-lg p-[3px] outline-none flex-wrap">
                        @foreach ($services_tabs as $tab)
                            <x-ui.tabs.trigger
                                :name="$loop->iteration"
                                class="btn text-foreground inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-1.5 border border-transparent px-2 py-1"
                                x-bind:class="{ 'bg-background shadow-sm': activeTab == '{{ $loop->iteration }}' }">
                                {{ $tab->title }}
                            </x-ui.tabs.trigger>
                        @endforeach
                    </x-slot:list>

                    <x-slot:panels>
                        @foreach ($services_tabs as $tab)
                            <x-ui.tabs.panel :name="$loop->iteration" class="flex-1 outline-none">
                                <div class="card shadow-none">
                                    <p class="text-sm">{{ $tab->description }}</p>
                                    <ul class="text-sm space-y-1 list-disc list-inside">
                                        @foreach ($tab->features as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    </ul>
                                    <div>
                                        <x-ui.button button_type="outline" size="sm">Learn more</x-ui.button>
                                    </div>
                                </div>
                            </x-ui.tabs.panel>
                        @endforeach
                    </x-slot:panels>
                </x-ui.tabs>
            </div>

            {{-- Interactive Components and FAQs --}}
            <div class="xl:col-span-full space-y-4">
                {{-- Collapsible FAQ --}}
                <div class="card shadow-none">
                    <div class="card__header">
                        <p class="font-semibold text-xl">FAQs</p>
                        <p class="text-muted-foreground text-sm">Here are some of the most frequently asked questions.</p>
                    </div>

                    @php
                    $faqs = [
                        (object)[
                            'question' => 'How long does a project take?',
                            'answer' => 'Project timelines vary based on complexity, but most websites take 4-8 weeks from start to finish.'
                        ],
                        (object)[
                            'question' => 'Do you offer maintenance?',
                            'answer' => 'Yes, we provide ongoing maintenance and support packages to keep your website running smoothly.'
                        ],
                        (object)[
                            'question' => 'How do I get started?',
                            'answer' => 'Contact us to discuss your project and we’ll provide a free consultation to help you get started.'
                        ],
                    ]
                    @endphp
                    <div class="space-y-2">
                        @foreach ($faqs as $faq)
                            <x-ui.collapsible>
                                <x-slot:trigger class="flex w-full items-center justify-between text-left text-sm font-medium py-2">
                                    {{ $faq->question }}
                                    <x-lucide-chevron-down class="size-4 transition-transform" x-bind:class="open && 'rotate-180'" />
                                </x-slot:trigger>
                                <x-slot:content class="text-sm text-muted-foreground">
                                    <p>{{ $faq->answer }}</p>
                                </x-slot:content>
                            </x-ui.collapsible>
                        @endforeach
                    </div>
                </div>

                {{-- Dialog and Dropdown --}}
                <div class="card shadow-none">
                    <div class="card__header">
                        <p class="leading-none font-semibold">Interactive elements</p>
                        <p class="text-muted-foreground text-sm">Explore our components.</p>
                    </div>

                    {{-- Dialog --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm">Share bedrock</p>
                            <p class="text-sm text-muted-foreground">Share this starter-kit with your followers</p>
                        </div>
                        <x-ui.dialog size="sm">
                            <x-slot:trigger class="btn--outline btn--sm">
                                Share
                            </x-slot:trigger>
                            <x-slot:content>
                                <div class="flex flex-col gap-4">
                                    <div class="flex flex-col gap-2 text-center sm:text-left">
                                        <p class="text-lg leading-none font-semibold text-neutral-800">Share link</p>
                                        <p class="text-muted-foreground text-sm">Anyone who has this link will be able to view this.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label for="link" class="sr-only">Link</label>
                                        <input type="text" id="link" class="input" readonly value="https://bedrock.remarkable.dev" />
                                        <x-ui.button
                                            button_type="outline"
                                            as="button"
                                            type="button"
                                            x-on:click="navigator.clipboard.writeText('https://bedrock.remarkable.dev')"
                                        >
                                            Copy link
                                        </x-ui.button>
                                    </div>
                                    <div>
                                        <x-ui.button button_type="secondary" as="button" type="button" x-on:click="close()">Close</x-ui.button>
                                    </div>
                                </div>
                            </x-slot:content>
                        </x-ui.dialog>
                    </div>

                    {{-- Dropdown --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-sm">Quick actions</p>
                            <p class="text-sm text-muted-foreground">Common website actions</p>
                        </div>
                        <x-ui.dropdown alignment="right" width="w-48">
                            <x-slot:toggle class="btn--outline btn--sm">
                                Actions
                                <x-lucide-chevron-down class="size-4 opacity-50 -mr-1 mt-px" />
                            </x-slot:toggle>

                            <x-slot:content>
                                @foreach (['Edit Content', 'Share Page', 'Export Data', 'Settings'] as $action)
                                    <x-ui.dropdown.item href="javascript:void(0)">
                                        <x-lucide-edit class="size-4" />
                                        {{ $action }}
                                    </x-ui.dropdown.item>
                                @endforeach
                            </x-slot:content>
                        </x-ui.dropdown>
                    </div>
                </div>

                {{-- Mock pagination --}}
                <div class="card shadow-none">
                    <nav class="flex justify-center items-center gap-1">
                        <button class="btn btn--ghost shrink-0">
                            <x-lucide-chevron-left class="size-4" />
                            <span class="sr-only lg:not-sr-only">Previous</span>
                        </button>

                        <div class="flex gap-1">
                            <button class="btn btn--ghost btn--square">1</button>
                            <button class="btn btn--outline btn--square">2</button>
                            <button class="btn btn--ghost btn--square">3</button>
                            <span class="flex size-9 items-center justify-center">
                                <x-lucide-ellipsis class="size-4" />
                            </span>
                            <button class="btn btn--ghost btn--square">12</button>
                        </div>

                        <button class="btn btn--ghost shrink-0">
                            <span class="sr-only lg:not-sr-only">Next</span>
                            <x-lucide-chevron-right class="size-4" />
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</section>
