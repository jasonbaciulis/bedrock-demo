<template x-teleport="body">
    {{-- TODO: if any toats are hovered, reset the timer, on hover away, restart --}}
    <ul
        x-cloak
        x-data="toaster({
                    position: 'bottom-right',
                    paddingBetweenToasts: 15,
                })"
        x-on:toast-show.window="
            event.stopPropagation()
            toasts.unshift({
                id: 'toast-' + Math.random().toString(16).slice(2),
                show: false,
                message: event.detail.message,
                description: event.detail.description,
                type: event.detail.type,
                html: event.detail.html,
            })
        "
        x-on:mouseenter="toastsHovered = true"
        x-on:mouseleave="toastsHovered = false"
        x-init="
            stackToasts()
            $watch('toastsHovered', function (value) {
                if (position.includes('bottom')) {
                    resetBottom()
                } else {
                    resetTop()
                }

                if (value) {
                    // calculate the new positions
                    expanded = true
                    stackToasts()
                } else {
                    expanded = false
                    //setTimeout(function(){
                    stackToasts()
                    //}, 10);
                    setTimeout(function () {
                        stackToasts()
                    }, 10)
                }
            })
        "
        class="group fixed z-[99] block w-full sm:max-w-xs"
        x-bind:class="{
            'right-0 top-0 sm:mt-6 sm:mr-6': position == 'top-right',
            'left-0 top-0 sm:mt-6 sm:ml-6': position == 'top-left',
            'left-1/2 -translate-x-1/2 top-0 sm:mt-6': position == 'top-center',
            'right-0 bottom-0 sm:mr-6 sm:mb-6': position == 'bottom-right',
            'left-0 bottom-0 sm:ml-6 sm:mb-6': position == 'bottom-left',
            'left-1/2 -translate-x-1/2 bottom-0 sm:mb-6': position == 'bottom-center',
        }"
    >
        <template x-for="(toast, index) in toasts" :key="toast.id">
            <li
                x-bind:id="toast.id"
                x-data="{
                    toastHovered: false,
                }"
                x-init="
                    if (position.includes('bottom')) {
                        $el.firstElementChild.classList.add('toast-bottom')
                        $el.firstElementChild.classList.add('opacity-0', 'translate-y-full')
                    } else {
                        $el.firstElementChild.classList.add('opacity-0', '-translate-y-full')
                    }
                    setTimeout(function () {
                        setTimeout(function () {
                            if (position.includes('bottom')) {
                                $el.firstElementChild.classList.remove(
                                    'opacity-0',
                                    'translate-y-full',
                                )
                            } else {
                                $el.firstElementChild.classList.remove(
                                    'opacity-0',
                                    '-translate-y-full',
                                )
                            }
                            $el.firstElementChild.classList.add('opacity-100', 'translate-y-0')

                            setTimeout(function () {
                                stackToasts()
                            }, 10)
                        }, 5)
                    }, 50)

                    setTimeout(function () {
                        setTimeout(function () {
                            $el.firstElementChild.classList.remove('opacity-100')
                            $el.firstElementChild.classList.add('opacity-0')
                            if (toasts.length == 1) {
                                $el.firstElementChild.classList.remove('translate-y-0')
                                $el.firstElementChild.classList.add('-translate-y-full')
                            }
                            setTimeout(function () {
                                deleteToastWithId(toast.id)
                            }, 300)
                        }, 5)
                    }, 4000)
                "
                x-on:mouseover="toastHovered = true"
                x-on:mouseout="toastHovered = false"
                class="absolute w-full duration-300 ease-out select-none sm:max-w-xs"
                x-bind:class="{ 'toast-no-description': ! toast.description }"
            >
                <span
                    class="group relative flex w-full flex-col items-start border border-gray-100 bg-white p-4 shadow-[0_5px_15px_-3px_rgb(0_0_0_/_0.08)] transition-all duration-300 ease-out sm:max-w-xs sm:rounded-md"
                    x-bind:class="{ 'p-4': ! toast.html, 'p-0': toast.html }"
                >
                    <template x-if="!toast.html">
                        <div class="relative flex flex-col gap-y-0.5">
                            <div
                                class="flex items-center gap-x-1.5"
                                x-bind:class="{
                                    'text-green-500': toast.type == 'success',
                                    'text-blue-500': toast.type == 'info',
                                    'text-orange-400': toast.type == 'warning',
                                    'text-red-500': toast.type == 'danger',
                                    'text-gray-800': toast.type == 'default',
                                }"
                            >
                                <template x-if="toast.type == 'success'">
                                    <x-lucide-circle-check class="-ml-1 size-4" />
                                </template>
                                <template x-if="toast.type == 'info'">
                                    <x-lucide-info class="-ml-1 size-4" />
                                </template>
                                <template x-if="toast.type == 'warning'">
                                    <x-lucide-triangle-alert class="-ml-1 size-4" />
                                </template>
                                <template x-if="toast.type == 'danger'">
                                    <x-lucide-circle-alert class="-ml-1 size-4" />
                                </template>

                                <p
                                    class="text-foreground text-sm font-medium"
                                    x-text="toast.message"
                                ></p>
                            </div>
                            <template x-if="toast.description">
                                <p
                                    class="text-muted-foreground text-xs"
                                    x-bind:class="{ 'pl-4.5': toast.type != 'default' }"
                                    x-text="toast.description"
                                ></p>
                            </template>
                        </div>
                    </template>
                    <template x-if="toast.html">
                        <div x-html="toast.html"></div>
                    </template>
                </span>
            </li>
        </template>
    </ul>
</template>
